<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\ProcessedDomainEvent;
use App\Services\PostSearchIndexer;
use App\Services\RabbitMqBlogEventConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RabbitMqBlogEventConsumerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_processed_event_and_skips_duplicate_delivery(): void
    {
        $post = $this->createPublishedPost();
        $searchIndexer = Mockery::mock(PostSearchIndexer::class);
        $searchIndexer
            ->shouldReceive('index')
            ->once()
            ->with(Mockery::type(Post::class));

        $consumer = new RabbitMqBlogEventConsumer($searchIndexer);
        $payload = [
            'event_id' => 'event-123',
            'event_type' => 'blog.post.published.v1',
            'data' => [
                'post_id' => $post->id,
            ],
        ];

        $this->assertTrue($consumer->process($payload));
        $this->assertFalse($consumer->process($payload));

        $this->assertDatabaseHas('processed_domain_events', [
            'event_id' => 'event-123',
            'event_type' => 'blog.post.published.v1',
            'consumer' => 'blog.search.indexer',
        ]);
        $this->assertSame(1, ProcessedDomainEvent::query()->count());
    }

    public function test_it_records_archived_event_after_deleting_search_document(): void
    {
        $post = $this->createPublishedPost();
        $searchIndexer = Mockery::mock(PostSearchIndexer::class);
        $searchIndexer
            ->shouldReceive('delete')
            ->once()
            ->with($post->id);

        $consumer = new RabbitMqBlogEventConsumer($searchIndexer);

        $processed = $consumer->process([
            'event_id' => 'event-archived-123',
            'event_type' => 'blog.post.archived.v1',
            'data' => [
                'post_id' => $post->id,
            ],
        ]);

        $this->assertTrue($processed);
        $this->assertDatabaseHas('processed_domain_events', [
            'event_id' => 'event-archived-123',
            'event_type' => 'blog.post.archived.v1',
        ]);
    }

    public function test_it_rejects_event_without_event_id(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('event_id');

        $consumer = new RabbitMqBlogEventConsumer(Mockery::mock(PostSearchIndexer::class));

        $consumer->process([
            'event_type' => 'blog.post.published.v1',
            'data' => [
                'post_id' => 1,
            ],
        ]);
    }

    private function createPublishedPost(): Post
    {
        $category = Category::query()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        return Post::query()->create([
            'author_id' => 10,
            'category_id' => $category->id,
            'title' => 'Published post',
            'slug' => 'published-post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
