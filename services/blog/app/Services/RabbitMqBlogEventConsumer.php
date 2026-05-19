<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
                $this->handle($payload);

                $message->ack();
                $command->info('Processed '.$payload['event_type'].' '.$payload['event_id']);
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

    private function handle(array $payload): void
    {
        $eventType = $payload['event_type'] ?? null;
        $postId = (int) data_get($payload, 'data.post_id');

        if ($postId < 1) {
            throw new \RuntimeException('RabbitMQ event does not contain a valid post_id.');
        }

        match ($eventType) {
            'blog.post.published.v1' => $this->indexPublishedPost($postId),
            'blog.post.archived.v1' => $this->searchIndexer->delete($postId),
            default => null,
        };
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
