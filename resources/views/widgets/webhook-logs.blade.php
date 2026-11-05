@extends('layouts.app')
@section('title', 'Webhook Logs — ' . $widget->name)
@section('page-title', 'Webhook Logs')
@section('content')
<div class="max-w-5xl" x-data="{ expandedLog: null }">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('widgets.edit', $widget) }}" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h2 class="text-lg font-semibold text-white">Webhook Event Log</h2>
                <p class="text-xs text-gray-500">{{ $widget->name }} · <span class="font-mono text-brand-400">{{ $widget->webhook_url ?: 'No webhook URL configured' }}</span></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('widgets.testWebhook', $widget) }}">@csrf
                <button type="submit" class="px-3 py-2 text-xs font-medium bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 rounded-xl transition">
                    ▶ Send Test
                </button>
            </form>
            @if($stats['total'] > 0)
            <form method="POST" action="{{ route('widgets.webhookLogs.clear', $widget) }}"
                  onsubmit="return confirm('Clear all {{ $stats['total'] }} webhook logs? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-2 text-xs font-medium bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-xl transition">
                    Clear All
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-4 p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-sm text-emerald-400">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 p-3.5 bg-rose-500/10 border border-rose-500/20 rounded-xl text-sm text-rose-400">{{ session('error') }}</div>
    @endif

    {{-- Stats bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold text-white">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Deliveries</p>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold text-emerald-400">{{ number_format($stats['success']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Successful</p>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold {{ $stats['failed'] > 0 ? 'text-rose-400' : 'text-gray-500' }}">{{ number_format($stats['failed']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Failed</p>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 text-center">
            @php
                $rate = $stats['total'] > 0 ? round(($stats['success'] / $stats['total']) * 100) : 0;
            @endphp
            <p class="text-2xl font-bold {{ $rate >= 95 ? 'text-emerald-400' : ($rate >= 80 ? 'text-amber-400' : 'text-rose-400') }}">{{ $rate }}%</p>
            <p class="text-xs text-gray-500 mt-1">Success Rate</p>
        </div>
    </div>

    {{-- Webhook not configured notice --}}
    @if(!$widget->webhook_enabled || !$widget->webhook_url)
    <div class="bg-amber-500/5 border border-amber-500/15 rounded-2xl p-5 flex items-start gap-4 mb-6">
        <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        <div>
            <p class="text-sm font-medium text-amber-400">Webhooks not configured</p>
            <p class="text-xs text-gray-500 mt-0.5">
                Enable webhooks and set a webhook URL in the
                <a href="{{ route('widgets.edit', $widget) }}#notifications" class="text-brand-400 hover:underline">Notifications tab</a>
                to start receiving delivery logs here.
            </p>
        </div>
    </div>
    @endif

    {{-- Logs table --}}
    @if($logs->isEmpty())
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-12 text-center">
        <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 6 0m-6 0H2.25m11.25 0a3 3 0 0 0 3-3m-3 3a3 3 0 1 1 6 0m0 0H21.75M6.75 14.25v-2.25A2.25 2.25 0 0 1 9 9.75h1.5M6.75 14.25v2.25a2.25 2.25 0 0 0 2.25 2.25h1.5M17.25 14.25v-2.25A2.25 2.25 0 0 0 15 9.75h-1.5M17.25 14.25v2.25a2.25 2.25 0 0 1-2.25 2.25h-1.5m-3 0h3"/>
            </svg>
        </div>
        <p class="text-gray-400 font-medium">No webhook deliveries yet</p>
        <p class="text-sm text-gray-600 mt-1">Send a test delivery above to see logs appear here.</p>
    </div>
    @else
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
        <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Recent Deliveries</p>
            <p class="text-xs text-gray-600">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</p>
        </div>

        <div class="divide-y divide-white/5">
            @foreach($logs as $log)
            <div class="group">
                {{-- Row header --}}
                <div class="flex items-center gap-3 px-5 py-3.5 cursor-pointer hover:bg-white/[0.02] transition"
                     @click="expandedLog = expandedLog === {{ $log->id }} ? null : {{ $log->id }}">

                    {{-- Status indicator --}}
                    <div class="shrink-0">
                        @if($log->status === 'success')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-500/10 text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> {{ $log->response_status ?? '2xx' }}
                        </span>
                        @elseif($log->status === 'failed')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-rose-500/10 text-rose-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> {{ $log->response_status ?? 'ERR' }}
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-500/10 text-amber-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span> Pending
                        </span>
                        @endif
                    </div>

                    {{-- Event name --}}
                    <code class="text-xs text-brand-300 font-mono bg-brand-500/10 px-2 py-0.5 rounded-md shrink-0">{{ $log->event }}</code>

                    {{-- URL truncated --}}
                    <span class="text-xs text-gray-500 truncate flex-1 font-mono" title="{{ $log->url }}">{{ $log->url }}</span>

                    {{-- Duration --}}
                    <span class="text-xs text-gray-600 shrink-0">{{ $log->getFormattedDuration() }}</span>

                    {{-- Attempt --}}
                    @if($log->attempt > 1)
                    <span class="text-[10px] bg-amber-500/10 text-amber-400 px-1.5 py-0.5 rounded-md shrink-0">Attempt {{ $log->attempt }}</span>
                    @endif

                    {{-- Time --}}
                    <span class="text-xs text-gray-600 shrink-0 whitespace-nowrap" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                        {{ $log->created_at->diffForHumans() }}
                    </span>

                    {{-- Expand chevron --}}
                    <svg class="w-4 h-4 text-gray-600 shrink-0 transition-transform duration-200"
                         :class="expandedLog === {{ $log->id }} ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </div>

                {{-- Expanded detail --}}
                <div x-show="expandedLog === {{ $log->id }}"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="px-5 pb-4 border-t border-white/[0.03] bg-black/20">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        {{-- Request payload --}}
                        <div>
                            <p class="text-xs font-medium text-gray-400 mb-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/></svg>
                                Request Payload
                            </p>
                            <pre class="bg-black/40 rounded-xl p-3 text-xs text-gray-300 overflow-auto font-mono max-h-48 whitespace-pre-wrap break-all">{{ $log->payload ? json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—' }}</pre>
                        </div>

                        {{-- Response --}}
                        <div>
                            <p class="text-xs font-medium text-gray-400 mb-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                                Response
                            </p>
                            @if($log->error_message && $log->status === 'failed')
                            <div class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-3 text-xs text-rose-300 font-mono">
                                {{ $log->error_message }}
                            </div>
                            @else
                            <pre class="bg-black/40 rounded-xl p-3 text-xs text-gray-300 overflow-auto font-mono max-h-48 whitespace-pre-wrap break-all">{{ $log->response_body ?: '(empty response body)' }}</pre>
                            @endif
                        </div>
                    </div>

                    {{-- Meta row --}}
                    <div class="flex flex-wrap gap-4 mt-3 text-xs text-gray-500">
                        <span>Delivered: <span class="text-gray-300">{{ $log->delivered_at?->format('M j, Y H:i:s') ?? '—' }}</span></span>
                        <span>Duration: <span class="text-gray-300">{{ $log->getFormattedDuration() }}</span></span>
                        <span>HTTP Status: <span class="text-gray-300">{{ $log->response_status ?? '—' }}</span></span>
                        <span>Attempt: <span class="text-gray-300">{{ $log->attempt }}</span></span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
    @endif
    @endif

</div>
@endsection
