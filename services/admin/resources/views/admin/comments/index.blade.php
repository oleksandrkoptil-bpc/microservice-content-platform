@extends('admin.layout', ['title' => 'Comments'])

@section('content')
    <div class="top">
        <h1>Comments</h1>
    </div>
    <form class="panel pad" method="get" action="{{ route('comments.index') }}" style="margin-bottom:14px;">
        <div class="form-grid">
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    @foreach (['pending', 'approved', 'rejected'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div style="align-self:end;" class="actions">
                <button class="btn primary" type="submit">Filter</button>
                <a class="btn" href="{{ route('comments.index') }}">Reset</a>
            </div>
        </div>
    </form>
    <div class="panel">
        <table>
            <thead><tr><th>Content</th><th>Post</th><th>Status</th><th>Author</th><th></th></tr></thead>
            <tbody>
            @foreach ($comments as $comment)
                <tr>
                    <td>{{ $comment['content'] }}</td>
                    <td>#{{ $comment['post_id'] }}</td>
                    <td><span class="badge">{{ $comment['status'] }}</span></td>
                    <td>#{{ $comment['author_id'] }}</td>
                    <td class="actions">
                        <form class="inline" method="post" action="{{ route('comments.approve', $comment['id']) }}">
                            @csrf @method('patch')
                            <button class="btn" type="submit">Approve</button>
                        </form>
                        <form class="inline" method="post" action="{{ route('comments.reject', $comment['id']) }}">
                            @csrf @method('patch')
                            <button class="btn" type="submit">Reject</button>
                        </form>
                        <form class="inline" method="post" action="{{ route('comments.destroy', $comment['id']) }}">
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
