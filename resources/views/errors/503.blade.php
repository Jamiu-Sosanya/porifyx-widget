<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service unavailable — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: "Plus Jakarta Sans", system-ui, sans-serif; background: #030712; color: #e5e7eb; min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { text-align: center; max-width: 420px; }
        h1 { font-size: 2rem; font-weight: 700; margin: 0; color: #f59e0b; }
        p { color: #9ca3af; margin: 16px 0 8px; line-height: 1.5; }
        .sub { font-size: 13px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <h1>We’ll be right back</h1>
        <p>The application is temporarily unavailable. Please try again shortly.</p>
        <p class="sub">Please try again in a few minutes.</p>
    </div>
</body>
</html>
