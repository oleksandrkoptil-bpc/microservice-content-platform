@extends('layouts.app')

@section('title', 'Blog')

@section('content')
    <section class="hero">
        <div class="wrap hero-content">
            <span class="eyebrow">Нові думки, практичні історії, авторські статті</span>
            @if($heroPost)
                <h1>{{ $heroPost['title'] }}</h1>
                <p>{{ $heroPost['excerpt'] ?: \Illuminate\Support\Str::limit(strip_tags($heroPost['content'] ?? ''), 180) }}</p>
                <a class="button warm" href="{{ route('posts.show', $heroPost['id']) }}">Відкрити статтю</a>
            @else
                <h1>Місце для першої статті твого блогу</h1>
                <p>Коли адмін опублікує перший пост, він автоматично стане головним матеріалом на цій сторінці.</p>
                <a class="button warm" href="{{ route('posts.create') }}">Написати статтю</a>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <h2>Категорії</h2>
                <a href="{{ route('posts.index') }}">Усі статті</a>
            </div>
            <div class="category-list">
                @forelse($categories as $category)
                    <a class="pill" href="{{ route('categories.show', $category['id']) }}">{{ $category['name'] }}</a>
                @empty
                    <span class="muted">Категорії ще не створені.</span>
                @endforelse
            </div>
        </div>
    </section>

    @if(count($recommendedPosts))
        <section class="section" style="background: var(--soft);">
            <div class="wrap">
                <div class="section-head">
                    <h2>Рекомендації</h2>
                </div>
                <div class="grid">
                    @foreach($recommendedPosts as $post)
                        @include('partials.post-card', ['post' => $post])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <h2>Останні статті</h2>
            </div>
            <div class="grid">
                @forelse($latestPosts as $post)
                    @include('partials.post-card', ['post' => $post])
                @empty
                    <div class="card">
                        <h3>Поки що порожньо</h3>
                        <p class="muted">Створи категорії в адмінці, напиши статтю і опублікуй її після модерації.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
