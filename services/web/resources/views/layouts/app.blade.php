<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Blog')</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #191816;
            --muted: #6f6a61;
            --line: #e5dfd4;
            --paper: #fffdf8;
            --soft: #f5f0e7;
            --accent: #1e6f5c;
            --accent-dark: #144b40;
            --warm: #c4562e;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--paper);
            line-height: 1.55;
        }
        a { color: inherit; text-decoration: none; }
        .wrap { width: min(1120px, calc(100% - 32px)); margin: 0 auto; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255, 253, 248, .94);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(12px);
        }
        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 68px;
            gap: 18px;
        }
        .brand { font-size: 22px; font-weight: 800; letter-spacing: 0; }
        .nav-links, .nav-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .nav a, .link-button {
            color: var(--muted);
            font-weight: 650;
            font-size: 14px;
        }
        .nav a:hover, .link-button:hover { color: var(--ink); }
        .link-button {
            border: 0;
            background: transparent;
            padding: 0;
            cursor: pointer;
            font: inherit;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid var(--accent);
            border-radius: 6px;
            background: var(--accent);
            color: white;
            font-weight: 750;
            cursor: pointer;
        }
        .button.secondary { background: transparent; color: var(--accent-dark); }
        .button.warm { background: var(--warm); border-color: var(--warm); }
        .hero {
            min-height: 440px;
            display: grid;
            align-items: end;
            background:
                linear-gradient(90deg, rgba(13, 17, 15, .78), rgba(13, 17, 15, .24)),
                url("https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=1800&q=80") center / cover;
            color: white;
        }
        .hero-content { padding: 86px 0 70px; width: min(720px, 100%); }
        .eyebrow {
            display: inline-block;
            margin-bottom: 14px;
            color: #f6d6be;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }
        h1, h2, h3 { line-height: 1.12; margin: 0; letter-spacing: 0; }
        .hero h1 { font-size: clamp(38px, 6vw, 70px); max-width: 760px; }
        .hero p { max-width: 620px; color: rgba(255,255,255,.84); font-size: 18px; }
        .section { padding: 42px 0; }
        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }
        .section-head h2 { font-size: 30px; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
        .two-col { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 28px; align-items: start; }
        .card {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: white;
            padding: 20px;
        }
        .post-card h3 { font-size: 21px; margin-bottom: 10px; }
        .post-card p, .muted { color: var(--muted); }
        .meta { display: flex; gap: 8px; flex-wrap: wrap; color: var(--muted); font-size: 13px; margin-bottom: 12px; }
        .pill {
            display: inline-flex;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 4px 10px;
            color: var(--accent-dark);
            background: var(--soft);
            font-size: 13px;
            font-weight: 700;
        }
        .category-list { display: flex; gap: 10px; flex-wrap: wrap; }
        .article { max-width: 780px; }
        .article h1 { font-size: clamp(34px, 5vw, 56px); margin-bottom: 16px; }
        .article-body { white-space: pre-line; font-size: 18px; }
        .form {
            display: grid;
            gap: 14px;
        }
        label { display: grid; gap: 7px; color: var(--muted); font-weight: 700; }
        input, textarea, select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 12px 13px;
            color: var(--ink);
            background: white;
            font: inherit;
        }
        textarea { min-height: 130px; resize: vertical; }
        .checks { display: flex; flex-wrap: wrap; gap: 10px; }
        .check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 12px;
            color: var(--ink);
            background: white;
        }
        .check input { width: auto; }
        .notice, .errors {
            margin: 18px auto 0;
            width: min(1120px, calc(100% - 32px));
            border-radius: 8px;
            padding: 12px 14px;
        }
        .notice { background: #e8f5ef; color: #14513f; }
        .errors { background: #fff1eb; color: #8b2d16; }
        footer { border-top: 1px solid var(--line); padding: 28px 0; color: var(--muted); }

        @media (max-width: 820px) {
            .nav { align-items: flex-start; flex-direction: column; padding: 14px 0; }
            .grid, .two-col { grid-template-columns: 1fr; }
            .hero { min-height: 390px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <nav class="wrap nav">
            <div class="nav-links">
                <a class="brand" href="{{ route('home') }}">Blog</a>
                <a href="{{ route('posts.index') }}">Статті</a>
                <a href="{{ route('posts.create') }}">Написати</a>
            </div>
            <div class="nav-actions">
                @if(session('user'))
                    <span class="muted">{{ session('user.name') }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="link-button" type="submit">Вийти</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Увійти</a>
                    <a class="button secondary" href="{{ route('register') }}">Реєстрація</a>
                @endif
            </div>
        </nav>
    </header>

    @if(session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="errors">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer>
        <div class="wrap">Blog microservice pet project. Public web читає дані з `blog` API і авторизується через `auth` API.</div>
    </footer>
</body>
</html>
