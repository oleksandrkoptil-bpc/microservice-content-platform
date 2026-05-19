<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiClient
{
    public function getBlog(string $path, array $query = []): Response
    {
        return Http::acceptJson()->get($this->url('blog', $path), $query);
    }

    public function postBlog(string $path, array $data = [], ?string $token = null): Response
    {
        $request = Http::acceptJson();

        if ($token) {
            $request = $request->withToken($token);
        }

        return $request->post($this->url('blog', $path), $data);
    }

    public function postAuth(string $path, array $data = [], ?string $token = null): Response
    {
        $request = Http::acceptJson();

        if ($token) {
            $request = $request->withToken($token);
        }

        return $request->post($this->url('auth', $path), $data);
    }

    private function url(string $service, string $path): string
    {
        return rtrim(config("services.{$service}.url"), '/').'/'.ltrim($path, '/');
    }
}
