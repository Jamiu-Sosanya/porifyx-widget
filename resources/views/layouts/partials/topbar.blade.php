<header class="h-16 bg-gray-900/50 backdrop-blur-xl border-b border-white/5 flex items-center justify-between px-6 shrink-0">
    <div class="flex items-center gap-4">
        {{-- Mobile menu button --}}
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>

        {{-- Page Title --}}
        <h1 class="text-lg font-semibold text-white">@yield('page-title', 'Dashboard')</h1>
    </div>

    <div class="flex items-center gap-3">
        {{-- Search --}}
        <div class="hidden md:block relative" x-data="{ open: false }">
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input type="text" placeholder="Search..." class="w-56 bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-1 focus:ring-brand-500/20 transition">
            </div>
        </div>

        {{-- Quick Create --}}
        <a href="{{ route('widgets.create') }}" class="hidden sm:flex items-center gap-2 px-3 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <span>New Widget</span>
        </a>

        {{-- PWA Install Button --}}
        @if(!auth()->user()->isAdmin())
        <button id="pwa-install-button" style="display: none;" 
                onclick="window.pwaManager?.installApp()"
                class="hidden sm:flex items-center gap-2 px-3 py-2 bg-accent-600 hover:bg-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-accent-500/20"
                title="Install app on your device">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5V15m7.5-7.5H4.5" />
            </svg>
            <span>Install App</span>
        </button>
        @endif

        @include('layouts.partials.topbar-actions')
    </div>
</header>
