@extends('admin.layout', ['title' => 'Categories'])

@section('content')
    <div class="top">
        <h1>Categories</h1>
        <a class="btn primary" href="{{ route('categories.create') }}">New</a>
    </div>
    <div class="panel">
        <table>
            <thead><tr><th>Name</th><th>Slug</th><th>Description</th><th></th></tr></thead>
            <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category['name'] }}</td>
                    <td>{{ $category['slug'] }}</td>
                    <td>{{ $category['description'] }}</td>
                    <td class="actions">
                        <a class="btn" href="{{ route('categories.edit', $category['id']) }}">Edit</a>
                        <form class="inline" method="post" action="{{ route('categories.destroy', $category['id']) }}">
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
