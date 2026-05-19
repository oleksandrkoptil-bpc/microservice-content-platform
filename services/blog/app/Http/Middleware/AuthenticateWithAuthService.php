<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithAuthService
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->get(rtrim(config('services.auth.url'), '/').'/me');
        } catch (ConnectionException) {
            return response()->json(['message' => 'Auth service unavailable.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (! $response->ok()) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $response->json('user');

        if (! is_array($user) || ! isset($user['id'])) {
            return response()->json(['message' => 'Invalid auth service response.'], Response::HTTP_BAD_GATEWAY);
        }

        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}
