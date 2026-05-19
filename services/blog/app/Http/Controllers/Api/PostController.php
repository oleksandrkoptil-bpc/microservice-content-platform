<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DomainEventPublisher;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\AuthServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PostController extends Controller
{
    public function __construct(
        private readonly AuthServiceClient $auth,
        private readonly DomainEventPublisher $events,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $authUser = $request->bearerToken() ? $this->auth->userFromToken($request->bearerToken()) : null;
        $isAdmin = ($authUser['role'] ?? null) === 'admin';
        $status = $isAdmin ? $request->query('status') : PostStatus::Published->value;

        $posts = Post::query()
            ->with(['category', 'tags'])
            ->when($status, fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('category_id'), fn ($query, string $categoryId) => $query->where('category_id', $categoryId))
            ->when($isAdmin && $request->query('author_id'), fn ($query) => $query->where('author_id', $request->query('author_id')))
            ->latest()
            ->paginate();

        return PostResource::collection($posts);
    }

    public function store(Request $request): PostResource
    {
        $data = $request->validate($this->rules());
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        $authUser = $request->attributes->get('auth_user');
        $data['author_id'] = $authUser['id'];

        if (($authUser['role'] ?? null) !== 'admin') {
            $data['status'] = PostStatus::Draft;
            $data['published_at'] = null;
        }

        $post = Post::query()->create($data);
        $post->tags()->sync($tagIds);
        $post->load(['category', 'tags']);

        $this->events->publish('blog.post.created.v1', [
            'post_id' => $post->id,
            'author_id' => $post->author_id,
            'category_id' => $post->category_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status->value,
            'tag_ids' => $post->tags->pluck('id')->all(),
        ]);

        return new PostResource($post);
    }

    public function show(Request $request, Post $post): PostResource
    {
        if ($post->status !== PostStatus::Published) {
            $authUser = $request->bearerToken() ? $this->auth->userFromToken($request->bearerToken()) : null;

            abort_if(($authUser['role'] ?? null) !== 'admin', HttpResponse::HTTP_NOT_FOUND);
        }

        return new PostResource($post->load([
            'category',
            'tags',
            'comments' => fn ($query) => $query->where('status', 'approved')->latest(),
        ]));
    }

    public function update(Request $request, Post $post): PostResource
    {
        $data = $request->validate($this->rules($post));
        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);

        $post->update($data);

        if (is_array($tagIds)) {
            $post->tags()->sync($tagIds);
        }

        return new PostResource($post->load(['category', 'tags']));
    }

    public function destroy(Post $post): Response
    {
        $post->delete();

        return response()->noContent();
    }

    public function publish(Post $post): PostResource
    {
        $post->update([
            'status' => PostStatus::Published,
            'published_at' => $post->published_at ?? now(),
        ]);
        $post->load(['category', 'tags']);

        $this->events->publish('blog.post.published.v1', [
            'post_id' => $post->id,
            'author_id' => $post->author_id,
            'category_id' => $post->category_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'published_at' => $post->published_at?->toISOString(),
            'tag_ids' => $post->tags->pluck('id')->all(),
        ]);

        return new PostResource($post);
    }

    public function archive(Post $post): PostResource
    {
        $post->update(['status' => PostStatus::Archived]);
        $post->load(['category', 'tags']);

        $this->events->publish('blog.post.archived.v1', [
            'post_id' => $post->id,
            'author_id' => $post->author_id,
            'category_id' => $post->category_id,
            'title' => $post->title,
            'slug' => $post->slug,
        ]);

        return new PostResource($post);
    }

    private function rules(?Post $post = null): array
    {
        $required = $post ? 'sometimes' : 'required';

        return [
            'category_id' => [$required, 'integer', 'exists:categories,id'],
            'title' => [$required, 'string', 'max:255'],
            'slug' => [$required, 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post)],
            'excerpt' => ['nullable', 'string'],
            'content' => [$required, 'string'],
            'status' => ['sometimes', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }
}
