<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    public function store(Request $request, int $post)
    {
        if (! session('access_token')) {
            return redirect()->route('login')->withErrors(['email' => 'Увійдіть, щоб залишити коментар.']);
        }

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $response = $this->api->postBlog("/posts/{$post}/comments", $data, session('access_token'));

        if ($response->failed()) {
            return back()->withInput()->withErrors([
                'content' => 'Не вдалося додати коментар.',
            ]);
        }

        return redirect()->route('posts.show', $post)
            ->with('status', 'Коментар надіслано на модерацію.');
    }
}
