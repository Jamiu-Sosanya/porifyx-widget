@extends('layouts.guest')
@section('title', 'Verify Email')
@section('content')
<div>
    <div class="flex justify-center mb-6">
        <div class="w-16 h-16 rounded-2xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center">
            <svg class="w-8 h-8 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
    </div>

    <h2 class="text-2xl font-bold text-white mb-1 text-center">Check your inbox</h2>
    <p class="text-gray-400 mb-8 text-center text-sm leading-relaxed">
        We sent a verification link to <strong class="text-white">{{ auth()->user()->email }}</strong>.
        Click the link in the email to activate your account.
    </p>

    @if (session('status'))
        <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="text-sm text-emerald-400">{{ session('status') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl">
            <p class="text-sm text-rose-400">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white/5 border border-white/10 rounded-xl p-4 mb-6 space-y-2">
        <div class="flex items-start gap-3">
            <span class="text-brand-400 mt-0.5 flex-shrink-0">①</span>
            <p class="text-sm text-gray-300">Open the email from us — subject: <em class="text-white">"Verify your email address"</em></p>
        </div>
        <div class="flex items-start gap-3">
            <span class="text-brand-400 mt-0.5 flex-shrink-0">②</span>
            <p class="text-sm text-gray-300">Click the <strong class="text-white">Verify Email Address</strong> button inside</p>
        </div>
        <div class="flex items-start gap-3">
            <span class="text-brand-400 mt-0.5 flex-shrink-0">③</span>
            <p class="text-sm text-gray-300">You'll be brought straight to your dashboard</p>
        </div>
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit"
                class="w-full bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 hover:text-white font-medium py-3 px-4 rounded-xl transition-all text-sm">
            Didn't receive it? Resend verification email
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-400">
        Wrong account?
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-brand-400 hover:text-brand-300 font-medium transition">Sign out</button>
        </form>
    </p>
</div>
@endsection
