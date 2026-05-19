@extends('layouts.app')

@section('title', $category['name'] ?? 'Категорія')

@section('content')
    <section class="section">
        <div class="wrap">
            <span class="eyebrow" style="color: var(--warm);">Категорія</span>
            <h1>{{ $category['name'] }}</h1>
            @if(!empty($category['description']))
                <p class="muted">{{ $category['description'] }}</p>
            @endif
            <div class="grid" style="margin-top: 24px;">
                @forelse($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @empty
                    <div class="card">У цій категорії ще немає опублікованих статей.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
