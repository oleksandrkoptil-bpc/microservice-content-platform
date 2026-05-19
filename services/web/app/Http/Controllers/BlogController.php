<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    public function home()
    {
        $posts = $this->api->getBlog('/posts', ['status' => 'published'])->json('data', []);
        $categories = $this->api->getBlog('/categories')->json('data', []);

        return view('blog.home', [
            'heroPost' => $posts[0] ?? null,
            'recommendedPosts' => array_slice($posts, 1, 3),
            'latestPosts' => array_slice($posts, 0, 9),
            'categories' => $categories,
        ]);
    }

    public function posts(Request $request)
    {
        $posts = $this->api->getBlog('/posts', [
            'status' => 'published',
            'page' => $request->query('page', 1),
        ])->json('data', []);

        $categories = $this->api->getBlog('/categories')->json('data', []);

        return view('blog.posts', compact('posts', 'categories'));
    }

    public function show(int $post)
    {
        $response = $this->api->getBlog("/posts/{$post}");
        abort_if($response->failed(), 404);

        return view('blog.show', [
            'post' => $response->json('data'),
            'user' => session('user'),
        ]);
    }

    public function category(int $category)
    {
        $categoryResponse = $this->api->getBlog("/categories/{$category}");
        abort_if($categoryResponse->failed(), 404);

        $posts = $this->api->getBlog('/posts', [
            'status' => 'published',
            'category_id' => $category,
        ])->json('data', []);

        return view('blog.category', [
            'category' => $categoryResponse->json('data'),
            'posts' => $posts,
        ]);
    }
}
