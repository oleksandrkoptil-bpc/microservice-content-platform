@extends('layouts.app')

@section('title', 'Статті')

@section('content')
    <section class="section">
        <div class="wrap two-col">
            <div>
                <div class="section-head">
                    <h1>Статті</h1>
                    <a class="button" href="{{ route('posts.create') }}">Написати</a>
                </div>
                <div class="grid" style="grid-template-columns: 1fr;">
                    @forelse($posts as $post)
                        @include('partials.post-card', ['post' => $post])
                    @empty
                        <div class="card">Опублікованих статей ще немає.</div>
                    @endforelse
                </div>
            </div>
            <aside class="card">
                <h2>Категорії</h2>
                <div class="category-list" style="margin-top: 16px;">
                    @foreach($categories as $category)
                        <a class="pill" href="{{ route('categories.show', $category['id']) }}">{{ $category['name'] }}</a>
                    @endforeach
                </div>
            </aside>
        </div>
    </section>
@endsection
