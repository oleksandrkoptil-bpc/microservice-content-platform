<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_content_flow(): void
    {
        Http::fake(function ($request) {
            $authorization = $request->header('Authorization')[0] ?? '';

            if ($authorization === 'Bearer admin-token') {
                return Http::response([
                    'user' => [
                        'id' => 1,
                        'name' => 'Admin User',
                        'email' => 'admin@example.com',
                        'role' => 'admin',
                    ],
                ]);
            }

            return Http::response([
                'user' => [
                    'id' => 10,
                    'name' => 'Author User',
                    'email' => 'author@example.com',
                    'role' => 'author',
                ],
            ]);
        });

        $categoryId = $this->withToken('admin-token')->postJson('/categories', [
            'name' => 'Tech',
            'slug' => 'tech',
            'description' => 'Tech posts',
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'tech')
            ->json('data.id');

        $tagId = $this->withToken('admin-token')->postJson('/tags', [
            'name' => 'Laravel',
            'slug' => 'laravel',
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'laravel')
            ->json('data.id');

        $postResponse = $this->withToken('author-token')->postJson('/posts', [
            'category_id' => $categoryId,
            'title' => 'First post',
            'slug' => 'first-post',
            'excerpt' => 'Short preview',
            'content' => 'Long post content',
            'status' => 'published',
            'tag_ids' => [$tagId],
        ])
            ->assertCreated()
            ->assertJsonPath('data.author_id', 10)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.tags.0.slug', 'laravel');

        $postId = $postResponse->json('data.id');

        $this->withToken('admin-token')
            ->patchJson("/posts/{$postId}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->withToken('author-token')->postJson("/posts/{$postId}/comments", [
            'content' => 'Nice post',
            'status' => 'approved',
        ])
            ->assertCreated()
            ->assertJsonPath('data.author_id', 10)
            ->assertJsonPath('data.status', 'pending');

        $this->getJson("/posts/{$postId}")
            ->assertOk()
            ->assertJsonMissing(['content' => 'Nice post']);

        $commentId = $this->withToken('admin-token')
            ->getJson('/comments?status=pending')
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Nice post')
            ->json('data.0.id');

        $this->withToken('admin-token')
            ->patchJson("/comments/{$commentId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->getJson("/posts/{$postId}")
            ->assertOk()
            ->assertJsonPath('data.comments.0.content', 'Nice post');
    }

    public function test_public_cannot_list_or_show_unpublished_posts(): void
    {
        $category = Category::query()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $draft = Post::query()->create([
            'author_id' => 10,
            'category_id' => $category->id,
            'title' => 'Draft post',
            'slug' => 'draft-post',
            'content' => 'Draft content',
            'status' => 'draft',
        ]);

        $published = Post::query()->create([
            'author_id' => 10,
            'category_id' => $category->id,
            'title' => 'Published post',
            'slug' => 'published-post',
            'content' => 'Published content',
            'status' => 'published',
        ]);

        $this->getJson('/posts?status=draft')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Draft post'])
            ->assertJsonPath('data.0.id', $published->id);

        $this->getJson("/posts/{$draft->id}")
            ->assertNotFound();
    }

    public function test_admin_can_list_and_show_unpublished_posts(): void
    {
        Http::fake([
            'auth-nginx/me' => Http::response([
                'user' => [
                    'id' => 1,
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                ],
            ]),
        ]);

        $category = Category::query()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $draft = Post::query()->create([
            'author_id' => 10,
            'category_id' => $category->id,
            'title' => 'Draft post',
            'slug' => 'draft-post',
            'content' => 'Draft content',
            'status' => 'draft',
        ]);

        $this->withToken('admin-token')
            ->getJson('/posts?status=draft')
            ->assertOk()
            ->assertJsonPath('data.0.id', $draft->id);

        $this->withToken('admin-token')
            ->getJson("/posts/{$draft->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $draft->id);
    }

    public function test_category_slug_must_be_unique(): void
    {
        Http::fake([
            'auth-nginx/me' => Http::response([
                'user' => [
                    'id' => 1,
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                ],
            ]),
        ]);

        $this->withToken('admin-token')->postJson('/categories', [
            'name' => 'Tech',
            'slug' => 'tech',
        ])->assertCreated();

        $this->withToken('admin-token')->postJson('/categories', [
            'name' => 'Another Tech',
            'slug' => 'tech',
        ])->assertUnprocessable();
    }

    public function test_write_routes_require_authentication(): void
    {
        $this->postJson('/categories', [
            'name' => 'Tech',
            'slug' => 'tech',
        ])->assertUnauthorized();
    }

    public function test_author_cannot_manage_categories(): void
    {
        Http::fake([
            'auth-nginx/me' => Http::response([
                'user' => [
                    'id' => 10,
                    'name' => 'Author User',
                    'email' => 'author@example.com',
                    'role' => 'author',
                ],
            ]),
        ]);

        $this->withToken('author-token')->postJson('/categories', [
            'name' => 'Tech',
            'slug' => 'tech',
        ])->assertForbidden();
    }

    public function test_admin_can_publish_archive_approve_and_reject(): void
    {
        Http::fake([
            'auth-nginx/me' => Http::response([
                'user' => [
                    'id' => 1,
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                ],
            ]),
        ]);

        $category = Category::query()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Post::query()->create([
            'author_id' => 10,
            'category_id' => $category->id,
            'title' => 'Draft post',
            'slug' => 'draft-post',
            'content' => 'Draft content',
        ]);

        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'author_id' => 20,
            'content' => 'Needs moderation',
        ]);

        $this->withToken('admin-token')
            ->patchJson("/posts/{$post->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->assertNotNull($post->fresh()->published_at);

        $this->withToken('admin-token')
            ->patchJson("/posts/{$post->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');

        $this->withToken('admin-token')
            ->patchJson("/comments/{$comment->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->withToken('admin-token')
            ->patchJson("/comments/{$comment->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_public_comment_list_only_shows_approved_comments(): void
    {
        $category = Category::query()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Post::query()->create([
            'author_id' => 10,
            'category_id' => $category->id,
            'title' => 'Post',
            'slug' => 'post',
            'content' => 'Content',
        ]);

        Comment::query()->create([
            'post_id' => $post->id,
            'author_id' => 20,
            'content' => 'Pending comment',
            'status' => 'pending',
        ]);

        Comment::query()->create([
            'post_id' => $post->id,
            'author_id' => 21,
            'content' => 'Approved comment',
            'status' => 'approved',
        ]);

        $this->getJson("/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Approved comment')
            ->assertJsonMissing(['content' => 'Pending comment']);
    }
}
