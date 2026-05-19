<?php

namespace App\Http\Controllers\Api;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $authUser = $request->bearerToken() ? $this->resolveAuthUser($request) : null;
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

        return new PostResource($post->load(['category', 'tags']));
    }

    public function show(Request $request, Post $post): PostResource
    {
        if ($post->status !== PostStatus::Published) {
            $authUser = $request->bearerToken() ? $this->resolveAuthUser($request) : null;

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

        return new PostResource($post->load(['category', 'tags']));
    }

    public function archive(Post $post): PostResource
    {
        $post->update(['status' => PostStatus::Archived]);

        return new PostResource($post->load(['category', 'tags']));
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

    private function resolveAuthUser(Request $request): array
    {
        try {
            $response = Http::withToken($request->bearerToken())
                ->acceptJson()
                ->timeout(5)
                ->get(rtrim(config('services.auth.url'), '/').'/me');
        } catch (ConnectionException) {
            abort(response()->json(['message' => 'Auth service unavailable.'], HttpResponse::HTTP_SERVICE_UNAVAILABLE));
        }

        if (! $response->ok()) {
            abort(response()->json(['message' => 'Unauthenticated.'], HttpResponse::HTTP_UNAUTHORIZED));
        }

        $user = $response->json('user');

        if (! is_array($user) || ! isset($user['id'])) {
            abort(response()->json(['message' => 'Invalid auth service response.'], HttpResponse::HTTP_BAD_GATEWAY));
        }

        return $user;
    }
}
