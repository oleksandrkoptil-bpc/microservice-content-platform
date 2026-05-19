<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\ProcessedDomainEvent;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

class RabbitMqBlogEventConsumer
{
    public function __construct(private readonly PostSearchIndexer $searchIndexer) {}

    public function consume(object $command): void
    {
        $connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            (int) config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost'),
        );
        $channel = $connection->channel();
        $exchange = config('rabbitmq.exchange');
        $queue = config('rabbitmq.queues.search_indexer');

        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, 'blog.post.published.v1');
        $channel->queue_bind($queue, $exchange, 'blog.post.archived.v1');
        $channel->basic_qos(null, 1, null);

        $command->info("Listening RabbitMQ queue [{$queue}]...");

        $channel->basic_consume($queue, '', false, false, false, false, function (AMQPMessage $message) use ($command): void {
            try {
                $payload = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);
                $processed = $this->process($payload);

                $message->ack();

                $status = $processed ? 'Processed' : 'Skipped duplicate';
                $command->info($status.' '.$payload['event_type'].' '.$payload['event_id']);
            } catch (Throwable $exception) {
                Log::error('RabbitMQ consumer failed.', [
                    'exception' => $exception->getMessage(),
                    'payload' => $message->getBody(),
                ]);

                $message->nack(false, true);
                $command->error($exception->getMessage());
            }
        });

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function process(array $payload): bool
    {
        $eventType = $payload['event_type'] ?? null;
        $eventId = $payload['event_id'] ?? null;
        $postId = (int) data_get($payload, 'data.post_id');

        if (! is_string($eventId) || $eventId === '') {
            throw new RuntimeException('RabbitMQ event does not contain a valid event_id.');
        }

        if (! is_string($eventType) || $eventType === '') {
            throw new RuntimeException('RabbitMQ event does not contain a valid event_type.');
        }

        if ($this->wasProcessed($eventId)) {
            return false;
        }

        if ($postId < 1) {
            throw new RuntimeException('RabbitMQ event does not contain a valid post_id.');
        }

        match ($eventType) {
            'blog.post.published.v1' => $this->indexPublishedPost($postId),
            'blog.post.archived.v1' => $this->searchIndexer->delete($postId),
            default => null,
        };

        ProcessedDomainEvent::query()->create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'consumer' => config('rabbitmq.queues.search_indexer'),
            'processed_at' => now(),
        ]);

        return true;
    }

    private function wasProcessed(string $eventId): bool
    {
        return ProcessedDomainEvent::query()
            ->where('event_id', $eventId)
            ->where('consumer', config('rabbitmq.queues.search_indexer'))
            ->exists();
    }

    private function indexPublishedPost(int $postId): void
    {
        $post = Post::query()
            ->with(['category', 'tags'])
            ->findOrFail($postId);

        if ($post->status !== PostStatus::Published) {
            $this->searchIndexer->delete($postId);

            return;
        }

        $this->searchIndexer->index($post);
    }
}
