<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_see_dashboard(): void
    {
        Http::fake([
            'auth-nginx/login' => Http::response([
                'token_type' => 'Bearer',
                'access_token' => 'admin-token',
                'user' => [
                    'id' => 1,
                    'name' => 'Admin',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                ],
            ]),
            'blog-nginx/posts' => Http::response(['data' => [], 'meta' => ['total' => 2]]),
            'blog-nginx/categories' => Http::response(['data' => [], 'meta' => ['total' => 3]]),
            'blog-nginx/tags' => Http::response(['data' => [], 'meta' => ['total' => 4]]),
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertRedirect(rtrim(config('app.url'), '/').'/');

        $this->get('/')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('href="'.rtrim(config('app.url'), '/').'/"', false)
            ->assertSee('2')
            ->assertSee('3')
            ->assertSee('4');
    }

    public function test_non_admin_cannot_login(): void
    {
        Http::fake([
            'auth-nginx/login' => Http::response([
                'access_token' => 'author-token',
                'user' => [
                    'id' => 2,
                    'name' => 'Author',
                    'email' => 'author@example.com',
                    'role' => 'author',
                ],
            ]),
        ]);

        $this->post('/login', [
            'email' => 'author@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_posts_page_uses_blog_api(): void
    {
        Http::fake([
            'blog-nginx/posts' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'title' => 'First post',
                        'slug' => 'first-post',
                        'status' => 'draft',
                        'author_id' => 10,
                        'category' => ['name' => 'Tech'],
                    ],
                ],
            ]),
        ]);

        $this->withSession([
            'admin_token' => 'admin-token',
            'admin_user' => ['role' => 'admin'],
        ])->get('/posts')
            ->assertOk()
            ->assertSee('First post')
            ->assertSee('Tech');
    }

    public function test_posts_can_be_filtered_by_status(): void
    {
        Http::fake([
            'blog-nginx/posts?status=published' => Http::response(['data' => []]),
        ]);

        $this->withSession([
            'admin_token' => 'admin-token',
            'admin_user' => ['role' => 'admin'],
        ])->get('/posts?status=published')
            ->assertOk()
            ->assertSee('Published');

        Http::assertSent(fn ($request) => $request->url() === 'http://blog-nginx/posts?status=published');
    }

    public function test_comments_can_be_filtered_by_status(): void
    {
        Http::fake([
            'blog-nginx/comments?status=pending' => Http::response([
                'data' => [
                    ['id' => 1, 'post_id' => 2, 'content' => 'Pending comment', 'status' => 'pending', 'author_id' => 10],
                ],
            ]),
        ]);

        $this->withSession([
            'admin_token' => 'admin-token',
            'admin_user' => ['role' => 'admin'],
        ])->get('/comments?status=pending')
            ->assertOk()
            ->assertSee('Pending comment')
            ->assertSee('#2');

        Http::assertSent(fn ($request) => $request->url() === 'http://blog-nginx/comments?status=pending');
    }

    public function test_post_publish_action_calls_blog_api(): void
    {
        Http::fake([
            'blog-nginx/posts/1/publish' => Http::response(['data' => ['id' => 1, 'status' => 'published']]),
        ]);

        $this->withSession([
            'admin_token' => 'admin-token',
            'admin_user' => ['role' => 'admin'],
        ])->patch('/posts/1/publish')
            ->assertRedirect('/posts');

        Http::assertSent(fn ($request) => $request->url() === 'http://blog-nginx/posts/1/publish'
            && $request->method() === 'PATCH'
            && $request->hasHeader('Authorization', 'Bearer admin-token'));
    }

    public function test_comment_approve_action_calls_blog_api(): void
    {
        Http::fake([
            'blog-nginx/comments/1/approve' => Http::response(['data' => ['id' => 1, 'status' => 'approved']]),
        ]);

        $this->withSession([
            'admin_token' => 'admin-token',
            'admin_user' => ['role' => 'admin'],
        ])->patch('/comments/1/approve')
            ->assertRedirect('/comments');

        Http::assertSent(fn ($request) => $request->url() === 'http://blog-nginx/comments/1/approve'
            && $request->method() === 'PATCH'
            && $request->hasHeader('Authorization', 'Bearer admin-token'));
    }
}
