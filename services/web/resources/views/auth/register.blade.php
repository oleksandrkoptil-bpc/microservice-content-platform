@extends('layouts.app')

@section('title', 'Реєстрація')

@section('content')
    <section class="section">
        <div class="wrap" style="max-width: 560px;">
            <h1>Реєстрація автора</h1>
            <form class="form card" method="POST" action="{{ route('register.store') }}" style="margin-top: 20px;">
                @csrf
                <label>
                    Ім'я
                    <input type="text" name="name" value="{{ old('name') }}" required>
                </label>
                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>
                <label>
                    Пароль
                    <input type="password" name="password" required>
                </label>
                <label>
                    Повтор пароля
                    <input type="password" name="password_confirmation" required>
                </label>
                <button class="button" type="submit">Створити акаунт</button>
            </form>
        </div>
    </section>
@endsection
