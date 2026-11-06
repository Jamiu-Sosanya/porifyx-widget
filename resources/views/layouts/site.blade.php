<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('branding.name') }}</title>
    <meta name="description" content="@yield('description', config('branding.description'))">
    <meta property="og:title" content="@yield('title') — {{ config('branding.name') }}">
    <meta property="og:description" content="@yield('description', config('branding.description'))">
    <script>
        (function () {
            var t = localStorage.getItem('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', t === 'dark');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; } html, body { overflow-x: clip; }</style>
</head>
<body class="antialiased font-sans" x-data="{
        menu: false,
        dark: document.documentElement.classList.contains('dark'),
        toggleTheme() { this.dark = !this.dark; document.documentElement.classList.toggle('dark', this.dark); localStorage.setItem('theme', this.dark ? 'dark' : 'light'); }
    }">
<div class="min-h-screen bg-[#f6f7fb] text-[#0d1a33] dark:bg-[#08122a] dark:text-slate-100 transition-colors">
@include('layouts.partials.site-nav')

{{-- Page header --}}
<section class="relative overflow-hidden border-b border-slate-200 dark:border-white/10">
    <div class="absolute -top-40 -right-32 w-[34rem] h-[34rem] rounded-full bg-brand-500/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -left-32 w-[28rem] h-[28rem] rounded-full bg-accent-500/10 blur-3xl pointer-events-none"></div>
    <div class="relative max-w-4xl mx-auto px-5 pt-16 pb-12 md:pt-24 md:pb-16">
        <p class="{{ $eyebrow }}">@yield('eyebrow')</p>
        <h1 class="mt-3 text-4xl sm:text-5xl font-extrabold tracking-tight text-balance">@yield('heading')</h1>
        <p class="mt-5 text-lg leading-relaxed max-w-2xl {{ $muted }}">@yield('lead')</p>
        @hasSection('meta')<p class="mt-6 text-sm {{ $muted }}">@yield('meta')</p>@endif
    </div>
</section>

<main>@yield('content')</main>

{{-- Closing call to action, shared by every page --}}
@unless(View::hasSection('no-cta'))
<section class="px-5 py-16">
    <div class="max-w-4xl mx-auto rounded-3xl bg-[#0d1a33] text-white px-7 py-10 md:px-12 md:py-12 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <p class="text-2xl font-extrabold tracking-tight">Still have questions?</p>
            <p class="mt-2 text-slate-300">A real person on our team reads every message and replies within one business day.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 shrink-0">
            <a href="{{ route('contact', ['topic' => 'sales']) }}#form" class="px-6 py-3.5 rounded-xl bg-white text-slate-900 font-bold text-center hover:bg-slate-200">Talk to sales</a>
            <a href="{{ route('register') }}" class="px-6 py-3.5 rounded-xl border border-white/25 font-semibold text-center hover:bg-white/5">Start free</a>
        </div>
    </div>
</section>
@endunless

@include('layouts.partials.site-footer')
</div>
@include('layouts.partials.cookie-consent')
</body>
</html>
