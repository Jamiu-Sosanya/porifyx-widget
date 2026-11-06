<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="html-root" class="dark">
<head>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#ff6a2b">
    <meta name="application-name" content="{{ config('app.name', 'PoriFyx Widget') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Chat Widget">
    <meta name="description" content="AI-powered chat widget for lead generation and customer engagement">
    <meta name="push-public-key" content="{{ config('services.vapid.public_key') }}">
    <meta name="msapplication-TileColor" content="#ff6a2b">
    <meta name="msapplication-TileImage" content="/images/icon-192x192.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    {{-- Apply saved theme before CSS renders to prevent flash --}}
    <script>
        (function() {
            var t = localStorage.getItem('theme') || 'dark';
            document.documentElement.classList.remove('dark', 'light');
            document.documentElement.classList.add(t);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="pfx-shell bg-gray-950 text-gray-200 antialiased"
      x-data="{
          sidebarOpen: false,
          sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
          theme: localStorage.getItem('theme') || 'dark',
          toggleTheme() {
              this.theme = this.theme === 'dark' ? 'light' : 'dark';
              document.documentElement.classList.remove('dark', 'light');
              document.documentElement.classList.add(this.theme);
              localStorage.setItem('theme', this.theme);
          }
      }">
    @include('layouts.partials.app-nav')

            <main class="pfx-canvas min-h-screen lg:pl-72">
                {{-- Demo Mode Banner --}}
                @if(env('DEMO_MODE') === 'true' || env('DEMO_MODE') === true || env('DEMO_MODE') === '1')
                <div class="flex items-center justify-center gap-2 bg-amber-500 px-4 py-2 text-xs font-semibold text-amber-950">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    Demo Mode Active — all changes are disabled. This is a read-only preview.
                </div>
                @endif
                {{-- Flash Messages --}}
                @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                     class="max-w-[1400px] mx-auto px-4 sm:px-6 mt-4">
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            {{ session('success') }}
                        </div>
                        <button @click="show = false" class="text-emerald-400/60 hover:text-emerald-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                    </div>
                </div>
                @endif
                @if(session('error'))
                <div x-data="{ show: true }" x-show="show" class="max-w-[1400px] mx-auto px-4 sm:px-6 mt-4">
                    <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-400 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                            {{ session('error') }}
                        </div>
                        <button @click="show = false" class="text-rose-400/60 hover:text-rose-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                    </div>
                </div>
                @endif

                @if(session('impersonating'))
                <div class="max-w-[1400px] mx-auto px-4 sm:px-6 mt-4 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-400 text-sm flex items-center justify-between">
                    <span>⚡ You are impersonating <strong>{{ auth()->user()->name }}</strong></span>
                    <a href="{{ route('admin.stopImpersonating') }}" class="px-3 py-1 bg-amber-500/20 hover:bg-amber-500/30 rounded-lg text-xs font-medium transition">Stop</a>
                </div>
                @endif

                {{-- What's new: latest published changelog entry the user has not dismissed --}}
                @php
                    $pfxNews = auth()->check() && ! auth()->user()->isAdmin()
                        ? \Illuminate\Support\Facades\DB::table('changelog_entries')->whereNotNull('published_at')->where('published_at', '>', auth()->user()->changelog_seen_at ?? now()->subDays(30))->latest('published_at')->first()
                        : null;
                @endphp
                @if($pfxNews)
                <div x-data="{ show: true }" x-show="show" class="max-w-[1400px] mx-auto px-4 sm:px-6 mt-4">
                    <div class="p-3.5 rounded-xl border border-brand-500/20 bg-brand-500/10 text-sm text-gray-200 flex items-center gap-3">
                        <span class="shrink-0 px-2 py-0.5 rounded-full bg-brand-500 text-white text-[10px] font-bold uppercase">New</span>
                        <p class="flex-1 min-w-0 truncate"><strong>{{ $pfxNews->title }}</strong></p>
                        <a href="{{ route('changelog') }}" target="_blank" class="shrink-0 text-xs font-semibold text-brand-400">See what's new</a>
                        <button type="button" aria-label="Dismiss" class="shrink-0 text-gray-400 hover:text-white"
                                @click="show = false; fetch(@js(route('changelog.seen')), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })">✕</button>
                    </div>
                </div>
                @endif

                {{-- Plan status: tells owners what their visitors currently see. --}}
                @php
                    $pfxU = auth()->user();
                    $pfxOut = [];
                    if ($pfxU && ! $pfxU->isAdmin() && $pfxU->hasActivePlan()) {
                        foreach (['messages' => 'AI messages', 'leads' => 'new leads', 'bookings' => 'bookings'] as $m => $label) {
                            if ($pfxU->currentPlan()->{['messages' => 'messages_per_month', 'leads' => 'leads_per_month', 'bookings' => 'bookings_per_month'][$m]} != 0 && ! $pfxU->canUse($m)) $pfxOut[] = $label;
                        }
                    }
                @endphp
                @if($pfxU && ! $pfxU->isAdmin() && (! $pfxU->hasActivePlan() || $pfxOut))
                <div class="max-w-[1400px] mx-auto px-4 sm:px-6 mt-4">
                    <div class="p-4 rounded-xl border text-sm flex flex-col sm:flex-row sm:items-center gap-3 {{ $pfxU->hasActivePlan() ? 'bg-amber-500/10 border-amber-500/20 text-amber-300' : 'bg-rose-500/10 border-rose-500/20 text-rose-300' }}">
                        <p class="flex-1">
                            @if(! $pfxU->hasActivePlan())
                                <strong>Your subscription is not active.</strong> Your chat widgets are not answering with AI: visitors see a "leave a message" form and bookings are paused.
                            @else
                                <strong>You have used all your {{ implode(', ', $pfxOut) }} for this month.</strong>
                                {{ in_array('AI messages', $pfxOut) ? 'Visitors now see a "leave a message" form instead of AI replies.' : 'Visitors can still chat, but these stop until next month or an upgrade.' }}
                            @endif
                        </p>
                        <a href="{{ route('settings.billing') }}" class="shrink-0 text-center px-4 py-2 rounded-lg bg-brand-500 text-white text-xs font-semibold">{{ $pfxU->hasActivePlan() ? 'Upgrade plan' : 'Renew now' }}</a>
                    </div>
                </div>
                @endif

                <div class="max-w-[1400px] mx-auto px-4 sm:px-6 pt-4 lg:pt-0 pb-12">
                    <div class="pfx-pagehead mb-7 -mx-4 sm:-mx-6 px-4 sm:px-6 pt-4 pb-4 flex items-start justify-between gap-4 lg:sticky lg:top-0 lg:z-30 lg:pt-6">
                        <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-accent-400">{{ now()->format('l, j F') }}</p>
                        <h1 class="mt-1.5 text-2xl sm:text-3xl font-extrabold tracking-tight text-white">@yield('page-title', 'Dashboard')</h1>
                        </div>
                        <div class="hidden lg:flex items-center gap-1 pt-1">@include('layouts.partials.topbar-actions')</div>
                    </div>
                    @yield('content')
                </div>
            </main>

    @stack('modals')
    @stack('scripts')

    <!-- PWA Manager -->
    <script src="{{ asset('js/pwa.js') }}" defer></script>

    @include('layouts.partials.cookie-consent')
</body>
</html>
