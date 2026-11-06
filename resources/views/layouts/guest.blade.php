<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#08122a] text-white antialiased" x-data>
    <div class="min-h-screen flex">
        {{-- Left: PoriFyx panel --}}
        <div class="hidden lg:flex lg:w-[44%] relative overflow-hidden bg-[#0d1a33] border-r border-white/5">
            <div class="absolute inset-0" style="background-image: radial-gradient(#ff6a2b33 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="absolute -bottom-40 -left-40 w-[28rem] h-[28rem] rounded-full bg-brand-500/25 blur-3xl"></div>
            <div class="relative z-10 flex flex-col justify-between p-12 w-full">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    @include('layouts.partials.logo', ['size' => 38])
                    <span class="text-xl font-extrabold tracking-tight text-white">{{ config('branding.name') }}</span>
                </a>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[.18em] text-accent-400">Answer · Qualify · Hand off</p>
                    <h2 class="mt-4 text-4xl font-extrabold leading-tight tracking-tight">Your website,<br>always in conversation.</h2>
                    <ul class="mt-8 space-y-4 text-slate-300">
                        @foreach(['Trained on your own content in minutes', 'Leads scored and routed automatically', 'Your team steps in whenever it matters'] as $line)
                        <li class="flex items-center gap-3">
                            <span class="grid place-items-center w-6 h-6 rounded-full bg-brand-500/20 text-brand-300 text-xs">✓</span>{{ $line }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="flex items-center gap-6 text-sm text-slate-500">
                    <span>© {{ date('Y') }} {{ config('branding.name') }}</span>
                    <a href="{{ route('privacy') }}" class="hover:text-slate-300 transition">Privacy</a>
                    <a href="{{ route('terms') }}" class="hover:text-slate-300 transition">Terms</a>
                </div>
            </div>
        </div>

        {{-- Right: Form --}}
        <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                {{-- Mobile logo --}}
                <div class="lg:hidden mb-8 flex items-center gap-3">
                    @include('layouts.partials.logo')
                    <span class="text-xl font-bold">{{ config('app.name') }}</span>
                </div>

                @if(session('status'))
                    <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    @include('layouts.partials.cookie-consent')
</body>
</html>
