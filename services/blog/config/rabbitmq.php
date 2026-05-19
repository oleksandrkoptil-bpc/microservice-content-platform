<?php

return [
    'enabled' => env('RABBITMQ_ENABLED', false),
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'blog'),
    'password' => env('RABBITMQ_PASSWORD', 'secret'),
    'vhost' => env('RABBITMQ_VHOST', 'blog'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'domain.events'),
    'retry_exchange' => env('RABBITMQ_RETRY_EXCHANGE', 'domain.events.retry'),
    'failed_exchange' => env('RABBITMQ_FAILED_EXCHANGE', 'domain.events.failed'),
    'retry_ttl_ms' => env('RABBITMQ_RETRY_TTL_MS', 10000),
    'max_attempts' => env('RABBITMQ_MAX_ATTEMPTS', 3),
    'service' => 'blog',
    'queues' => [
        'search_indexer' => env('RABBITMQ_SEARCH_INDEXER_QUEUE', 'blog.search.indexer'),
        'search_indexer_retry' => env('RABBITMQ_SEARCH_INDEXER_RETRY_QUEUE', 'blog.search.indexer.retry'),
        'search_indexer_failed' => env('RABBITMQ_SEARCH_INDEXER_FAILED_QUEUE', 'blog.search.indexer.failed'),
    ],
];
