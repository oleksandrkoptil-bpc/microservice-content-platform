@extends('layouts.app')

@section('title', 'Написати статтю')

@section('content')
    <section class="section">
        <div class="wrap" style="max-width: 820px;">
            <h1>Написати статтю</h1>
            <p class="muted">Нова стаття зберігається як чернетка. Публікація лишається за адміном.</p>
            <form class="form card" method="POST" action="{{ route('posts.store') }}" style="margin-top: 20px;">
                @csrf
                <label>
                    Категорія
                    <select name="category_id" required>
                        <option value="">Оберіть категорію</option>
                        @foreach($categories as $category)
                            <option value="{{ $category['id'] }}" @selected(old('category_id') == $category['id'])>{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Заголовок
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </label>
                <label>
                    Короткий опис
                    <textarea name="excerpt">{{ old('excerpt') }}</textarea>
                </label>
                <label>
                    Текст статті
                    <textarea name="content" required style="min-height: 280px;">{{ old('content') }}</textarea>
                </label>
                @if(count($tags))
                    <div>
                        <div class="muted" style="font-weight: 700; margin-bottom: 8px;">Теги</div>
                        <div class="checks">
                            @foreach($tags as $tag)
                                <label class="check">
                                    <input type="checkbox" name="tag_ids[]" value="{{ $tag['id'] }}" @checked(in_array($tag['id'], old('tag_ids', [])))>
                                    {{ $tag['name'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                <button class="button" type="submit">Зберегти чернетку</button>
            </form>
        </div>
    </section>
@endsection
