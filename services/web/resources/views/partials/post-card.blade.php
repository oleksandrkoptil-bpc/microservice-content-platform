<article class="card post-card">
    <div class="meta">
        @if(!empty($post['category']['name']))
            <a class="pill" href="{{ route('categories.show', $post['category']['id']) }}">{{ $post['category']['name'] }}</a>
        @endif
        @if(!empty($post['published_at']))
            <span>{{ \Illuminate\Support\Carbon::parse($post['published_at'])->format('d.m.Y') }}</span>
        @endif
    </div>
    <h3><a href="{{ route('posts.show', $post['id']) }}">{{ $post['title'] }}</a></h3>
    <p>{{ $post['excerpt'] ?: \Illuminate\Support\Str::limit(strip_tags($post['content'] ?? ''), 130) }}</p>
    <a class="button secondary" href="{{ route('posts.show', $post['id']) }}">Читати</a>
</article>
