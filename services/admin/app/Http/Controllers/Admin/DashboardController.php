<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BlogApiClient;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(BlogApiClient $blog): View
    {
        return view('admin.dashboard', [
            'posts' => $blog->get('/posts')->json('meta.total', 0),
            'categories' => $blog->get('/categories')->json('meta.total', 0),
            'tags' => $blog->get('/tags')->json('meta.total', 0),
        ]);
    }
}
