<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-white antialiased min-h-screen flex items-center justify-center px-4" x-data>
    <div class="w-full max-w-sm">

        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex mb-4">@include('layouts.partials.logo', ['size' => 52])</div>
            <h1 class="text-xl font-bold text-white">Administration Panel</h1>
            <p class="text-sm text-gray-500 mt-1">{{ config('app.name') }} · Restricted Access</p>
        </div>

        {{-- Security Notice --}}
        <div class="flex items-start gap-3 mb-6 p-3.5 bg-amber-500/5 border border-amber-500/15 rounded-xl">
            <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <p class="text-xs text-amber-400/80 leading-relaxed">This area is restricted to authorised administrators only. All access attempts are logged.</p>
        </div>

        {{-- Error message --}}
        @if($errors->any())
        <div class="mb-5 p-3.5 bg-rose-500/10 border border-rose-500/20 rounded-xl">
            <p class="text-sm text-rose-400">{{ $errors->first() }}</p>
        </div>
        @endif

        @if(session('status'))
        <div class="mb-5 p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
            <p class="text-sm text-emerald-400">{{ session('status') }}</p>
        </div>
        @endif

        {{-- Login Form --}}
        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-rose-500/50 focus:ring-2 focus:ring-rose-500/20 transition">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        x-ref="passwordField"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 pr-10 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-rose-500/50 focus:ring-2 focus:ring-rose-500/20 transition">
                    <button type="button" @click="$refs.passwordField.type = $refs.passwordField.type === 'password' ? 'text' : 'password'"
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-500 hover:text-gray-300 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </button>
                </div>
            </div>

            @if(config('app.features.two_factor', false))
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 300)">
                <div x-show="show" x-transition>
                    <label for="two_factor_code" class="block text-sm font-medium text-gray-300 mb-1.5">
                        Two-Factor Code <span class="text-gray-500 font-normal">(if enabled)</span>
                    </label>
                    <input type="text" id="two_factor_code" name="two_factor_code" maxlength="6" autocomplete="one-time-code"
                        placeholder="000000"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-rose-500/50 focus:ring-2 focus:ring-rose-500/20 transition tracking-widest text-center font-mono">
                </div>
            </div>
            @endif

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-white/5 border-white/20 text-rose-500 focus:ring-rose-500/30">
                    <span class="text-sm text-gray-400">Remember me</span>
                </label>
            </div>

            <button type="submit"
                class="w-full py-3 bg-rose-600 hover:bg-rose-500 text-white font-semibold rounded-xl transition-all duration-200 text-sm shadow-lg shadow-rose-500/20 mt-2">
                Sign In to Admin Panel
            </button>
        </form>

        {{-- Footer --}}
        <div class="mt-8 pt-6 border-t border-white/5 text-center space-y-2">
            <a href="{{ route('login') }}" class="text-xs text-gray-600 hover:text-gray-400 transition">← Back to user login</a>
            <p class="text-[10px] text-gray-700">
                IP: {{ request()->ip() }} · {{ now()->format('M d, Y g:i A') }}
            </p>
        </div>

    </div>
</body>
</html>
