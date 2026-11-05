@extends('layouts.guest')
@section('title', 'Forgot Password')
@section('content')
<div>
    <h2 class="text-2xl font-bold text-white mb-1">Reset your password</h2>
    <p class="text-gray-400 mb-8">Enter your email and we'll send you a reset link.</p>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition"
                   placeholder="you@company.com">
            @error('email')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-semibold py-3 px-4 rounded-xl transition-all shadow-lg shadow-brand-500/20">
            Send reset link
        </button>
    </form>
    <p class="mt-8 text-center text-sm text-gray-400">
        Remember your password? <a href="{{ route('login') }}" class="text-brand-400 hover:text-brand-300 font-medium transition">Sign in</a>
    </p>
</div>
@endsection
