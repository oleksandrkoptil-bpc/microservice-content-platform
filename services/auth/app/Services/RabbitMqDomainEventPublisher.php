<?php

namespace App\Services;

use App\Contracts\DomainEventPublisher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class RabbitMqDomainEventPublisher implements DomainEventPublisher
{
    public function publish(string $eventType, array $data = [], array $metadata = []): void
    {
        if (! config('rabbitmq.enabled')) {
            return;
        }

        try {
            $connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                (int) config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost'),
            );
            $channel = $connection->channel();
            $exchange = config('rabbitmq.exchange');

            $channel->exchange_declare($exchange, 'topic', false, true, false);

            $payload = [
                'event_id' => (string) Str::uuid(),
                'event_type' => $eventType,
                'event_version' => 1,
                'producer' => config('rabbitmq.service'),
                'occurred_at' => now()->toISOString(),
                'data' => $data,
                'metadata' => $metadata,
            ];

            $message = new AMQPMessage(
                json_encode($payload, JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'type' => $eventType,
                    'message_id' => $payload['event_id'],
                    'timestamp' => now()->timestamp,
                ],
            );

            $channel->basic_publish($message, $exchange, $eventType);
            $channel->close();
            $connection->close();
        } catch (Throwable $exception) {
            Log::warning('RabbitMQ publish failed.', [
                'event_type' => $eventType,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
