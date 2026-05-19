@extends('admin.layout', ['title' => $tag ? 'Edit Tag' : 'New Tag'])

@section('content')
    <div class="top">
        <h1>{{ $tag ? 'Edit Tag' : 'New Tag' }}</h1>
        <a class="btn" href="{{ route('tags.index') }}">Back</a>
    </div>
    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif
    <form class="panel pad" method="post" action="{{ $tag ? route('tags.update', $tag['id']) : route('tags.store') }}">
        @csrf
        @if ($tag) @method('put') @endif
        <div class="form-grid">
            <div>
                <label>Name</label>
                <input name="name" value="{{ old('name', $tag['name'] ?? '') }}" required>
            </div>
            <div>
                <label>Slug</label>
                <input name="slug" value="{{ old('slug', $tag['slug'] ?? '') }}" required>
            </div>
        </div>
        <div class="actions" style="margin-top:14px;">
            <button class="btn primary" type="submit">Save</button>
        </div>
    </form>
@endsection
