<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BlogApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request, BlogApiClient $blog): View
    {
        $status = $request->query('status');

        return view('admin.posts.index', [
            'posts' => $blog->get('/posts', array_filter(['status' => $status]))->json('data', []),
            'status' => $status,
        ]);
    }

    public function create(BlogApiClient $blog): View
    {
        return view('admin.posts.form', [
            'post' => null,
            'categories' => $blog->get('/categories')->json('data', []),
            'tags' => $blog->get('/tags')->json('data', []),
        ]);
    }

    public function store(Request $request, BlogApiClient $blog): RedirectResponse
    {
        $response = $blog->post('/posts', $this->payload($request));

        return $this->redirectAfterWrite($response, 'posts.index');
    }

    public function edit(BlogApiClient $blog, int $post): View
    {
        return view('admin.posts.form', [
            'post' => $blog->get("/posts/{$post}")->json('data'),
            'categories' => $blog->get('/categories')->json('data', []),
            'tags' => $blog->get('/tags')->json('data', []),
        ]);
    }

    public function update(Request $request, BlogApiClient $blog, int $post): RedirectResponse
    {
        $response = $blog->put("/posts/{$post}", $this->payload($request));

        return $this->redirectAfterWrite($response, 'posts.index');
    }

    public function destroy(BlogApiClient $blog, int $post): RedirectResponse
    {
        $blog->delete("/posts/{$post}");

        return redirect()->route('posts.index', request()->only('status'));
    }

    public function publish(BlogApiClient $blog, int $post): RedirectResponse
    {
        $blog->patch("/posts/{$post}/publish");

        return redirect()->route('posts.index', request()->only('status'));
    }

    public function archive(BlogApiClient $blog, int $post): RedirectResponse
    {
        $blog->patch("/posts/{$post}/archive");

        return redirect()->route('posts.index');
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ]);
    }

    private function redirectAfterWrite($response, string $route): RedirectResponse
    {
        if ($response->failed()) {
            return back()->withErrors($response->json('errors') ?? ['error' => 'Request failed.'])->withInput();
        }

        return redirect()->route($route);
    }
}
