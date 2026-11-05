@extends('layouts.admin')
@section('title', 'Admin: Channels')
@section('page-title', 'Channel Configurations')
@section('content')

@php
$channelMeta = [
    'whatsapp'  => ['name' => 'WhatsApp',       'emoji' => '💬', 'color' => 'emerald'],
    'telegram'  => ['name' => 'Telegram',        'emoji' => '✈️',  'color' => 'sky'],
    'facebook'  => ['name' => 'Facebook',        'emoji' => '📘', 'color' => 'blue'],
    'instagram' => ['name' => 'Instagram',       'emoji' => '📸', 'color' => 'pink'],
    'slack'     => ['name' => 'Slack',            'emoji' => '💼', 'color' => 'accent'],
    'discord'   => ['name' => 'Discord',          'emoji' => '🎮', 'color' => 'brand'],
    'sms'       => ['name' => 'SMS',              'emoji' => '📱', 'color' => 'rose'],
    'twitter'   => ['name' => 'Twitter / X',     'emoji' => '🐦', 'color' => 'gray'],
    'email'     => ['name' => 'Email',            'emoji' => '📧', 'color' => 'accent'],
    'web'       => ['name' => 'Web Widget',       'emoji' => '🌐', 'color' => 'amber'],
];
@endphp

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4">
        <p class="text-xs text-gray-500">Total Channels</p>
        <p class="text-2xl font-bold text-white mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4">
        <p class="text-xs text-gray-500">Active</p>
        <p class="text-2xl font-bold text-emerald-400 mt-1">{{ $stats['active'] }}</p>
    </div>
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4">
        <p class="text-xs text-gray-500">Inactive</p>
        <p class="text-2xl font-bold text-gray-400 mt-1">{{ $stats['inactive'] }}</p>
    </div>
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4">
        <p class="text-xs text-gray-500">Channel Types in Use</p>
        <p class="text-2xl font-bold text-white mt-1">{{ $stats['by_type']->count() }}</p>
    </div>
</div>

{{-- Type breakdown --}}
@if($stats['by_type']->count())
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 mb-6">
    <p class="text-xs font-semibold text-gray-400 mb-3">By Channel Type</p>
    <div class="flex flex-wrap gap-2">
        @foreach($stats['by_type'] as $type => $count)
        <a href="{{ request()->fullUrlWithQuery(['type' => $type]) }}"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium transition
                  {{ request('type') === $type ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-white/5 text-gray-300 border border-white/5 hover:bg-white/10' }}">
            <span>{{ $channelMeta[$type]['emoji'] ?? '📡' }}</span>
            <span>{{ $channelMeta[$type]['name'] ?? $type }}</span>
            <span class="px-1.5 py-0.5 bg-white/10 rounded-full text-[10px]">{{ $count }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Filters & Search --}}
<div class="flex flex-wrap items-center gap-3 mb-4">
    <form method="GET" class="flex items-center gap-2 flex-1 min-w-0">
        <input name="search" value="{{ request('search') }}" placeholder="Search by channel, widget or user…"
               class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-rose-500/50 transition">
        <select name="status" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-rose-500/50 transition">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <select name="type" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-rose-500/50 transition">
            <option value="">All Types</option>
            @foreach($types as $t)
            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $channelMeta[$t]['emoji'] ?? '' }} {{ $channelMeta[$t]['name'] ?? $t }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm rounded-xl transition">Search</button>
        @if(request()->hasAny(['search', 'status', 'type']))
        <a href="{{ route('admin.channels') }}" class="px-3 py-2 text-xs text-gray-400 hover:text-white transition">Clear</a>
        @endif
    </form>
</div>

{{-- Channels Table --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    @if($channels->isEmpty())
    <div class="text-center py-16 text-gray-500">
        <div class="text-4xl mb-3">📡</div>
        <p class="font-medium text-gray-400">No channels found</p>
        <p class="text-sm mt-1">Users connect channels from their widget settings page.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Channel</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Widget / User</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Messages</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Activity</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Connected</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/3">
                @foreach($channels as $channel)
                @php
                    $meta = $channelMeta[$channel->channel_type] ?? ['name' => $channel->channel_type, 'emoji' => '📡', 'color' => 'gray'];
                @endphp
                <tr class="hover:bg-white/2 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-{{ $meta['color'] }}-500/10 flex items-center justify-center text-lg shrink-0">
                                {{ $meta['emoji'] }}
                            </div>
                            <div>
                                <p class="font-medium text-white">{{ $channel->account_name ?: $meta['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $meta['name'] }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-white font-medium truncate max-w-40">{{ $channel->widget->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500 truncate max-w-40">{{ $channel->widget->user->email ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full font-medium
                            {{ $channel->is_enabled ? 'bg-emerald-500/15 text-emerald-400' : 'bg-gray-500/15 text-gray-500' }}">
                            {{ $channel->is_enabled ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">Sent:</span>
                                <span class="text-white">{{ number_format($channel->total_messages_sent) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">Recv:</span>
                                <span class="text-white">{{ number_format($channel->total_messages_received) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">Today:</span>
                                <span class="text-white">{{ $channel->messages_today }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400">
                        {{ $channel->last_message_at ? $channel->last_message_at->diffForHumans() : '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $channel->created_at->format('M j, Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 justify-end">
                            <form method="POST" action="{{ route('admin.channels.toggle', $channel) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2.5 py-1 text-xs rounded-lg font-medium transition
                                    {{ $channel->is_enabled ? 'bg-amber-500/15 text-amber-400 hover:bg-amber-500/25' : 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25' }}">
                                    {{ $channel->is_enabled ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.channels.delete', $channel) }}"
                                  onsubmit="return confirm('Delete this channel configuration?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 text-xs rounded-lg font-medium bg-rose-500/15 text-rose-400 hover:bg-rose-500/25 transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($channels->hasPages())
    <div class="px-4 py-3 border-t border-white/5">
        {{ $channels->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
