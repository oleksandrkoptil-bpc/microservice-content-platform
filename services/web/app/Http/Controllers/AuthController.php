<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $response = $this->api->postAuth('/login', $credentials);

        if ($response->failed()) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Не вдалося увійти з цими даними.',
            ]);
        }

        $this->storeSession($response->json());

        return redirect()->route('home')->with('status', 'Ви успішно увійшли.');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $response = $this->api->postAuth('/register', $data);

        if ($response->failed()) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors($this->responseErrors($response->json()));
        }

        $this->storeSession($response->json());

        return redirect()->route('home')->with('status', 'Акаунт створено. Можна писати статтю.');
    }

    public function logout()
    {
        if (session('access_token')) {
            $this->api->postAuth('/logout', [], session('access_token'));
        }

        session()->forget(['access_token', 'user']);

        return redirect()->route('home');
    }

    private function storeSession(array $payload): void
    {
        session([
            'access_token' => $payload['access_token'] ?? null,
            'user' => $payload['user'] ?? null,
        ]);
    }

    private function responseErrors(?array $payload): array
    {
        $errors = $payload['errors'] ?? [];

        if (is_array($errors) && count($errors) > 0) {
            return collect($errors)
                ->map(fn ($messages) => is_array($messages) ? $messages[0] : $messages)
                ->all();
        }

        return [
            'email' => $payload['message'] ?? 'Не вдалося виконати запит. Перевірте дані і спробуйте ще раз.',
        ];
    }
}
