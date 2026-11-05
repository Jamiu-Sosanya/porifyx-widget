@extends('layouts.guest')
@section('title', 'Reset Password')
@section('content')
<div>
    <h2 class="text-2xl font-bold text-white mb-1">Set new password</h2>
    <p class="text-gray-400 mb-8">Choose a strong password for your account.</p>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', request('email')) }}" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
            @error('email')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">New password</label>
            <input id="password" name="password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
            @error('password')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1.5">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
        </div>
        <button type="submit" class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-semibold py-3 px-4 rounded-xl transition-all shadow-lg shadow-brand-500/20">
            Reset password
        </button>
    </form>
</div>
@endsection
