<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: "Plus Jakarta Sans", system-ui, sans-serif; background: #030712; color: #e5e7eb; min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { text-align: center; max-width: 420px; }
        h1 { font-size: 4rem; font-weight: 700; margin: 0; background: linear-gradient(135deg, #2f5ee8, #0891b2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        p { color: #9ca3af; margin: 16px 0 24px; line-height: 1.5; }
        a { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #2449c4; color: white; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 14px; }
        a:hover { background: #2f5ee8; }
    </style>
</head>
<body>
    <div class="card">
        <h1>404</h1>
        <p>The page you are looking for does not exist or was moved.</p>
        <a href="{{ url('/') }}">← Back home</a>
    </div>
</body>
</html>
