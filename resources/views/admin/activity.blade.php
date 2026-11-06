@extends('layouts.admin')
@section('title', 'Admin: Activity Log')
@section('page-title', 'Platform Activity Log')
@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-2 gap-3 mb-6 max-w-sm">
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-white">{{ number_format($todayCount) }}</p>
        <p class="text-xs text-gray-500 mt-0.5 uppercase tracking-wider">Events Today</p>
    </div>
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-white">{{ number_format($weekCount) }}</p>
        <p class="text-xs text-gray-500 mt-0.5 uppercase tracking-wider">Last 7 Days</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Description, user name, email…"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition">
        </div>
        @if($events->isNotEmpty())
        <div class="w-44">
            <label class="block text-xs text-gray-500 mb-1">Event Type</label>
            <select name="event" class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
                <option value="">All Events</option>
                @foreach($events as $event)
                <option value="{{ $event }}" {{ request('event') === $event ? 'selected' : '' }}>{{ ucfirst($event) }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="w-36">
            <label class="block text-xs text-gray-500 mb-1">From Date</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
        </div>
        <button type="submit" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Filter</button>
        @if(request()->hasAny(['search','event','from']))
        <a href="{{ route('admin.activity') }}" class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-400 text-sm rounded-xl transition">Reset</a>
        @endif
    </form>
</div>

{{-- Activity feed --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    @if($activities->isNotEmpty())
    <div class="divide-y divide-white/[0.04]">
        @foreach($activities as $activity)
        @php
            $eventColors = [
                'created'  => ['bg-emerald-500/10 text-emerald-400', 'emerald'],
                'updated'  => ['bg-accent-500/10 text-accent-400',         'sky'],
                'deleted'  => ['bg-rose-500/10 text-rose-400',       'rose'],
                'login'    => ['bg-brand-500/10 text-brand-400',   'brand'],
                'logout'   => ['bg-gray-500/10 text-gray-400',       'gray'],
            ];
            $eventStyle = $eventColors[$activity->event] ?? ['bg-accent-500/10 text-accent-400', 'accent'];

            $props = json_decode($activity->properties ?? '{}', true);
            $subject = $activity->subject_type ? class_basename($activity->subject_type) : null;
        @endphp
        <div class="px-5 py-4 hover:bg-white/[0.015] transition flex items-start gap-4">
            {{-- Avatar --}}
            <div class="w-8 h-8 rounded-full bg-brand-500/20 border border-brand-500/20 flex items-center justify-center shrink-0 text-xs font-bold text-brand-400 mt-0.5">
                {{ $activity->causer_name ? strtoupper(substr($activity->causer_name, 0, 1)) : '?' }}
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-0.5">
                    {{-- User --}}
                    <span class="text-sm font-medium text-white">
                        {{ $activity->causer_name ?? 'System' }}
                    </span>
                    @if($activity->causer_email)
                    <span class="text-xs text-gray-600">{{ $activity->causer_email }}</span>
                    @endif

                    {{-- Event badge --}}
                    @if($activity->event)
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $eventStyle[0] }}">
                        {{ strtoupper($activity->event) }}
                    </span>
                    @endif

                    {{-- Subject type --}}
                    @if($subject)
                    <span class="px-2 py-0.5 text-[10px] font-mono bg-white/5 text-gray-400 rounded-full">{{ $subject }}</span>
                    @endif
                </div>

                {{-- Description --}}
                <p class="text-sm text-gray-400 leading-relaxed">{{ $activity->description }}</p>

                {{-- Changed properties --}}
                @if(!empty($props['attributes']) || !empty($props['old']))
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach(array_keys($props['attributes'] ?? $props['old'] ?? []) as $attr)
                    @if(!in_array($attr, ['password', 'remember_token', 'two_factor_secret']))
                    <span class="text-[10px] font-mono bg-white/5 text-gray-500 px-2 py-0.5 rounded-md">{{ $attr }}</span>
                    @endif
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Time --}}
            <div class="text-right shrink-0">
                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}</p>
                <p class="text-[10px] text-gray-700 mt-0.5">{{ \Carbon\Carbon::parse($activity->created_at)->format('M d, g:i A') }}</p>
            </div>
        </div>
        @endforeach
    </div>

    @if($activities->hasPages())
    <div class="px-5 py-4 border-t border-white/5">{{ $activities->links() }}</div>
    @endif

    @else
    <div class="py-20 text-center">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <p class="text-gray-500 text-sm">No activity recorded yet.</p>
        <p class="text-gray-600 text-xs mt-1">Activity is logged automatically as users interact with the platform.</p>
    </div>
    @endif
</div>

@endsection
