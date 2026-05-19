<?php

use App\Services\RabbitMqBlogEventConsumer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rabbitmq:consume-blog-events', function () {
    app(RabbitMqBlogEventConsumer::class)->consume($this);
})->purpose('Consume blog RabbitMQ events and update read models');
