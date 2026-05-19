<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class PostSearchIndexer
{
    /**
     * @throws RequestException
     */
    public function index(Post $post): void
    {
        $post->loadMissing(['category', 'tags']);

        Http::timeout((int) config('services.elasticsearch.timeout'))
            ->put($this->documentUrl($post->id), [
                'id' => $post->id,
                'author_id' => $post->author_id,
                'category_id' => $post->category_id,
                'category' => $post->category?->only(['id', 'name', 'slug']),
                'tag_ids' => $post->tags->pluck('id')->all(),
                'tags' => $post->tags->map->only(['id', 'name', 'slug'])->values()->all(),
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->toISOString(),
                'created_at' => $post->created_at?->toISOString(),
                'updated_at' => $post->updated_at?->toISOString(),
            ])
            ->throw();
    }

    /**
     * @throws RequestException
     */
    public function delete(int $postId): void
    {
        $response = Http::timeout((int) config('services.elasticsearch.timeout'))
            ->delete($this->documentUrl($postId));

        if ($response->status() !== 404) {
            $response->throw();
        }
    }

    private function documentUrl(int $postId): string
    {
        $baseUrl = rtrim((string) config('services.elasticsearch.url'), '/');
        $index = trim((string) config('services.elasticsearch.posts_index'), '/');

        return "{$baseUrl}/{$index}/_doc/{$postId}";
    }
}
