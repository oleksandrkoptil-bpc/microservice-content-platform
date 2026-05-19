<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BlogController::class, 'home'])->name('home');
Route::get('/posts', [BlogController::class, 'posts'])->name('posts.index');
Route::get('/posts/{post}', [BlogController::class, 'show'])->name('posts.show');
Route::get('/categories/{category}', [BlogController::class, 'category'])->name('categories.show');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/write', [PostController::class, 'create'])->name('posts.create');
Route::post('/write', [PostController::class, 'store'])->name('posts.store');
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
