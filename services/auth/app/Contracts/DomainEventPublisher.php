<?php

namespace App\Contracts;

interface DomainEventPublisher
{
    public function publish(string $eventType, array $data = [], array $metadata = []): void;
}
