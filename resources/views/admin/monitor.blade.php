@extends('layouts.admin')
@section('title', 'Widget monitor')
@section('page-title', 'Widget monitor')
@section('content')
@php
    $types = \App\Support\WidgetEvents::TYPES;
    $sel = 'bg-white/5 border border-white/10 rounded-lg px-2.5 py-1.5 text-xs text-white';
@endphp
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5">
    <p class="flex-1 text-sm text-gray-400">Errors, AI provider outages and failed loads from live widgets. Mark each one resolved as you fix it.</p>
    <a href="{{ request()->fullUrl() }}" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">↻ Refresh</a>
</div>

<div class="mb-5 rounded-2xl border p-4 flex items-center gap-3 {{ $stats['total'] ? 'border-amber-500/30 bg-amber-500/10 text-amber-200' : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' }}">
    <span class="text-lg">{{ $stats['total'] ? '⚠️' : '✓' }}</span>
    <p class="text-sm flex-1">{{ $stats['total'] ? $stats['total'] . ' problem(s) in the last 24 hours.' : 'All clear: no widget errors in the last 24 hours.' }}</p>
    @if($stats['open'])
    <form method="POST" action="{{ route('admin.monitor.resolve') }}">@csrf<input type="hidden" name="all" value="1"><button class="px-3 py-1.5 rounded-lg bg-white/10 text-xs font-semibold">Resolve all</button></form>
    @endif
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 mb-5">
    @foreach(['open' => 'Open', 'provider_down' => 'Provider down', 'reply_failed' => 'Stream failed', 'failover' => 'Failover', 'client' => 'Client errors', 'total' => 'Total (24h)'] as $k => $l)
    <div class="rounded-2xl border border-white/5 bg-gray-900/50 p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $l }}</p>
        <p class="mt-2 text-2xl font-bold {{ $stats[$k] ? ($k === 'failover' ? 'text-amber-400' : 'text-white') : 'text-gray-500' }}">{{ $stats[$k] }}</p>
    </div>
    @endforeach
</div>

<form method="GET" class="flex flex-wrap items-center gap-2 mb-4 text-xs text-gray-400">
    Window <select name="window" onchange="this.form.submit()" class="{{ $sel }}">@foreach(['24h' => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days'] as $k => $l)<option value="{{ $k }}" @selected(request('window', '7d') === $k)>{{ $l }}</option>@endforeach</select>
    Type <select name="type" onchange="this.form.submit()" class="{{ $sel }}"><option value="">All types</option>@foreach($types as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
    Severity <select name="severity" onchange="this.form.submit()" class="{{ $sel }}"><option value="">All</option><option value="error" @selected(request('severity') === 'error')>Error</option><option value="warning" @selected(request('severity') === 'warning')>Warning</option></select>
    Status <select name="status" onchange="this.form.submit()" class="{{ $sel }}"><option value="">All</option><option value="open" @selected(request('status') === 'open')>Open</option><option value="resolved" @selected(request('status') === 'resolved')>Resolved</option></select>
</form>

<div class="grid xl:grid-cols-[minmax(0,1fr)_280px] gap-5">
    <form method="POST" action="{{ route('admin.monitor.resolve') }}" class="rounded-2xl border border-white/5 bg-gray-900/50 overflow-x-auto">
        @csrf
        @forelse($events as $e)
        <label class="flex items-start gap-3 px-4 py-3 border-b border-white/5 last:border-0 {{ $e->resolved_at ? 'opacity-50' : '' }}">
            <input type="checkbox" name="ids[]" value="{{ $e->id }}" class="mt-1" @disabled($e->resolved_at)>
            <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded-full font-semibold {{ $e->severity === 'warning' ? 'bg-amber-500/15 text-amber-400' : 'bg-rose-500/15 text-rose-400' }}">{{ $types[$e->type] ?? $e->type }}</span>
                    <span class="text-gray-300">{{ $e->widget_name ?? 'Unknown widget' }}</span>
                    <span class="text-gray-500">{{ \Carbon\Carbon::parse($e->created_at)->diffForHumans() }}</span>
                    @if($e->resolved_at)<span class="text-emerald-400">Resolved</span>@endif
                </span>
                <span class="block mt-1 text-xs text-gray-400 break-words">{{ $e->message }}</span>
                @if($e->page_url)<span class="block text-[11px] text-gray-600 break-all">{{ $e->page_url }}</span>@endif
            </span>
        </label>
        @empty
        <p class="px-4 py-14 text-center text-sm text-gray-500">🛡️ No events match these filters. A quiet monitor is a healthy widget.</p>
        @endforelse
        @if($events->whereNull('resolved_at')->count())
        <div class="p-3 border-t border-white/5"><button class="px-4 py-2 rounded-lg bg-brand-500 text-white text-xs font-semibold">Mark selected resolved</button></div>
        @endif
    </form>
    <aside class="rounded-2xl border border-white/5 bg-gray-900/50 p-4 self-start">
        <p class="text-sm font-semibold text-white">Open by widget</p>
        <ul class="mt-3 space-y-2 text-sm">
            @forelse($byWidget as $w)<li class="flex justify-between gap-2"><span class="truncate text-gray-300">{{ $w->name }}</span><strong class="text-rose-400">{{ $w->c }}</strong></li>
            @empty<li class="text-xs text-gray-500">Nothing open.</li>@endforelse
        </ul>
        <p class="mt-4 text-[11px] text-gray-500">Events are recorded off the chat path, so capturing a failure never slows a working conversation.</p>
    </aside>
</div>
@endsection
