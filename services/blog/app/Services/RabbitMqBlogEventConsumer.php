<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\ProcessedDomainEvent;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
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

        $this->declareTopology($channel);

        $queue = config('rabbitmq.queues.search_indexer');
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
                $payload = $this->decodePayload($message);
                $attempt = $this->retryCount($message) + 1;
                $maxAttempts = (int) config('rabbitmq.max_attempts');

                Log::warning('RabbitMQ consumer failed.', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'exception' => $exception->getMessage(),
                    'payload' => $message->getBody(),
                ]);

                $attempt >= $maxAttempts
                    ? $this->publishFailed($message, $payload, $attempt)
                    : $this->publishRetry($message, $payload, $attempt);

                $message->ack();
                $command->error($exception->getMessage());
            }
        });

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function declareTopology(AMQPChannel $channel): void
    {
        $exchange = config('rabbitmq.exchange');
        $retryExchange = config('rabbitmq.retry_exchange');
        $failedExchange = config('rabbitmq.failed_exchange');
        $queue = config('rabbitmq.queues.search_indexer');
        $retryQueue = config('rabbitmq.queues.search_indexer_retry');
        $failedQueue = config('rabbitmq.queues.search_indexer_failed');

        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->exchange_declare($retryExchange, 'topic', false, true, false);
        $channel->exchange_declare($failedExchange, 'topic', false, true, false);

        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, 'blog.post.published.v1');
        $channel->queue_bind($queue, $exchange, 'blog.post.archived.v1');

        $channel->queue_declare($retryQueue, false, true, false, false, false, new AMQPTable([
            'x-message-ttl' => (int) config('rabbitmq.retry_ttl_ms'),
            'x-dead-letter-exchange' => $exchange,
        ]));
        $channel->queue_bind($retryQueue, $retryExchange, 'blog.post.published.v1');
        $channel->queue_bind($retryQueue, $retryExchange, 'blog.post.archived.v1');

        $channel->queue_declare($failedQueue, false, true, false, false);
        $channel->queue_bind($failedQueue, $failedExchange, 'blog.post.published.v1');
        $channel->queue_bind($failedQueue, $failedExchange, 'blog.post.archived.v1');
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

    private function publishRetry(AMQPMessage $message, array $payload, int $attempt): void
    {
        $this->publishToExchange(
            config('rabbitmq.retry_exchange'),
            $payload['event_type'] ?? 'unknown',
            $message,
            $attempt,
        );
    }

    private function publishFailed(AMQPMessage $message, array $payload, int $attempt): void
    {
        $this->publishToExchange(
            config('rabbitmq.failed_exchange'),
            $payload['event_type'] ?? 'unknown',
            $message,
            $attempt,
        );
    }

    private function publishToExchange(string $exchange, string $routingKey, AMQPMessage $message, int $attempt): void
    {
        $channel = $message->getChannel();

        if (! $channel instanceof AMQPChannel) {
            throw new RuntimeException('RabbitMQ message channel is unavailable.');
        }

        /** @var AbstractConnection|null $connection */
        $connection = $channel->getConnection();
        $publisherChannel = $connection?->channel();

        if (! $publisherChannel instanceof AMQPChannel) {
            throw new RuntimeException('RabbitMQ publisher channel is unavailable.');
        }

        $publisherChannel->basic_publish(
            new AMQPMessage($message->getBody(), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => new AMQPTable([
                    'x-retry-count' => $attempt,
                ]),
            ]),
            $exchange,
            $routingKey,
        );
        $publisherChannel->close();
    }

    private function decodePayload(AMQPMessage $message): array
    {
        try {
            $payload = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);

            return is_array($payload) ? $payload : [];
        } catch (Throwable) {
            return [];
        }
    }

    private function retryCount(AMQPMessage $message): int
    {
        if (! $message->has('application_headers')) {
            return 0;
        }

        $headers = $message->get('application_headers');

        if (! $headers instanceof AMQPTable) {
            return 0;
        }

        return (int) ($headers->getNativeData()['x-retry-count'] ?? 0);
    }
}
