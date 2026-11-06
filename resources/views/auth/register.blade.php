@extends('layouts.guest')
@section('title', 'Create Account')
@section('content')
<div>
    <h2 class="text-2xl font-bold text-white mb-1">Create your account</h2>
    <p class="text-gray-400 mb-8">Start with a 14-day Pro trial, then stay on the Free plan or upgrade. No card required.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-1.5">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition @error('name') border-rose-500/50 @enderror"
                       placeholder="John Doe">
                @error('name')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="company_name" class="block text-sm font-medium text-gray-300 mb-1.5">Company <span class="text-gray-500">(optional)</span></label>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition"
                       placeholder="Acme Inc">
            </div>
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition @error('email') border-rose-500/50 @enderror"
                   placeholder="you@company.com">
            @error('email')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">Password</label>
            <input id="password" name="password" type="password" required
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition @error('password') border-rose-500/50 @enderror"
                   placeholder="Min 8 characters">
            @error('password')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1.5">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition"
                   placeholder="••••••••">
        </div>
        <button type="submit" class="w-full bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 shadow-lg shadow-brand-500/20 hover:shadow-brand-500/40">
            Start free trial
        </button>
        <p class="text-xs text-gray-500 text-center">By creating an account, you agree to our <a href="#" class="text-gray-400 hover:text-white underline">Terms</a> and <a href="#" class="text-gray-400 hover:text-white underline">Privacy Policy</a>.</p>
    </form>

    <p class="mt-8 text-center text-sm text-gray-400">
        Already have an account? <a href="{{ route('login') }}" class="text-brand-400 hover:text-brand-300 font-medium transition">Sign in</a>
    </p>
</div>
@endsection
