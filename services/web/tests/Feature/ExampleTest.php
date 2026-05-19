<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        Http::fake([
            'http://blog-nginx/posts*' => Http::response(['data' => []]),
            'http://blog-nginx/categories*' => Http::response(['data' => []]),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_login_stores_user_session(): void
    {
        Http::fake([
            'http://auth-nginx/login' => Http::response([
                'access_token' => 'test-token',
                'user' => [
                    'id' => 1,
                    'name' => 'Author',
                    'email' => 'author@example.com',
                    'role' => 'author',
                ],
            ]),
        ]);

        $response = $this->post('/login', [
            'email' => 'author@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertSame('test-token', session('access_token'));
        $this->assertSame('Author', session('user.name'));
    }

    public function test_guest_is_redirected_from_write_page(): void
    {
        $this->get('/write')->assertRedirect('/login');
    }

    public function test_register_sends_password_confirmation_to_auth_service(): void
    {
        Http::fake([
            'http://auth-nginx/register' => Http::response([
                'access_token' => 'registered-token',
                'user' => [
                    'id' => 5,
                    'name' => 'New Author',
                    'email' => 'new-author@example.com',
                    'role' => 'author',
                ],
            ], 201),
        ]);

        $response = $this->post('/register', [
            'name' => 'New Author',
            'email' => 'new-author@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertSame('registered-token', session('access_token'));

        Http::assertSent(fn ($request) => $request->url() === 'http://auth-nginx/register'
            && $request['password_confirmation'] === 'password123');
    }
}
