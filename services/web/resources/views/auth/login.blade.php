@extends('layouts.app')

@section('title', 'Увійти')

@section('content')
    <section class="section">
        <div class="wrap" style="max-width: 520px;">
            <h1>Увійти</h1>
            <form class="form card" method="POST" action="{{ route('login.store') }}" style="margin-top: 20px;">
                @csrf
                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>
                <label>
                    Пароль
                    <input type="password" name="password" required>
                </label>
                <button class="button" type="submit">Увійти</button>
            </form>
        </div>
    </section>
@endsection
