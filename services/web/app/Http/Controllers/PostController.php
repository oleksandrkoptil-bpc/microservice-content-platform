<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    public function create()
    {
        if (! session('access_token')) {
            return redirect()->route('login')->withErrors(['email' => 'Увійдіть, щоб написати статтю.']);
        }

        return view('posts.create', [
            'categories' => $this->api->getBlog('/categories')->json('data', []),
            'tags' => $this->api->getBlog('/tags')->json('data', []),
        ]);
    }

    public function store(Request $request)
    {
        if (! session('access_token')) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ]);

        $payload = [
            ...$data,
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'status' => 'draft',
            'tag_ids' => array_map('intval', $data['tag_ids'] ?? []),
        ];

        $response = $this->api->postBlog('/posts', $payload, session('access_token'));

        if ($response->failed()) {
            return back()->withInput()->withErrors([
                'title' => 'Не вдалося зберегти статтю. Перевірте поля і спробуйте ще раз.',
            ]);
        }

        return redirect()->route('posts.show', $response->json('data.id'))
            ->with('status', 'Статтю створено як чернетку. Публікація буде після модерації.');
    }
}
