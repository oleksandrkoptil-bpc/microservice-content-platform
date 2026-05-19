<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\PostSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostSearchIndexerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_indexes_published_post_in_elasticsearch(): void
    {
        Http::fake([
            'http://elasticsearch:9200/blog_posts/_doc/*' => Http::response([], 200),
        ]);

        $category = Category::query()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);
        $tag = Tag::query()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);
        $post = Post::query()->create([
            'author_id' => 10,
            'category_id' => $category->id,
            'title' => 'Searchable post',
            'slug' => 'searchable-post',
            'excerpt' => 'Short preview',
            'content' => 'Long content',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post->tags()->sync([$tag->id]);

        app(PostSearchIndexer::class)->index($post);

        Http::assertSent(function ($request) use ($post, $category, $tag) {
            return $request->method() === 'PUT'
                && $request->url() === "http://elasticsearch:9200/blog_posts/_doc/{$post->id}"
                && $request['id'] === $post->id
                && $request['category']['slug'] === $category->slug
                && $request['tags'][0]['slug'] === $tag->slug
                && $request['status'] === 'published';
        });
    }

    public function test_it_deletes_post_from_elasticsearch(): void
    {
        Http::fake([
            'http://elasticsearch:9200/blog_posts/_doc/55' => Http::response([], 200),
        ]);

        app(PostSearchIndexer::class)->delete(55);

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'http://elasticsearch:9200/blog_posts/_doc/55';
        });
    }

    public function test_delete_ignores_missing_elasticsearch_document(): void
    {
        Http::fake([
            'http://elasticsearch:9200/blog_posts/_doc/55' => Http::response([], 404),
        ]);

        app(PostSearchIndexer::class)->delete(55);

        $this->assertTrue(true);
    }
}
