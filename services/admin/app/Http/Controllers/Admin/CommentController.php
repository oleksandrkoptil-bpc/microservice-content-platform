<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BlogApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function index(Request $request, BlogApiClient $blog): View
    {
        $status = $request->query('status');

        return view('admin.comments.index', [
            'comments' => $blog->get('/comments', array_filter(['status' => $status]))->json('data', []),
            'status' => $status,
        ]);
    }

    public function approve(BlogApiClient $blog, int $comment): RedirectResponse
    {
        $blog->patch("/comments/{$comment}/approve");

        return redirect()->route('comments.index');
    }

    public function reject(BlogApiClient $blog, int $comment): RedirectResponse
    {
        $blog->patch("/comments/{$comment}/reject");

        return redirect()->route('comments.index');
    }

    public function destroy(BlogApiClient $blog, int $comment): RedirectResponse
    {
        $blog->delete("/comments/{$comment}");

        return redirect()->route('comments.index');
    }
}
