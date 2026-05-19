<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TagController;
use App\Http\Middleware\AuthenticateWithAuthService;
use App\Http\Middleware\RequireRole;
use Illuminate\Support\Facades\Route;

Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('tags', TagController::class)->only(['index', 'show']);
Route::apiResource('posts', PostController::class)->only(['index', 'show']);

Route::get('posts/{post}/comments', [CommentController::class, 'index']);

Route::middleware(AuthenticateWithAuthService::class)->group(function (): void {
    Route::post('posts', [PostController::class, 'store']);
    Route::post('posts/{post}/comments', [CommentController::class, 'store']);

    Route::middleware(RequireRole::class.':admin')->group(function (): void {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('tags', TagController::class)->except(['index', 'show']);

        Route::patch('posts/{post}', [PostController::class, 'update']);
        Route::put('posts/{post}', [PostController::class, 'update']);
        Route::delete('posts/{post}', [PostController::class, 'destroy']);
        Route::patch('posts/{post}/publish', [PostController::class, 'publish']);
        Route::patch('posts/{post}/archive', [PostController::class, 'archive']);

        Route::patch('comments/{comment}', [CommentController::class, 'update']);
        Route::put('comments/{comment}', [CommentController::class, 'update']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
        Route::get('comments', [CommentController::class, 'adminIndex']);
        Route::patch('comments/{comment}/approve', [CommentController::class, 'approve']);
        Route::patch('comments/{comment}/reject', [CommentController::class, 'reject']);
    });
});
