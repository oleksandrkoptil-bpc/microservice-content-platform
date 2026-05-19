<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_token') || session('admin_user.role') !== 'admin') {
            return redirect()->to(rtrim(config('app.url'), '/').'/login');
        }

        return $next($request);
    }
}
