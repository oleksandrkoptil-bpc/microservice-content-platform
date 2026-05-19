<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class AuthServiceClient
{
    public function userFromToken(string $token): array
    {
        return Cache::remember(
            'auth_user:'.hash('sha256', $token),
            now()->addSeconds((int) config('services.auth.cache_ttl', 60)),
            fn () => $this->fetchUser($token)
        );
    }

    private function fetchUser(string $token): array
    {
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout((int) config('services.auth.timeout', 5))
                ->retry((int) config('services.auth.retries', 1), 100)
                ->get(rtrim(config('services.auth.url'), '/').'/me');
        } catch (ConnectionException) {
            abort(response()->json(['message' => 'Auth service unavailable.'], Response::HTTP_SERVICE_UNAVAILABLE));
        }

        if (! $response->ok()) {
            abort(response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED));
        }

        $user = $response->json('user');

        if (! is_array($user) || ! isset($user['id'])) {
            abort(response()->json(['message' => 'Invalid auth service response.'], Response::HTTP_BAD_GATEWAY));
        }

        return $user;
    }
}
