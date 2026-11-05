@extends('layouts.guest')
@section('title', 'Sign In')
@section('content')
<div>
    <h2 class="text-2xl font-bold text-white mb-1">Welcome back</h2>
    <p class="text-gray-400 mb-8">Sign in to your account to continue</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition @error('email') border-rose-500/50 @enderror"
                   placeholder="you@company.com">
            @error('email')
            <p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm text-brand-400 hover:text-brand-300 transition">Forgot?</a>
            </div>
            <input id="password" name="password" type="password" required
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition"
                   placeholder="••••••••">
            @error('password')
            <p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox" class="w-4 h-4 rounded bg-white/5 border-white/20 text-brand-600 focus:ring-brand-500/30">
            <label for="remember" class="ml-2 text-sm text-gray-400">Remember me</label>
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 shadow-lg shadow-brand-500/20 hover:shadow-brand-500/40 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
            Sign in
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-gray-400">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-brand-400 hover:text-brand-300 font-medium transition">Create free account</a>
    </p>
</div>
@endsection
