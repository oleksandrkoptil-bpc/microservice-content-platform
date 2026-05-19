<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BlogApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(BlogApiClient $blog): View
    {
        return view('admin.tags.index', [
            'tags' => $blog->get('/tags')->json('data', []),
        ]);
    }

    public function create(): View
    {
        return view('admin.tags.form', ['tag' => null]);
    }

    public function store(Request $request, BlogApiClient $blog): RedirectResponse
    {
        $response = $blog->post('/tags', $this->payload($request));

        return $this->redirectAfterWrite($response, 'tags.index');
    }

    public function edit(BlogApiClient $blog, int $tag): View
    {
        return view('admin.tags.form', [
            'tag' => $blog->get("/tags/{$tag}")->json('data'),
        ]);
    }

    public function update(Request $request, BlogApiClient $blog, int $tag): RedirectResponse
    {
        $response = $blog->put("/tags/{$tag}", $this->payload($request));

        return $this->redirectAfterWrite($response, 'tags.index');
    }

    public function destroy(BlogApiClient $blog, int $tag): RedirectResponse
    {
        $blog->delete("/tags/{$tag}");

        return redirect()->route('tags.index');
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
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
