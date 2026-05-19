<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; font-family:Arial, sans-serif; background:#f5f7fa; color:#16202a; }
        form { width:min(420px, calc(100vw - 28px)); background:#fff; border:1px solid #d7dce2; border-radius:8px; padding:22px; }
        h1 { margin:0 0 18px; font-size:24px; }
        label { display:block; color:#637083; margin:0 0 6px; font-size:14px; }
        input { width:100%; border:1px solid #d7dce2; border-radius:6px; padding:10px; font:inherit; margin-bottom:14px; box-sizing:border-box; }
        button { width:100%; border:0; border-radius:6px; padding:10px; background:#2364aa; color:#fff; font:inherit; cursor:pointer; }
        .errors { border:1px solid #f2b8b5; background:#fff1f0; color:#8f1d18; border-radius:8px; padding:12px; margin-bottom:14px; }
    </style>
</head>
<body>
    <form method="post" action="{{ route('login.store') }}">
        @csrf
        <h1>Admin</h1>
        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>
