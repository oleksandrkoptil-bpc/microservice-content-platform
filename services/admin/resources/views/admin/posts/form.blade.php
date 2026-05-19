@extends('admin.layout', ['title' => $post ? 'Edit Post' : 'New Post'])

@php
    $selectedTags = collect(old('tag_ids', collect($post['tags'] ?? [])->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
@endphp

@section('content')
    <div class="top">
        <h1>{{ $post ? 'Edit Post' : 'New Post' }}</h1>
        <a class="btn" href="{{ route('posts.index') }}">Back</a>
    </div>
    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif
    <form class="panel pad" method="post" action="{{ $post ? route('posts.update', $post['id']) : route('posts.store') }}">
        @csrf
        @if ($post) @method('put') @endif
        <div class="form-grid">
            <div>
                <label>Title</label>
                <input name="title" value="{{ old('title', $post['title'] ?? '') }}" required>
            </div>
            <div>
                <label>Slug</label>
                <input name="slug" value="{{ old('slug', $post['slug'] ?? '') }}" required>
            </div>
            <div>
                <label>Category</label>
                <select name="category_id" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category['id'] }}" @selected((int) old('category_id', $post['category_id'] ?? 0) === (int) $category['id'])>{{ $category['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Status</label>
                <select name="status" required>
                    @foreach (['draft', 'published', 'archived'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $post['status'] ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-full">
                <label>Tags</label>
                <select name="tag_ids[]" multiple>
                    @foreach ($tags as $tag)
                        <option value="{{ $tag['id'] }}" @selected(in_array((int) $tag['id'], $selectedTags, true))>{{ $tag['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-full">
                <label>Excerpt</label>
                <textarea name="excerpt">{{ old('excerpt', $post['excerpt'] ?? '') }}</textarea>
            </div>
            <div class="field-full">
                <label>Content</label>
                <textarea name="content" required>{{ old('content', $post['content'] ?? '') }}</textarea>
            </div>
            <div>
                <label>Published At</label>
                <input name="published_at" value="{{ old('published_at', $post['published_at'] ?? '') }}">
            </div>
        </div>
        <div class="actions" style="margin-top:14px;">
            <button class="btn primary" type="submit">Save</button>
        </div>
    </form>
@endsection
