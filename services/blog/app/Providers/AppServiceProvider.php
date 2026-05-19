<?php

namespace App\Providers;

use App\Contracts\DomainEventPublisher;
use App\Services\RabbitMqDomainEventPublisher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DomainEventPublisher::class, RabbitMqDomainEventPublisher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
