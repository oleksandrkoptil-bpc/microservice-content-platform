<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AuthApiClient
{
    public function login(string $email, string $password): Response
    {
        return Http::acceptJson()
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
