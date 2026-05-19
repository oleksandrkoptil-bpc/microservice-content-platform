@extends('admin.layout', ['title' => 'Tags'])

@section('content')
    <div class="top">
        <h1>Tags</h1>
        <a class="btn primary" href="{{ route('tags.create') }}">New</a>
    </div>
    <div class="panel">
        <table>
            <thead><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
            <tbody>
            @foreach ($tags as $tag)
                <tr>
                    <td>{{ $tag['name'] }}</td>
                    <td>{{ $tag['slug'] }}</td>
                    <td class="actions">
                        <a class="btn" href="{{ route('tags.edit', $tag['id']) }}">Edit</a>
                        <form class="inline" method="post" action="{{ route('tags.destroy', $tag['id']) }}">
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
