@extends('layouts.app')
@section('title', 'Two-Factor Authentication')
@section('page-title', 'Settings')
@section('content')
<div class="max-w-3xl" x-data="{ showDisableForm: false }">

    {{-- Settings Nav --}}
    <div class="flex items-center gap-1 bg-gray-900/50 border border-white/5 rounded-2xl p-1.5 mb-6 overflow-x-auto">
        @foreach(['settings.profile' => '👤 Profile', 'settings.notifications' => '🔔 Notifications', 'settings.team' => '👥 Team', 'settings.billing' => '💳 Billing', 'settings.twoFactor' => '🔐 Two-Factor', 'settings.apiKeys' => '🔑 API Keys'] as $route => $label)
        <a href="{{ route($route) }}" class="px-4 py-2 text-xs font-medium rounded-xl transition whitespace-nowrap {{ request()->routeIs($route) ? 'bg-white/10 text-white shadow' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400">{{ $errors->first() }}</div>
    @endif

    {{-- Recovery Codes (shown once after enabling or regenerating) --}}
    @if(session('recovery_codes'))
    <div class="mb-6 bg-amber-500/5 border border-amber-500/20 rounded-2xl p-5">
        <div class="flex items-start gap-3 mb-4">
            <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-400">Save your recovery codes now</p>
                <p class="text-xs text-gray-500 mt-0.5">These codes will not be shown again. Store them somewhere safe. Each code can only be used once.</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            @foreach(session('recovery_codes') as $code)
            <code class="block px-3 py-2 bg-black/30 rounded-lg text-sm font-mono text-white text-center tracking-widest border border-white/5">{{ $code }}</code>
            @endforeach
        </div>
        <button onclick="
            const codes = @js(session('recovery_codes'));
            navigator.clipboard.writeText(codes.join('\n')).then(() => this.textContent = '✓ Copied!');
            setTimeout(() => this.textContent = 'Copy all codes', 2000);
        " class="mt-3 px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-medium rounded-xl transition">
            Copy all codes
        </button>
    </div>
    @endif

    {{-- Status Banner --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 mb-5 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl {{ $user->two_factor_enabled ? 'bg-emerald-500/10' : 'bg-gray-500/10' }}">
                {{ $user->two_factor_enabled ? '🔐' : '🔓' }}
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Two-Factor Authentication</p>
                @if($user->two_factor_enabled)
                <p class="text-xs text-emerald-400 mt-0.5">● Enabled · Confirmed {{ $user->two_factor_confirmed_at?->diffForHumans() }}</p>
                @else
                <p class="text-xs text-gray-500 mt-0.5">Not enabled · Protect your account with an authenticator app</p>
                @endif
            </div>
        </div>
        @if($user->two_factor_enabled)
        <button @click="showDisableForm = !showDisableForm"
                class="px-4 py-2 text-xs font-medium bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-xl transition">
            Disable 2FA
        </button>
        @endif
    </div>

    {{-- Disable Form (toggle) --}}
    @if($user->two_factor_enabled)
    <div x-show="showDisableForm" x-transition class="bg-gray-900/50 border border-rose-500/20 rounded-2xl p-5 mb-5">
        <p class="text-sm font-medium text-white mb-3">Confirm your password to disable 2FA</p>
        <form method="POST" action="{{ route('settings.twoFactor.disable') }}" class="flex gap-3">
            @csrf
            <input type="password" name="password" placeholder="Your account password" autocomplete="current-password" required
                   class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-rose-500/50 transition">
            <button type="submit" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-xl transition">
                Disable
            </button>
        </form>
    </div>

    {{-- Manage section (when enabled) --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 mb-5">
        <h3 class="text-sm font-semibold text-white mb-4">Recovery Codes</h3>
        <p class="text-xs text-gray-500 mb-4">Recovery codes allow you to access your account if you lose your authenticator device. Each code can only be used once. Generate new codes below — this will invalidate all previous codes.</p>
        <form method="POST" action="{{ route('settings.twoFactor.recoveryCodes') }}"
              onsubmit="return confirm('Regenerate recovery codes? All existing codes will be invalidated.')">
            @csrf
            <button type="submit" class="px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-medium rounded-xl transition">
                Regenerate Recovery Codes
            </button>
        </form>
    </div>

    @else
    {{-- Setup section (when not enabled) --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-5">
        <h3 class="text-base font-semibold text-white mb-2">Set Up Authenticator App</h3>
        <p class="text-xs text-gray-500 mb-6">Scan the QR code below with your authenticator app (Google Authenticator, Authy, 1Password, etc.), then enter the 6-digit code to confirm and activate 2FA.</p>

        {{-- Step 1: QR Code --}}
        <div class="flex flex-col md:flex-row gap-8 items-start mb-6">
            <div class="shrink-0">
                <p class="text-xs font-medium text-gray-400 mb-3">Step 1 — Scan QR Code</p>
                @if($qrUrl)
                <div class="p-4 bg-white rounded-2xl inline-block">
                    {{-- Render QR code via Google Chart API --}}
                    <img src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={{ urlencode($qrUrl) }}"
                         alt="2FA QR Code" class="w-48 h-48">
                </div>
                @endif
            </div>
            <div class="flex-1">
                <p class="text-xs font-medium text-gray-400 mb-3">Can't scan? Enter manually</p>
                <p class="text-xs text-gray-500 mb-2">Account: <span class="text-white">{{ $user->email }}</span></p>
                <p class="text-xs text-gray-500 mb-3">Key:</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 bg-black/30 border border-white/5 rounded-xl px-3 py-2.5 text-sm font-mono text-brand-300 tracking-widest break-all">{{ $secret }}</code>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $secret }}').then(() => { this.textContent = '✓'; setTimeout(() => this.textContent = '⧉', 2000) })"
                            class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-gray-400 hover:text-white transition text-sm" title="Copy secret">⧉</button>
                </div>
                <p class="text-[10px] text-gray-600 mt-2">Time-based (TOTP) · 30 second interval</p>
            </div>
        </div>

        {{-- Step 2: Verify code --}}
        <div>
            <p class="text-xs font-medium text-gray-400 mb-3">Step 2 — Verify with your app</p>
            <form method="POST" action="{{ route('settings.twoFactor.enable') }}" class="flex gap-3 max-w-sm">
                @csrf
                <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required
                       placeholder="000000"
                       class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-center text-xl font-mono tracking-widest placeholder-gray-600 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
                <button type="submit" class="px-5 py-3 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">
                    Confirm & Enable
                </button>
            </form>
            @error('code')
            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Info cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach([
            ['🛡️', 'Extra Security', 'A one-time code is required at every login, even if your password is stolen.'],
            ['📱', 'Any TOTP App', 'Works with Google Authenticator, Authy, Microsoft Authenticator, 1Password, and more.'],
            ['🔑', 'Recovery Codes', 'You\'ll get 8 one-time recovery codes to use if you lose access to your device.'],
        ] as [$icon, $title, $desc])
        <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-4">
            <div class="text-2xl mb-2">{{ $icon }}</div>
            <p class="text-sm font-semibold text-white mb-1">{{ $title }}</p>
            <p class="text-xs text-gray-500 leading-relaxed">{{ $desc }}</p>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
