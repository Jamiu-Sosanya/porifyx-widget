@extends('layouts.app')
@section('title', 'Tickets')
@section('page-title', 'Tickets')
@section('content')
@php
    $pc = ['urgent' => 'bg-rose-500/15 text-rose-400', 'high' => 'bg-amber-500/15 text-amber-400', 'normal' => 'bg-white/5 text-gray-300', 'low' => 'bg-white/5 text-gray-500'];
    $sc = ['open' => 'bg-brand-500/15 text-brand-400', 'pending' => 'bg-accent-500/15 text-accent-300', 'solved' => 'bg-emerald-500/15 text-emerald-400', 'closed' => 'bg-white/5 text-gray-500'];
@endphp
<div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-5">
    <div class="flex flex-wrap gap-1.5">
        @foreach(['active' => 'Active', 'open' => 'Open', 'pending' => 'Waiting', 'solved' => 'Solved', 'closed' => 'Closed'] as $k => $l)
        <a href="{{ route('tickets.index', array_merge(request()->except('page'), ['status' => $k])) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $status === $k ? 'bg-brand-500 text-white' : 'bg-white/5 text-gray-300 hover:bg-white/10' }}">
            {{ $l }}@if($k !== 'active') <span class="opacity-60">{{ $counts[$k] ?? 0 }}</span>@endif
        </a>
        @endforeach
    </div>
    <form method="GET" class="flex gap-2 lg:ml-auto">
        <input type="hidden" name="status" value="{{ $status }}">
        <input name="q" value="{{ request('q') }}" placeholder="Search subject, email or TK-number" class="w-full lg:w-72 bg-white/5 border border-white/10 rounded-xl px-3.5 py-2 text-sm text-white">
    </form>
    <a href="{{ route('tickets.create') }}" class="text-center px-4 py-2 rounded-xl bg-brand-500 text-white text-sm font-semibold">New ticket</a>
</div>

<div class="rounded-2xl border border-white/5 bg-gray-900/50 divide-y divide-white/5">
    @forelse($tickets as $t)
    <a href="{{ route('tickets.show', $t) }}" class="flex items-start sm:items-center gap-3 p-4 hover:bg-white/[0.02]">
        <span class="hidden sm:block w-16 shrink-0 text-xs font-mono text-gray-500">{{ $t->number }}</span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-white truncate">{{ $t->subject }}</p>
            <p class="text-xs text-gray-500 truncate"><span class="sm:hidden font-mono">{{ $t->number }} · </span>{{ $t->requester_name ? $t->requester_name . ' · ' : '' }}{{ $t->requester_email }}{{ $t->assignee ? ' · ' . $t->assignee->name : '' }}</p>
        </div>
        <div class="flex flex-col sm:flex-row items-end sm:items-center gap-1.5 shrink-0">
            @if($t->escalated_at)<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/15 text-rose-400">Escalated</span>@endif
            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $pc[$t->priority] ?? '' }}">{{ \App\Models\Ticket::PRIORITIES[$t->priority] ?? $t->priority }}</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sc[$t->status] ?? '' }}">{{ \App\Models\Ticket::STATUSES[$t->status] ?? $t->status }}</span>
            <span class="text-[11px] text-gray-500 w-16 text-right">{{ $t->updated_at->diffForHumans(short: true) }}</span>
        </div>
    </a>
    @empty
    <div class="p-12 text-center">
        <p class="text-sm text-gray-300">No tickets here.</p>
        <p class="mt-1 text-xs text-gray-500">Create one by hand, or from any conversation with "Create ticket".</p>
    </div>
    @endforelse
</div>
<div class="mt-5">{{ $tickets->links() }}</div>

<form method="POST" action="{{ route('tickets.settings') }}" class="mt-6 rounded-2xl border border-white/5 bg-gray-900/50 p-5 space-y-4 text-sm text-gray-300">
    @csrf @method('PUT')
    <div>
        <h3 class="text-sm font-semibold text-white">Email channel</h3>
        <p class="mt-1 text-xs text-gray-500">Turn emails into tickets. In Mailgun (Routes → forward), Postmark (Inbound webhook) or SendGrid (Inbound Parse), send incoming mail for your support address to this URL:</p>
        <input readonly onclick="this.select()" value="{{ \App\Http\Controllers\Api\InboundEmailController::url(auth()->user()) }}" class="mt-2 w-full bg-black/30 border border-white/10 rounded-lg px-3 py-2 text-xs font-mono text-gray-200">
        <div class="mt-3 grid sm:grid-cols-2 gap-3">
            <label class="block text-xs text-gray-400">Support address customers reply to
                <input type="email" name="inbound_address" value="{{ $settings['inbound_address'] }}" placeholder="support@yourcompany.com" class="mt-1 w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
            </label>
            <label class="flex items-center gap-2 text-xs text-gray-300 sm:mt-6"><input type="checkbox" name="ai_draft" value="1" @checked($settings['ai_draft'])> Let the AI draft a reply (saved as an internal note)</label>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-3">
    Escalate open tickets with no reply after
    <input type="number" name="escalate_hours" min="0" max="720" value="{{ $settings['escalate_hours'] }}" class="w-20 bg-white/5 border border-white/10 rounded-lg px-2.5 py-1.5 text-white">
    hours <span class="text-xs text-gray-500">(0 = off)</span>
    <button class="ml-auto px-4 py-2 rounded-lg bg-brand-500 text-white text-xs font-semibold">Save</button>
    </div>
</form>
@endsection
