@extends('layouts.app')

@section('title', $post['title'] ?? 'Стаття')

@section('content')
    <section class="section">
        <div class="wrap two-col">
            <article class="article">
                <div class="meta">
                    @if(!empty($post['category']['name']))
                        <a class="pill" href="{{ route('categories.show', $post['category']['id']) }}">{{ $post['category']['name'] }}</a>
                    @endif
                    <span>Автор #{{ $post['author_id'] }}</span>
                </div>
                <h1>{{ $post['title'] }}</h1>
                @if(!empty($post['excerpt']))
                    <p class="muted" style="font-size: 20px;">{{ $post['excerpt'] }}</p>
                @endif
                <div class="article-body">{{ $post['content'] }}</div>

                <section class="section" style="padding-bottom: 0;">
                    <h2>Коментарі</h2>
                    <div style="display: grid; gap: 12px; margin-top: 18px;">
                        @forelse($post['comments'] ?? [] as $comment)
                            <div class="card">
                                <div class="meta">Автор #{{ $comment['author_id'] }}</div>
                                <div>{{ $comment['content'] }}</div>
                            </div>
                        @empty
                            <p class="muted">Поки немає коментарів.</p>
                        @endforelse
                    </div>
                </section>
            </article>

            <aside class="card">
                @if($user)
                    <h2>Додати коментар</h2>
                    <form class="form" method="POST" action="{{ route('comments.store', $post['id']) }}" style="margin-top: 16px;">
                        @csrf
                        <label>
                            Текст
                            <textarea name="content" required>{{ old('content') }}</textarea>
                        </label>
                        <button class="button" type="submit">Надіслати</button>
                    </form>
                @else
                    <h2>Обговорення</h2>
                    <p class="muted">Щоб залишити коментар, потрібно увійти або зареєструватись.</p>
                    <a class="button" href="{{ route('login') }}">Увійти</a>
                @endif
            </aside>
        </div>
    </section>
@endsection
