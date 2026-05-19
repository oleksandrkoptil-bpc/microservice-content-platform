<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AuthApiClient
{
    public function login(string $email, string $password): Response
    {
        return Http::acceptJson()
            ->timeout((int) config('services.http.timeout', 5))
            ->retry((int) config('services.http.retries', 2), 100)
            ->post($this->url('/login'), [
                'email' => $email,
                'password' => $password,
            ]);
    }

    private function url(string $path): string
    {
        return rtrim(config('services.auth.url'), '/').$path;
    }
}
