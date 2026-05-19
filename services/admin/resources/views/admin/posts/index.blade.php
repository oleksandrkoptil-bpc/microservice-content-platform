@extends('admin.layout', ['title' => 'Posts'])

@section('content')
    <div class="top">
        <h1>Posts</h1>
        <a class="btn primary" href="{{ route('posts.create') }}">New</a>
    </div>
    <form class="panel pad" method="get" action="{{ route('posts.index') }}" style="margin-bottom:14px;">
        <div class="form-grid">
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    @foreach (['draft', 'published', 'archived'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div style="align-self:end;" class="actions">
                <button class="btn primary" type="submit">Filter</button>
                <a class="btn" href="{{ route('posts.index') }}">Reset</a>
            </div>
        </div>
    </form>
    <div class="panel">
        <table>
            <thead><tr><th>Title</th><th>Status</th><th>Category</th><th>Author</th><th></th></tr></thead>
            <tbody>
            @foreach ($posts as $post)
                <tr>
                    <td>{{ $post['title'] }}<br><span class="badge">{{ $post['slug'] }}</span></td>
                    <td><span class="badge">{{ $post['status'] }}</span></td>
                    <td>{{ $post['category']['name'] ?? '-' }}</td>
                    <td>#{{ $post['author_id'] }}</td>
                    <td class="actions">
                        @if ($post['status'] !== 'published')
                            <form class="inline" method="post" action="{{ route('posts.publish', $post['id']) }}">
                                @csrf @method('patch')
                                <button class="btn" type="submit">Publish</button>
                            </form>
                        @endif
                        @if ($post['status'] !== 'archived')
                            <form class="inline" method="post" action="{{ route('posts.archive', $post['id']) }}">
                                @csrf @method('patch')
                                <button class="btn" type="submit">Archive</button>
                            </form>
                        @endif
                        <a class="btn" href="{{ route('posts.edit', $post['id']) }}">Edit</a>
                        <form class="inline" method="post" action="{{ route('posts.destroy', $post['id']) }}">
                            @csrf @method('delete')
                            <button class="btn danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
