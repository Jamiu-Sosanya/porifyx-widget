@extends('layouts.admin')
@section('title', 'Admin: Widgets')
@section('page-title', 'Widget Management')
@section('content')

{{-- Stats bar --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @foreach([
        ['Total Widgets',  $stats['total'],    'brand'],
        ['Active',         $stats['active'],   'emerald'],
        ['Inactive',       $stats['inactive'], 'rose'],
        ['Users w/ Widgets', $stats['users'],  'sky'],
    ] as [$label, $value, $color])
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-white">{{ number_format($value) }}</p>
        <p class="text-xs text-gray-500 mt-0.5 uppercase tracking-wider">{{ $label }}</p>
    </div>
    @endforeach
</div>

{{-- Flash --}}
@if(session('success'))
<div class="mb-4 p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-sm text-emerald-400">{{ session('success') }}</div>
@endif

{{-- Filters --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Widget name, user email…"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition">
        </div>
        <div class="w-36">
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
                <option value="">All Status</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Filter</button>
        @if(request()->hasAny(['search', 'status', 'user_id']))
        <a href="{{ route('admin.widgets') }}" class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white text-sm rounded-xl transition">Reset</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Widget</th>
                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Owner</th>
                    <th class="text-center px-4 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Convs</th>
                    <th class="text-center px-4 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Leads</th>
                    <th class="text-center px-4 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">AI Model</th>
                    <th class="text-center px-4 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="text-left px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Created</th>
                    <th class="px-5 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.04]">
                @forelse($widgets as $widget)
                <tr class="hover:bg-white/[0.02] transition group">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                 style="background: {{ $widget->primary_color ?? '#ff6a2b' }}22; border: 1px solid {{ $widget->primary_color ?? '#ff6a2b' }}44;">
                                <div class="w-3.5 h-3.5 rounded-full" style="background: {{ $widget->primary_color ?? '#ff6a2b' }};"></div>
                            </div>
                            <div>
                                <p class="font-medium text-white">{{ $widget->name }}</p>
                                <p class="text-xs text-gray-500 font-mono mt-0.5">{{ substr($widget->uuid, 0, 8) }}…</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div>
                            <p class="text-gray-300">{{ $widget->user?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-600">{{ $widget->user?->email }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="text-white font-medium">{{ number_format($widget->conversations_count) }}</span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="text-white font-medium">{{ number_format($widget->leads_count) }}</span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="px-2 py-0.5 text-xs bg-brand-500/10 text-brand-400 rounded-lg font-mono">
                            {{ strtoupper($widget->ai_provider ?? 'openai') }}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <form method="POST" action="{{ route('admin.widgets.toggle', $widget) }}">
                            @csrf
                            <button type="submit"
                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg transition {{ $widget->is_active ? 'bg-emerald-500/15 text-emerald-400 hover:bg-rose-500/15 hover:text-rose-400' : 'bg-rose-500/15 text-rose-400 hover:bg-emerald-500/15 hover:text-emerald-400' }}">
                                {{ $widget->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-500">{{ $widget->created_at->format('M d, Y') }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                            <a href="{{ route('admin.users.show', $widget->user_id) }}"
                               class="p-1.5 rounded-lg bg-white/5 hover:bg-brand-500/20 text-gray-400 hover:text-brand-400 transition" title="View Owner">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            </a>
                            <form method="POST" action="{{ route('admin.widgets.delete', $widget) }}"
                                  onsubmit="return confirm('Delete widget {{ addslashes($widget->name) }}? This will delete all conversations and leads.')">
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
                    <td colspan="8" class="px-5 py-16 text-center text-gray-500">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" /></svg>
                        No widgets found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($widgets->hasPages())
    <div class="px-5 py-4 border-t border-white/5">{{ $widgets->links() }}</div>
    @endif
</div>

@endsection
