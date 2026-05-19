<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }}</title>
    <style>
        :root { color-scheme: light; --border:#d7dce2; --ink:#16202a; --muted:#637083; --bg:#f5f7fa; --panel:#fff; --accent:#2364aa; --danger:#b42318; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, sans-serif; color:var(--ink); background:var(--bg); font-size:14px; }
        a { color:var(--accent); text-decoration:none; }
        .shell { display:grid; grid-template-columns:220px 1fr; min-height:100vh; }
        .side { background:#101820; color:#eef3f8; padding:18px 14px; }
        .brand { font-weight:700; font-size:18px; margin-bottom:20px; }
        .nav { display:grid; gap:4px; }
        .nav a, .logout button { display:block; width:100%; padding:9px 10px; color:#eef3f8; border-radius:6px; border:0; background:transparent; text-align:left; font:inherit; cursor:pointer; }
        .nav a:hover, .logout button:hover { background:#1e2c38; }
        .main { padding:22px; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; }
        h1 { font-size:24px; margin:0; }
        .panel { background:var(--panel); border:1px solid var(--border); border-radius:8px; overflow:hidden; }
        .pad { padding:16px; }
        .grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; }
        .stat { background:var(--panel); border:1px solid var(--border); border-radius:8px; padding:16px; }
        .stat strong { display:block; font-size:28px; margin-top:8px; }
        table { width:100%; border-collapse:collapse; background:var(--panel); }
        th, td { padding:11px 12px; border-bottom:1px solid var(--border); text-align:left; vertical-align:top; }
        th { color:var(--muted); font-size:12px; text-transform:uppercase; }
        .actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; justify-content:center; min-height:34px; padding:7px 11px; border:1px solid var(--border); border-radius:6px; background:#fff; color:var(--ink); cursor:pointer; font:inherit; }
        .btn.primary { background:var(--accent); border-color:var(--accent); color:#fff; }
        .btn.danger { color:var(--danger); }
        form.inline { display:inline; }
        label { display:block; color:var(--muted); margin:0 0 6px; }
        input, textarea, select { width:100%; border:1px solid var(--border); border-radius:6px; padding:9px 10px; font:inherit; background:#fff; }
        textarea { min-height:160px; resize:vertical; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .field-full { grid-column:1 / -1; }
        .errors { border:1px solid #f2b8b5; background:#fff1f0; color:#8f1d18; border-radius:8px; padding:12px; margin-bottom:14px; }
        .badge { display:inline-flex; padding:3px 8px; border-radius:999px; background:#eef2f7; color:#344054; font-size:12px; }
        @media (max-width: 780px) { .shell { grid-template-columns:1fr; } .side { position:static; } .grid, .form-grid { grid-template-columns:1fr; } .main { padding:14px; } }
    </style>
</head>
<body>
    @php($adminBase = rtrim(config('app.url'), '/'))
    <div class="shell">
        <aside class="side">
            <div class="brand">Admin</div>
            <nav class="nav">
                <a href="{{ $adminBase }}/">Dashboard</a>
                <a href="{{ $adminBase }}/posts">Posts</a>
                <a href="{{ $adminBase }}/comments">Comments</a>
                <a href="{{ $adminBase }}/categories">Categories</a>
                <a href="{{ $adminBase }}/tags">Tags</a>
            </nav>
            <form class="logout" method="post" action="{{ $adminBase }}/logout" style="margin-top:18px;">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </aside>
        <main class="main">
            @yield('content')
        </main>
    </div>
</body>
</html>
