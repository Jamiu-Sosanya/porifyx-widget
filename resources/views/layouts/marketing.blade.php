<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-gray-950 text-white antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-white/10 bg-gray-950/80 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-5 lg:px-8 py-5 flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    @include('layouts.partials.logo')
                    <span class="text-base font-bold text-white">{{ config('branding.name') }}</span>
                </a>
                <nav class="hidden md:flex items-center gap-4 text-sm text-gray-400">
                    <a href="{{ route('home') }}" class="hover:text-white transition">Home</a>
                    <a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a>
                    <a href="{{ route('privacy') }}" class="hover:text-white transition">Privacy</a>
                    <a href="{{ route('terms') }}" class="hover:text-white transition">Terms</a>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="border-t border-white/10 bg-gray-950/80">
            <div class="max-w-7xl mx-auto px-5 lg:px-8 py-8 text-sm text-gray-500 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p>© {{ date('Y') }} {{ config('branding.name') }}. All rights reserved.</p>
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('privacy') }}" class="hover:text-white transition">Privacy</a>
                    <a href="{{ route('terms') }}" class="hover:text-white transition">Terms</a>
                    <a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a>
                </div>
            </div>
        </footer>
    </div>

    @include('layouts.partials.cookie-consent')
</body>
</html>
