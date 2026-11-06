@extends('layouts.app')
@section('page-title', 'Settings')
@section('content')
@php
    $sections = [
        'Account' => [
            ['settings.profile', 'Profile', 'Name, photo and password', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-8 9a8 8 0 0 1 16 0'],
            ['settings.notifications', 'Notifications', 'Emails and alerts you get', 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0'],
            ['settings.twoFactor', 'Security', 'Two-factor sign-in', 'M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4zm-3 9 2 2 4-4'],
        ],
        'Workspace' => [
            ['settings.team', 'Team', 'Invite people and set roles', 'M17 20v-2a4 4 0 0 0-3-3.87M7 20v-2a4 4 0 0 1 3-3.87M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z'],
            ['settings.billing', 'Billing', 'Plan, invoices and payment', 'M3 8h18M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm2 10h4'],
            ['settings.cannedResponses', 'Canned responses', 'Saved replies for your team', 'M8 9h8m-8 4h5M4 19l2.5-3H19a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v13z'],
        ],
        'Developers' => [
            ['settings.apiKeys', 'API keys', 'Keys for the REST API', 'M15 7a3 3 0 1 1-2.8 4H9l-1.5 1.5L6 11l-1.5 1.5L3 11l2-2h7.2A3 3 0 0 1 15 7z'],
            ['settings.developer', 'Webhooks', 'Send events to your systems', 'm8 9-4 3 4 3m8-6 4 3-4 3M14 5l-4 14'],
        ],
    ];
@endphp
<div class="grid lg:grid-cols-[250px_minmax(0,1fr)] gap-6 items-start">
    <nav class="lg:sticky lg:top-6 bg-gray-900/50 border border-white/5 rounded-2xl p-2 flex lg:block gap-1 overflow-x-auto" aria-label="Settings">
        @foreach($sections as $group => $items)
            <p class="hidden lg:block px-3 pt-3 pb-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-500">{{ $group }}</p>
            @foreach($items as [$route, $label, $hint, $icon])
                @php $on = request()->routeIs($route . '*'); @endphp
                <a href="{{ route($route) }}" @if($on) aria-current="page" @endif
                   class="shrink-0 flex items-center gap-3 px-3 py-2 lg:py-2.5 rounded-xl transition {{ $on ? 'bg-brand-500/10 text-white ring-1 ring-brand-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                    <span class="grid place-items-center w-8 h-8 rounded-lg shrink-0 {{ $on ? 'bg-brand-500/20 text-brand-300' : 'bg-white/5 text-gray-500' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold whitespace-nowrap">{{ $label }}</span>
                        <span class="hidden lg:block text-[11px] text-gray-500 truncate">{{ $hint }}</span>
                    </span>
                </a>
            @endforeach
        @endforeach
    </nav>
    <div class="min-w-0 space-y-6">
        @yield('settings')
    </div>
</div>
@endsection
