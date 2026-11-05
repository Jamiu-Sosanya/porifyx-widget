@extends('layouts.admin')
@section('title', 'Admin: Conversations')
@section('page-title', 'All Conversations')
@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @foreach([
        ['Total',    $stats['total'],    'brand'],
        ['Open',     $stats['open'],     'amber'],
        ['Resolved', $stats['resolved'], 'emerald'],
        ['Today',    $stats['today'],    'sky'],
    ] as [$label, $value, $color])
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-white">{{ number_format($value) }}</p>
        <p class="text-xs text-gray-500 mt-0.5 uppercase tracking-wider">{{ $label }}</p>
    </div>
    @endforeach
</div>

@if(session('success'))
<div class="mb-4 p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-sm text-emerald-400">{{ session('success') }}</div>
@endif

{{-- Filters --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Visitor name, email, widget…"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition">
        </div>
        <div class="w-36">
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
                <option value="">All</option>
                @foreach(['open','resolved','pending','spam'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
        </div>
        <div class="w-36">
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
        </div>
        <button type="submit" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Filter</button>
        @if(request()->hasAny(['search','status','from','to']))
        <a href="{{ route('admin.conversations') }}" class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-400 text-sm rounded-xl transition">Reset</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Visitor</th>
                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Widget / Owner</th>
                    <th class="text-center px-4 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Messages</th>
                    <th class="text-center px-4 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="text-center px-4 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Lead</th>
                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Started</th>
                    <th class="px-5 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($conversations as $conv)
                @php
                    $statusColors = ['open'=>'amber','resolved'=>'emerald','pending'=>'sky','spam'=>'rose','closed'=>'gray'];
                    $sc = $statusColors[$conv->status] ?? 'gray';
                @endphp
                <tr class="hover:bg-white/[0.02] transition group">
                    <td class="px-5 py-4">
                        <p class="text-white font-medium">{{ $conv->visitor_name ?: 'Anonymous' }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $conv->visitor_email ?: $conv->visitor_ip }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-gray-300">{{ $conv->widget?->name ?? '—' }}</p>
                        <p class="text-xs text-gray-600">{{ $conv->widget?->user?->name }}</p>
                    </td>
                    <td class="px-4 py-4 text-center text-white font-medium">{{ $conv->messages_count }}</td>
                    <td class="px-4 py-4 text-center">
                        <span class="px-2.5 py-0.5 text-xs font-semibold bg-{{ $sc }}-500/10 text-{{ $sc }}-400 rounded-full">
                            {{ ucfirst($conv->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        @if($conv->lead)
                        <span class="px-2 py-0.5 text-xs bg-accent-500/10 text-accent-400 rounded-lg">✓ Captured</span>
                        @else
                        <span class="text-gray-700 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-500">
                        {{ $conv->created_at->format('M d, Y') }}<br>
                        <span class="text-gray-700">{{ $conv->created_at->format('g:i A') }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                            <a href="{{ route('conversations.show', $conv) }}"
                               class="p-1.5 rounded-lg bg-white/5 hover:bg-brand-500/20 text-gray-400 hover:text-brand-400 transition" title="View">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            </a>
                            <form method="POST" action="{{ route('admin.conversations.delete', $conv) }}"
                                  onsubmit="return confirm('Delete this conversation?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg bg-white/5 hover:bg-rose-500/20 text-gray-400 hover:text-rose-400 transition" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-16 text-center text-gray-500">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                        No conversations found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($conversations->hasPages())
    <div class="px-5 py-4 border-t border-white/5">{{ $conversations->links() }}</div>
    @endif
</div>

@endsection
