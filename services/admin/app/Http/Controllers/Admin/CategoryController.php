<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BlogApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(BlogApiClient $blog): View
    {
        return view('admin.categories.index', [
            'categories' => $blog->get('/categories')->json('data', []),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => null]);
    }

    public function store(Request $request, BlogApiClient $blog): RedirectResponse
    {
        $response = $blog->post('/categories', $this->payload($request));

        return $this->redirectAfterWrite($response, 'categories.index');
    }

    public function edit(BlogApiClient $blog, int $category): View
    {
        return view('admin.categories.form', [
            'category' => $blog->get("/categories/{$category}")->json('data'),
        ]);
    }

    public function update(Request $request, BlogApiClient $blog, int $category): RedirectResponse
    {
        $response = $blog->put("/categories/{$category}", $this->payload($request));

        return $this->redirectAfterWrite($response, 'categories.index');
    }

    public function destroy(BlogApiClient $blog, int $category): RedirectResponse
    {
        $blog->delete("/categories/{$category}");

        return redirect()->route('categories.index');
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
