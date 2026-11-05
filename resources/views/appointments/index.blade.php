@extends('layouts.app')
@section('title', 'Bookings')
@section('page-title', 'Bookings')
@section('content')
@php
    $badge = [
        'pending'   => 'bg-amber-500/15 text-amber-400',
        'confirmed' => 'bg-emerald-500/15 text-emerald-400',
        'completed' => 'bg-brand-500/15 text-brand-400',
        'cancelled' => 'bg-white/5 text-gray-400',
        'no_show'   => 'bg-rose-500/15 text-rose-400',
    ];
    $field = 'bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500';
@endphp

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([['Today', $stats['today'], 'Booked for today'], ['Next 7 days', $stats['week'], 'Pending and confirmed'], ['Awaiting confirmation', $stats['pending'], 'Need your answer'], ['No-show rate', $stats['no_show'] . '%', 'Of finished bookings']] as [$label, $value, $hint])
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $label }}</p>
        <p class="mt-2 text-3xl font-extrabold text-white">{{ $value }}</p>
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    </div>
    @endforeach
</div>

{{-- Booking setup per widget --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-semibold text-white">Booking setup</p>
            <p class="text-sm text-gray-400">Choose services, opening hours and rules for each widget. Visitors book from the calendar button in the chat.</p>
        </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        @forelse($widgets as $w)
            @php $on = (bool) ($w->booking['enabled'] ?? false); @endphp
            <a href="{{ route('widgets.booking', $w) }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-white/10 hover:border-brand-500/50 text-sm text-gray-200 transition">
                <span class="w-2 h-2 rounded-full {{ $on ? 'bg-emerald-400' : 'bg-gray-500' }}"></span>
                {{ $w->name }} <span class="text-xs text-gray-500">{{ $on ? 'On' : 'Off' }}</span>
            </a>
        @empty
            <a href="{{ route('widgets.create') }}" class="text-sm text-brand-400">Create a widget first →</a>
        @endforelse
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
    <div class="flex p-1 rounded-xl bg-white/5 border border-white/10">
        @foreach(['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['view' => $key, 'page' => null]) }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold {{ $view === $key ? 'bg-brand-500 text-white' : 'text-gray-400 hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </div>
    <input type="hidden" name="view" value="{{ $view }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or email" class="{{ $field }} w-56">
    <select name="status" class="{{ $field }}" onchange="this.form.submit()">
        <option value="">Any status</option>
        @foreach(\App\Models\Appointment::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
    </select>
    @if($widgets->count() > 1)
    <select name="widget" class="{{ $field }}" onchange="this.form.submit()">
        <option value="">All widgets</option>
        @foreach($widgets as $w)<option value="{{ $w->id }}" @selected(request('widget') == $w->id)>{{ $w->name }}</option>@endforeach
    </select>
    @endif
</form>

{{-- Agenda --}}
@forelse($days as $date => $items)
    @php $d = \Carbon\Carbon::parse($date); @endphp
    <div class="mb-6">
        <p class="mb-2 text-sm font-bold text-white">
            {{ $d->isToday() ? 'Today' : ($d->isTomorrow() ? 'Tomorrow' : $d->format('l')) }}
            <span class="font-normal text-gray-500">· {{ $d->format('j F Y') }}</span>
        </p>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl divide-y divide-white/5">
            @foreach($items as $a)
            <div class="p-4 flex flex-col md:flex-row md:items-center gap-3 md:gap-5" x-data="{ open: false }">
                <div class="md:w-28 shrink-0">
                    <p class="text-lg font-extrabold text-white leading-none">{{ $a->localStart()->format('g:i A') }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $a->duration }} min</p>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-white truncate">{{ $a->name }} <span class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $badge[$a->status] }}">{{ \App\Models\Appointment::STATUSES[$a->status] }}</span></p>
                    <p class="text-sm text-gray-400 truncate">{{ $a->service_name }} · {{ $a->widget->name }} · <a href="mailto:{{ $a->email }}" class="hover:text-white">{{ $a->email }}</a>@if($a->phone) · {{ $a->phone }}@endif</p>
                    @if($a->notes)<p class="mt-1 text-xs text-gray-500 line-clamp-2">“{{ $a->notes }}”</p>@endif
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @if($a->lead_id)<a href="{{ route('leads.show', $a->lead_id) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-300 bg-white/5 hover:bg-white/10">Lead</a>@endif
                    @if($a->conversation_id)<a href="{{ route('conversations.show', $a->conversation_id) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-300 bg-white/5 hover:bg-white/10">Chat</a>@endif
                    @foreach(array_filter([
                        'confirmed' => $a->status === 'pending' ? 'Confirm' : null,
                        'completed' => in_array($a->status, ['confirmed', 'no_show']) && $a->starts_at->isPast() ? 'Completed' : null,
                        'no_show'   => $a->status === 'confirmed' && $a->starts_at->isPast() ? 'No-show' : null,
                        'cancelled' => in_array($a->status, \App\Models\Appointment::ACTIVE) ? 'Cancel' : null,
                    ]) as $status => $label)
                    <form method="POST" action="{{ route('appointments.update', $a) }}" @if($status === 'cancelled') onsubmit="return confirm('Cancel this booking? {{ $a->name }} will get an email.')" @endif>
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status }}">
                        <button class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $status === 'confirmed' ? 'bg-brand-500 text-white hover:bg-brand-600' : ($status === 'cancelled' ? 'text-rose-400 hover:bg-rose-500/10' : 'text-gray-300 bg-white/5 hover:bg-white/10') }}">{{ $label }}</button>
                    </form>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-12 text-center">
        <p class="text-4xl">📅</p>
        <p class="mt-3 font-semibold text-white">No bookings {{ $view === 'past' ? 'in the past' : 'yet' }}</p>
        <p class="mt-1 text-sm text-gray-400">Turn on booking for a widget above, and visitors can pick a time straight from the chat.</p>
    </div>
@endforelse

{{ $appointments->links() }}
@endsection
