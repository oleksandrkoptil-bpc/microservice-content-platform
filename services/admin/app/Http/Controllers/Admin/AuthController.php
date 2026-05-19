<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request, AuthApiClient $auth): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $response = $auth->login($credentials['email'], $credentials['password']);

        if (! $response->ok()) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $user = $response->json('user');

        if (($user['role'] ?? null) !== 'admin') {
            return back()->withErrors(['email' => 'Admin access required.'])->onlyInput('email');
        }

        session([
            'admin_token' => $response->json('access_token'),
            'admin_user' => $user,
        ]);

        return redirect()->to($this->adminUrl('/'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_token', 'admin_user']);
        $request->session()->regenerateToken();

        return redirect()->to($this->adminUrl('/login'));
    }

    private function adminUrl(string $path): string
    {
        return rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
