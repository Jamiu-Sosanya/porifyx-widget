<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ $widget->name }} help centre</title>
    <meta name="description" content="@yield('description', 'Answers and guides from ' . $widget->name . '.')">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
    <style>.pfx-prose h2{margin-top:1.6em}.pfx-prose a{color: {{ $widget->primary_color ?: '#ff6a2b' }}}</style>
</head>
@php $brand = $widget->primary_color ?: '#ff6a2b'; @endphp
<body class="min-h-screen bg-[#f6f7fb] text-slate-800 antialiased" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif">
<header class="text-white" style="background: linear-gradient(135deg, {{ $brand }}, #0d1a33)">
    <div class="max-w-4xl mx-auto px-5 py-10">
        <a href="{{ route('help.index', $widget->uuid) }}" class="text-sm font-semibold opacity-90 hover:opacity-100">{{ $widget->name }} · Help centre</a>
        <form action="{{ route('help.index', $widget->uuid) }}" class="mt-5">
            <input name="q" value="{{ $q ?? '' }}" maxlength="100" placeholder="Search for answers…" aria-label="Search help articles"
                   class="w-full rounded-2xl bg-white text-slate-800 px-5 py-3.5 text-[15px] shadow-lg outline-none focus:ring-4 focus:ring-white/30">
        </form>
    </div>
</header>
<main class="max-w-4xl mx-auto px-5 py-8">
    @yield('content')
</main>
<footer class="max-w-4xl mx-auto px-5 pb-10 text-xs text-slate-400">© {{ date('Y') }} {{ $widget->name }}</footer>
<script src="{{ asset('widget.js') }}" data-widget-id="{{ $widget->uuid }}" async></script>
</body>
</html>
