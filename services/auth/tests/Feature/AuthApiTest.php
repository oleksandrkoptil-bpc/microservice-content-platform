<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Test Author',
            'email' => 'author@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'author@example.com')
            ->assertJsonPath('user.role', 'author')
            ->assertJsonStructure(['access_token']);

        $this->assertDatabaseHas('users', [
            'email' => 'author@example.com',
            'role' => 'author',
        ]);
        $this->assertDatabaseCount('api_tokens', 1);
    }

    public function test_user_can_login_and_fetch_profile(): void
    {
        User::query()->create([
            'name' => 'Existing Author',
            'email' => 'existing@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginResponse = $this->postJson('/login', [
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->assertOk()->json('access_token');

        $this->withToken($token)
            ->getJson('/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'existing@example.com')
            ->assertJsonPath('user.role', 'author');
    }

    public function test_user_can_logout(): void
    {
        $registerResponse = $this->postJson('/register', [
            'name' => 'Logout Author',
            'email' => 'logout@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $token = $registerResponse->json('access_token');

        $this->withToken($token)
            ->postJson('/logout')
            ->assertNoContent();

        $this->withToken($token)
            ->getJson('/me')
            ->assertUnauthorized();
    }

    public function test_public_registration_cannot_create_admin_user(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Sneaky Admin',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.role', 'author');

        $this->assertDatabaseHas('users', [
            'email' => 'sneaky@example.com',
            'role' => 'author',
        ]);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/login', [
                'email' => 'missing@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }
}
