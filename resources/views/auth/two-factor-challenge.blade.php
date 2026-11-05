@extends('layouts.guest')
@section('title', 'Two-Factor Authentication')
@section('content')
<div class="w-full max-w-sm mx-auto" x-data="{ useRecovery: false }">

    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-500/10 border border-brand-500/20 rounded-2xl mb-4">
            <svg class="w-7 h-7 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-white">Two-Factor Authentication</h1>
        <p class="text-sm text-gray-500 mt-1">Enter the code from your authenticator app</p>
    </div>

    @if($errors->any())
    <div class="mb-5 p-3.5 bg-rose-500/10 border border-rose-500/20 rounded-xl">
        <p class="text-sm text-rose-400">{{ $errors->first() }}</p>
    </div>
    @endif

    <form method="POST" action="{{ route('two-factor.challenge.verify') }}" class="space-y-4">
        @csrf

        {{-- TOTP input --}}
        <div x-show="!useRecovery">
            <label class="block text-sm font-medium text-gray-300 mb-1.5">Authentication Code</label>
            <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
                   autocomplete="one-time-code" autofocus
                   placeholder="000000"
                   x-ref="totpInput"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-4 text-white text-center text-2xl font-mono tracking-[0.5em] placeholder-gray-600 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
            <p class="text-xs text-gray-600 mt-2 text-center">Open your authenticator app and enter the 6-digit code.</p>
        </div>

        {{-- Recovery code input --}}
        <div x-show="useRecovery">
            <label class="block text-sm font-medium text-gray-300 mb-1.5">Recovery Code</label>
            <input type="text" name="code" maxlength="9" autocomplete="off"
                   placeholder="XXXX-XXXX"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-4 text-white text-center text-xl font-mono tracking-widest placeholder-gray-600 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition uppercase">
            <p class="text-xs text-gray-600 mt-2 text-center">Enter one of your saved recovery codes (format: XXXX-XXXX).</p>
        </div>

        <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl transition-all duration-200 text-sm shadow-lg shadow-brand-500/20">
            Verify
        </button>
    </form>

    <div class="mt-5 text-center">
        <button type="button" @click="useRecovery = !useRecovery"
                class="text-xs text-gray-500 hover:text-gray-300 transition underline underline-offset-2">
            <span x-show="!useRecovery">Use a recovery code instead</span>
            <span x-show="useRecovery">Use authenticator app instead</span>
        </button>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('login') }}" class="text-xs text-gray-600 hover:text-gray-400 transition">← Back to login</a>
    </div>
</div>
@endsection
