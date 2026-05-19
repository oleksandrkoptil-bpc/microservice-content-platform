<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BlogApiClient
{
    public function get(string $path, array $query = []): Response
    {
        return $this->request()->get($this->url($path), $query);
    }

    public function post(string $path, array $data = []): Response
    {
        return $this->request()->post($this->url($path), $data);
    }

    public function put(string $path, array $data = []): Response
    {
        return $this->request()->put($this->url($path), $data);
    }

    public function patch(string $path, array $data = []): Response
    {
        return $this->request()->patch($this->url($path), $data);
    }

    public function delete(string $path): Response
    {
        return $this->request()->delete($this->url($path));
    }

    private function request()
    {
        return Http::acceptJson()->withToken(session('admin_token'));
    }

    private function url(string $path): string
    {
        return rtrim(config('services.blog.url'), '/').'/'.ltrim($path, '/');
    }
}
