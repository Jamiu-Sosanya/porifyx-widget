@php $home = request()->routeIs('home') ? '' : route('home'); @endphp
<header class="sticky top-0 z-40 border-b border-slate-200/70 bg-[#f6f7fb]/80 backdrop-blur-lg dark:border-white/10 dark:bg-[#08122a]/80">
    <nav class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-extrabold tracking-tight text-lg shrink-0">
            @include('layouts.partials.logo', ['size' => 36])
        </a>
        <div class="hidden lg:flex items-center gap-7 text-sm font-medium {{ $muted }}">
            <a href="{{ $home }}#features" class="hover:text-slate-900 dark:hover:text-white">Features</a>
            <a href="{{ $home }}#bookings" class="hover:text-slate-900 dark:hover:text-white">Bookings</a>
            <a href="{{ $home }}#use-cases" class="hover:text-slate-900 dark:hover:text-white">Use cases</a>
            <a href="{{ $home }}#how" class="hover:text-slate-900 dark:hover:text-white">How it works</a>
            <a href="{{ $home }}#pricing" class="hover:text-slate-900 dark:hover:text-white">Pricing</a>
            <a href="{{ $home }}#faq" class="hover:text-slate-900 dark:hover:text-white">FAQ</a>
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button" @click="toggleTheme()" @if(\App\Support\MarketingTheme::forced()) hidden @endif :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                    class="grid place-items-center w-10 h-10 rounded-xl hover:bg-slate-200/70 dark:hover:bg-white/10">
                <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg x-show="dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <div class="hidden md:flex items-center gap-2 text-sm font-semibold">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-2.5 hover:text-brand-600 dark:hover:text-brand-300">Sign in</a>
                    <a href="{{ route('register') }}" class="px-4 py-2.5 rounded-xl bg-brand-500 text-white hover:bg-brand-600">Start free</a>
                @endauth
            </div>
            <button class="lg:hidden grid place-items-center w-10 h-10 -mr-2" @click="menu = !menu" :aria-expanded="menu" aria-label="Menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path x-show="!menu" d="M4 7h16M4 12h16M4 17h16"/><path x-show="menu" x-cloak d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </nav>
    <div x-show="menu" x-cloak x-transition.opacity @click="menu = false"
         class="lg:hidden absolute inset-x-0 top-16 border-b border-slate-200 bg-white px-5 py-5 flex flex-col gap-4 font-medium dark:bg-[#0d1a33] dark:border-white/10">
        <a href="{{ $home }}#features">Features</a><a href="{{ $home }}#bookings">Bookings</a><a href="{{ $home }}#use-cases">Use cases</a><a href="{{ $home }}#how">How it works</a><a href="{{ $home }}#pricing">Pricing</a><a href="{{ $home }}#faq">FAQ</a>
        <div class="grid grid-cols-2 gap-3 pt-2 md:hidden">
            <a href="{{ route('login') }}" class="py-3 rounded-xl border border-slate-300 dark:border-white/20 text-center">Sign in</a>
            <a href="{{ route('register') }}" class="py-3 rounded-xl bg-brand-500 text-white text-center">Start free</a>
        </div>
    </div>
</header>
