<?php

namespace App\Providers;

use App\Contracts\DomainEventPublisher;
use App\Services\RabbitMqDomainEventPublisher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute((int) env('AUTH_LOGIN_MAX_ATTEMPTS_PER_MINUTE', 5))
                ->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute((int) env('AUTH_REGISTER_MAX_ATTEMPTS_PER_MINUTE', 10))
                ->by($request->ip());
        });
    }
}
