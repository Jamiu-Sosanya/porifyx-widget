{{-- Customer app navigation: floating sidebar on desktop, slide-in drawer on mobile (admins use layouts.admin). --}}
@php
    $isAdmin = auth()->user()->isAdmin();
    $i = fn ($d) => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
    $is = fn (...$p) => request()->routeIs(...$p);
    // [url, label, active, icon, children: [url, label, active]]
    $firstWidget = $isAdmin ? null : auth()->user()->widgets()->value('id');
    $groups = $isAdmin
        ? ['Admin' => [[route('admin.dashboard'), 'Admin panel', $is('admin.*'), 'M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z', []]]]
        : [
            'Workspace' => [
                [route('dashboard'), 'Overview', $is('dashboard'), 'M4 5h7v7H4zM13 5h7v4h-7zM13 11h7v8h-7zM4 14h7v5H4z', []],
                [route('widgets.index'), 'Widgets', $is('widgets.*') && ! $is('widgets.booking*'), 'M8 10h8M8 14h5M5 4h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-6l-5 4v-4H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z', []],
                [route('conversations.index'), 'Conversations', $is('conversations.*'), 'M7 8h10M7 12h6m-9 8 3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14z', []],
                [route('leads.index'), 'Leads', $is('leads.*'), 'M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM21 19v-1a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75', []],
                [route('appointments.index'), 'Bookings', $is('appointments.*', 'widgets.booking*'), 'M8 2v3m8-3v3M3.5 9h17M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z', array_filter([
                    [route('appointments.index'), 'Agenda', $is('appointments.*') && request('view') !== 'week'],
                    [route('appointments.index', ['view' => 'week']), 'Week view', $is('appointments.*') && request('view') === 'week'],
                    $firstWidget ? [route('widgets.booking', $firstWidget), 'Booking setup', $is('widgets.booking*')] : null,
                ])],
            ],
            'Grow' => [
                [route('analytics.index'), 'Analytics', $is('analytics.*'), 'M4 20V10m6 10V4m6 16v-7m4 7H2', []],
                [route('automations.index'), 'Automations', $is('automations.*'), 'M13 2 4 14h7l-1 8 9-12h-7l1-8z', []],
                [route('settings.profile'), 'Settings', $is('settings.*'), 'M10.3 4.3a1.7 1.7 0 0 1 3.4 0l.2.9a1.7 1.7 0 0 0 2.5 1l.8-.4a1.7 1.7 0 0 1 2.4 2.4l-.4.8a1.7 1.7 0 0 0 1 2.5l.9.2a1.7 1.7 0 0 1 0 3.4l-.9.2a1.7 1.7 0 0 0-1 2.5l.4.8a1.7 1.7 0 0 1-2.4 2.4l-.8-.4a1.7 1.7 0 0 0-2.5 1l-.2.9a1.7 1.7 0 0 1-3.4 0l-.2-.9a1.7 1.7 0 0 0-2.5-1l-.8.4a1.7 1.7 0 0 1-2.4-2.4l.4-.8a1.7 1.7 0 0 0-1-2.5l-.9-.2a1.7 1.7 0 0 1 0-3.4l.9-.2a1.7 1.7 0 0 0 1-2.5l-.4-.8a1.7 1.7 0 0 1 2.4-2.4l.8.4a1.7 1.7 0 0 0 2.5-1l.2-.9zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z', [
                    [route('settings.profile'), 'Profile', $is('settings.profile*', 'settings.password*')],
                    [route('settings.notifications'), 'Notifications', $is('settings.notifications*')],
                    [route('settings.twoFactor'), 'Security', $is('settings.twoFactor*')],
                    [route('settings.team'), 'Team', $is('settings.team*')],
                    [route('settings.billing'), 'Billing', $is('settings.billing*')],
                    [route('settings.cannedResponses'), 'Canned responses', $is('settings.cannedResponses*')],
                    [route('settings.apiKeys'), 'API keys', $is('settings.apiKeys*')],
                    [route('settings.developer'), 'Webhooks', $is('settings.developer*')],
                ]],
            ],
        ];
    $plan = $isAdmin ? null : rescue(fn () => auth()->user()->subscription?->plan?->name, null, false);
@endphp

{{-- Mobile top bar --}}
<header class="pfx-appbar lg:hidden sticky top-0 z-40 h-16 px-4 flex items-center gap-3 border-b border-white/[0.06] bg-[#070b16]/90 backdrop-blur-xl">
    <button @click="sidebarOpen = true" class="p-2 -ml-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5" aria-label="Open menu">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
    </button>
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">@include('layouts.partials.logo')</a>
    <div class="ml-auto flex items-center gap-1">@include('layouts.partials.topbar-actions')</div>
</header>

<div x-show="sidebarOpen" x-transition.opacity x-cloak @click="sidebarOpen = false" class="lg:hidden fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"></div>

{{-- Sidebar --}}
<aside class="pfx-sidebar fixed z-50 inset-y-0 left-0 w-72 p-3 transition-transform duration-300 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="h-full flex flex-col rounded-3xl border border-white/[0.07] bg-gradient-to-b from-[#0d1428] to-[#080c18] shadow-2xl shadow-black/40 overflow-hidden">
        <div class="px-5 pt-5 pb-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                @include('layouts.partials.logo')
                <span class="text-[15px] font-bold text-white tracking-tight truncate">{{ config('branding.name') }}</span>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:text-white" aria-label="Close menu">✕</button>
        </div>

        @unless($isAdmin)
        <div class="px-4">
            <a href="{{ route('widgets.create') }}" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-2xl bg-gradient-to-r from-brand-600 to-accent-500 text-white text-sm font-semibold shadow-lg shadow-brand-600/25 hover:brightness-110 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New widget
            </a>
        </div>
        @endunless

        {{-- Keeps its scroll position between pages so the clicked item stays in view. --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5 custom-scrollbar"
             x-data x-init="$el.scrollTop = +sessionStorage.getItem('pfx-nav-scroll') || 0; const on = $el.querySelector('[aria-current=page]'); if (on) { const r = on.getBoundingClientRect(), n = $el.getBoundingClientRect(); if (r.top < n.top || r.bottom > n.bottom) on.scrollIntoView({ block: 'center' }); }"
             @scroll.debounce.100ms="sessionStorage.setItem('pfx-nav-scroll', $el.scrollTop)">
            @foreach($groups as $group => $items)
            <div>
                <p class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-500">{{ $group }}</p>
                <div class="space-y-0.5">
                    @foreach($items as [$url, $label, $on, $icon, $children])
                    <div x-data="{ open: {{ $on ? 'true' : 'false' }} }">
                        <div class="relative flex items-center">
                            <a href="{{ $url }}" @if($on && ! $children) aria-current="page" @endif
                               class="group relative flex-1 flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ $on ? 'pfx-nav-on bg-white/[0.07] text-white' : 'text-gray-400 hover:text-white hover:bg-white/[0.04]' }}">
                                @if($on)<span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r-full bg-gradient-to-b from-brand-400 to-accent-400"></span>@endif
                                <span class="{{ $on ? 'text-accent-300' : 'text-gray-500 group-hover:text-gray-300' }}">{!! $i($icon) !!}</span>
                                {{ $label }}
                            </a>
                            @if($children)
                            <button type="button" @click="open = !open" class="absolute right-1.5 p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-white/5" :aria-expanded="open" aria-label="Show {{ $label }} pages">
                                <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                            </button>
                            @endif
                        </div>
                        @if($children)
                        <div x-show="open" x-collapse @if(! $on) x-cloak @endif class="mt-0.5 ml-[1.35rem] pl-3 border-l border-white/[0.08] space-y-0.5">
                            @foreach($children as [$curl, $clabel, $con])
                            <a href="{{ $curl }}" @if($con) aria-current="page" @endif
                               class="block px-3 py-2 rounded-lg text-[13px] transition {{ $con ? 'text-white bg-white/[0.06] font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/[0.04]' }}">{{ $clabel }}</a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </nav>

        <div class="p-3 border-t border-white/[0.06]">
            @if($plan)
            <a href="{{ route('settings.billing') }}" class="block mb-2 px-3 py-2.5 rounded-xl bg-brand-500/10 border border-brand-500/20 text-xs">
                <span class="text-gray-400">Plan</span> <span class="font-semibold text-white">{{ $plan }}</span>
            </a>
            @endif
            <div class="flex items-center gap-3 px-2 py-1.5">
                <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="w-9 h-9 rounded-xl object-cover shrink-0">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </div>
</aside>
