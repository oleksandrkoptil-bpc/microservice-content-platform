@extends('admin.layout', ['title' => $category ? 'Edit Category' : 'New Category'])

@section('content')
    <div class="top">
        <h1>{{ $category ? 'Edit Category' : 'New Category' }}</h1>
        <a class="btn" href="{{ route('categories.index') }}">Back</a>
    </div>
    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif
    <form class="panel pad" method="post" action="{{ $category ? route('categories.update', $category['id']) : route('categories.store') }}">
        @csrf
        @if ($category) @method('put') @endif
        <div class="form-grid">
            <div>
                <label>Name</label>
                <input name="name" value="{{ old('name', $category['name'] ?? '') }}" required>
            </div>
            <div>
                <label>Slug</label>
                <input name="slug" value="{{ old('slug', $category['slug'] ?? '') }}" required>
            </div>
            <div class="field-full">
                <label>Description</label>
                <textarea name="description">{{ old('description', $category['description'] ?? '') }}</textarea>
            </div>
        </div>
        <div class="actions" style="margin-top:14px;">
            <button class="btn primary" type="submit">Save</button>
        </div>
    </form>
@endsection
