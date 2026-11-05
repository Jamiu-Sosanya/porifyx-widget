{{-- Customer app navigation: a top bar with pill links (admins use layouts.admin). --}}
@php
    $isAdmin = auth()->user()->isAdmin();
    $navItems = $isAdmin
        ? [['admin.dashboard', 'Admin panel', 'admin.*']]
        : [
            ['dashboard', 'Overview', 'dashboard'],
            ['widgets.index', 'Widgets', 'widgets.*'],
            ['conversations.index', 'Conversations', 'conversations.*'],
            ['leads.index', 'Leads', 'leads.*'],
            ['appointments.index', 'Bookings', 'appointments.*'],
            ['analytics.index', 'Analytics', 'analytics.*'],
            ['automations.index', 'Automations', 'automations.*'],
        ];
    $settingsItems = [
        ['settings.profile', 'Account settings'],
        ['settings.cannedResponses', 'Canned responses'],
        ['settings.developer', 'Webhooks & API'],
    ];
    $settingsActive = request()->routeIs('settings.*');
@endphp
<header class="pfx-appbar sticky top-0 z-40 border-b border-white/[0.06] bg-[#070b16]/85 backdrop-blur-xl">
    <div class="max-w-[1400px] mx-auto h-16 px-4 sm:px-6 flex items-center gap-4">
        {{-- Mobile menu --}}
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 -ml-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5" aria-label="Menu">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </button>

        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0">
            @include('layouts.partials.logo')
            <span class="hidden sm:block text-[15px] font-bold text-white tracking-tight">{{ config('branding.name') }}</span>
        </a>

        {{-- Desktop pills --}}
        <nav class="hidden lg:flex items-center gap-1 ml-4 p-1 rounded-full bg-white/[0.04] border border-white/[0.06]">
            @foreach($navItems as [$route, $label, $pattern])
                <a href="{{ route($route) }}"
                   class="px-3.5 py-1.5 rounded-full text-[13px] font-semibold transition {{ request()->routeIs($pattern) ? 'pfx-pill-active bg-white text-gray-950 shadow' : 'text-gray-400 hover:text-white' }}">{{ $label }}</a>
            @endforeach
            <div class="relative" x-data="{ open: false }" @click.away="open = false">
                <button @click="open = !open" class="flex items-center gap-1 px-3.5 py-1.5 rounded-full text-[13px] font-semibold transition {{ $settingsActive ? 'pfx-pill-active bg-white text-gray-950 shadow' : 'text-gray-400 hover:text-white' }}">
                    Settings
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" x-transition x-cloak class="absolute left-0 mt-2 w-56 p-1 bg-gray-900 border border-white/10 rounded-2xl shadow-2xl z-50">
                    @foreach($settingsItems as [$route, $label])
                        <a href="{{ route($route) }}" class="block px-3 py-2 rounded-xl text-sm {{ request()->routeIs($route.'*') ? 'text-brand-400 bg-brand-500/10' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <a href="{{ route('widgets.create') }}" class="hidden sm:flex items-center gap-1.5 pl-3 pr-4 py-2 rounded-full bg-gradient-to-r from-brand-600 to-accent-500 text-white text-sm font-semibold shadow-lg shadow-brand-600/25 hover:brightness-110 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New widget
            </a>
            @include('layouts.partials.topbar-actions')
        </div>
    </div>

    {{-- Mobile drawer --}}
    <nav x-show="sidebarOpen" x-collapse x-cloak class="lg:hidden border-t border-white/[0.06] px-4 py-3 grid grid-cols-2 gap-1.5">
        @foreach(array_merge(array_map(fn ($i) => [$i[0], $i[1], $i[2]], $navItems), array_map(fn ($i) => [$i[0], $i[1], $i[0].'*'], $settingsItems)) as [$route, $label, $pattern])
            <a href="{{ route($route) }}" class="px-3 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs($pattern) ? 'bg-brand-500/15 text-brand-300' : 'text-gray-300 bg-white/[0.03]' }}">{{ $label }}</a>
        @endforeach
    </nav>
</header>
