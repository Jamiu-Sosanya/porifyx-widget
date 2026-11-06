@extends('layouts.app')
@section('title', 'Conversations')
@section('page-title', 'Conversations')
@section('content')
{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @foreach(['total' => ['Total', 'brand'], 'active' => ['Active', 'emerald'], 'unread' => ['Unread', 'amber'], 'resolved' => ['Resolved', 'accent']] as $key => [$label, $color])
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-white">{{ number_format($stats[$key]) }}</p>
        <p class="text-xs text-gray-500">{{ $label }}</p>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:items-center sm:gap-3 mb-6">
    <a href="{{ route('conversations.export', request()->query()) }}" class="col-span-2 sm:order-last justify-center inline-flex items-center gap-2 px-4 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-medium text-gray-300 hover:text-white transition shrink-0">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
        Export CSV
    </a>
    <form method="GET" class="contents sm:flex sm:flex-wrap sm:items-center sm:gap-3 sm:flex-1 sm:min-w-0">
        <div class="relative col-span-2 sm:flex-1 sm:min-w-[200px]">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            <input name="search" value="{{ request('search') }}" placeholder="Search conversations..." class="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition">
        </div>
        <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto min-w-0 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
            <option value="">All Status</option>
            @foreach(['ai_active' => 'AI Active', 'human_active' => 'Human Active', 'open' => 'Open', 'resolved' => 'Resolved', 'spam' => 'Spam'] as $val => $label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="widget_id" onchange="this.form.submit()" class="w-full sm:w-auto min-w-0 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
            <option value="">All Widgets</option>
            @foreach($widgets as $w)
            <option value="{{ $w->id }}" {{ request('widget_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Conversation List --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    <div class="divide-y divide-white/5">
        @forelse($conversations as $conv)
        <a href="{{ route('conversations.show', $conv) }}" class="flex items-start sm:items-center gap-3 sm:gap-4 p-3.5 sm:p-4 hover:bg-white/[0.02] transition {{ !$conv->is_read ? 'bg-brand-500/[0.02]' : '' }}">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-xs font-bold text-white shrink-0">
                {{ strtoupper(substr($conv->visitor?->name ?? $conv->visitor?->email ?? 'V', 0, 2)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-white truncate {{ !$conv->is_read ? 'font-semibold' : '' }}">
                        {{ $conv->visitor?->name ?? $conv->visitor?->email ?? 'Visitor #' . $conv->visitor_id }}
                    </p>
                    @if($conv->is_starred)<svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>@endif
                    @if(!$conv->is_read)<span class="w-2 h-2 bg-brand-500 rounded-full shrink-0"></span>@endif
                </div>
                <p class="text-xs text-gray-500 truncate">{{ $conv->lastMessage?->content ?? 'No messages' }}</p>
                <p class="sm:hidden mt-1 text-[11px] text-gray-500 truncate">{{ $conv->widget?->name }} · {{ $conv->messages_count }} {{ \Illuminate\Support\Str::plural('message', $conv->messages_count) }}</p>
            </div>
            <div class="text-right shrink-0">
                @php
                $statusColors = ['ai_active' => 'bg-brand-500/10 text-brand-400', 'human_active' => 'bg-emerald-500/10 text-emerald-400', 'open' => 'bg-accent-500/10 text-accent-400', 'resolved' => 'bg-gray-500/10 text-gray-400', 'spam' => 'bg-rose-500/10 text-rose-400'];
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusColors[$conv->status] ?? '' }}">
                    {{ str_replace('_', ' ', ucfirst($conv->status)) }}
                </span>
                <p class="text-[10px] text-gray-600 mt-1">{{ $conv->last_message_at?->diffForHumans(short: true) ?? $conv->created_at->diffForHumans(short: true) }}</p>
                <p class="hidden sm:block text-[10px] text-gray-600">{{ $conv->messages_count }} {{ \Illuminate\Support\Str::plural('message', $conv->messages_count) }} · {{ $conv->widget?->name }}</p>
            </div>
        </a>
        @empty
        <div class="p-12 text-center">
            <svg class="w-10 h-10 text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
            <h3 class="text-sm font-medium text-white mb-1">No conversations yet</h3>
            <p class="text-xs text-gray-500">Conversations will appear here once visitors start chatting with your widgets.</p>
        </div>
        @endforelse
    </div>
</div>
<div class="mt-6">{{ $conversations->withQueryString()->links() }}</div>
@endsection
