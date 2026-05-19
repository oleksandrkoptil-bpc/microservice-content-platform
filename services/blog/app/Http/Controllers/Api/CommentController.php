<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    public function index(Post $post): AnonymousResourceCollection
    {
        return CommentResource::collection(
            $post->comments()
                ->where('status', CommentStatus::Approved)
                ->latest()
                ->paginate()
        );
    }

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $comments = Comment::query()
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('post_id'), fn ($query, string $postId) => $query->where('post_id', $postId))
            ->when($request->query('author_id'), fn ($query, string $authorId) => $query->where('author_id', $authorId))
            ->latest()
            ->paginate();

        return CommentResource::collection($comments);
    }

    public function store(Request $request, Post $post): CommentResource
    {
        $data = $request->validate([
            'content' => ['required', 'string'],
            'status' => ['sometimes', Rule::enum(CommentStatus::class)],
        ]);

        $authUser = $request->attributes->get('auth_user');
        $data['author_id'] = $authUser['id'];

        if (($authUser['role'] ?? null) !== 'admin') {
            $data['status'] = CommentStatus::Pending;
        }

        $comment = $post->comments()->create($data);

        return new CommentResource($comment);
    }

    public function update(Request $request, Comment $comment): CommentResource
    {
        $comment->update($request->validate([
            'content' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', Rule::enum(CommentStatus::class)],
        ]));

        return new CommentResource($comment);
    }

    public function destroy(Comment $comment): Response
    {
        $comment->delete();

        return response()->noContent();
    }

    public function approve(Comment $comment): CommentResource
    {
        $comment->update(['status' => CommentStatus::Approved]);

        return new CommentResource($comment);
    }

    public function reject(Comment $comment): CommentResource
    {
        $comment->update(['status' => CommentStatus::Rejected]);

        return new CommentResource($comment);
    }
}
