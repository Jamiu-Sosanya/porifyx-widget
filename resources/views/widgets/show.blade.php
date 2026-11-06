@extends('layouts.app')
@section('title', $widget->name)
@section('page-title', 'Widget Overview')
@section('content')
<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
        <div>
            <a href="{{ route('widgets.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white mb-3 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                All widgets
            </a>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border border-white/10"
                     style="background-color: {{ $widget->primary_color }}22">
                    <div class="w-5 h-5 rounded-full" style="background-color: {{ $widget->primary_color }}"></div>
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-white">{{ $widget->name }}</h1>
                    <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $widget->uuid }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                    {{ $widget->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-500/10 text-gray-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $widget->is_active ? 'bg-emerald-400' : 'bg-gray-500' }}"></span>
                    {{ $widget->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('widgets.edit', $widget) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-medium text-white transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                Edit
            </a>
            <a href="{{ route('widgets.knowledge.index', $widget) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-medium text-white transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                Knowledge
            </a>
            <a href="{{ route('widgets.embed', $widget) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-500 rounded-xl text-sm font-medium text-white transition shadow-lg shadow-brand-500/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg>
                Embed code
            </a>
        </div>
    </div>

    @include('widgets.partials.hub')

    {{-- KPI Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 mb-8">
        @foreach([
            ['Total Chats',    number_format($widget->total_conversations), 'text-white'],
            ['Active Now',     number_format($stats['conversations_active']), 'text-emerald-400'],
            ['Resolved',       number_format($stats['resolved_total']), 'text-accent-400'],
            ['Messages',       number_format($widget->total_messages), 'text-white'],
            ['Leads',          number_format($widget->total_leads), 'text-amber-400'],
            ['Knowledge Bases',number_format($stats['knowledge_bases']), 'text-accent-400'],
            ['Avg CSAT',       $stats['avg_csat'] !== null ? $stats['avg_csat'].'/5' : '—', 'text-rose-300'],
        ] as [$label, $value, $color])
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4 text-center hover:border-white/10 transition">
            <p class="text-xl font-bold {{ $color }}">{{ $value }}</p>
            <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-1">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    {{-- Main content --}}
    <div class="grid lg:grid-cols-3 gap-6 mb-6">
        {{-- Conversation trend chart --}}
        <div class="lg:col-span-2 bg-gray-900/50 border border-white/5 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-1">
                <h2 class="text-sm font-semibold text-white">Conversation Trend</h2>
                <span class="text-xs text-gray-500">Last 30 days</span>
            </div>
            <p class="text-xs text-gray-600 mb-4">Daily conversations on this widget</p>
            <canvas id="widgetConvChart" height="160"></canvas>
        </div>

        {{-- AI Profile + Integrations --}}
        <div class="space-y-4">
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white mb-3">AI Profile</h3>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500">Assistant name</dt>
                        <dd class="text-white text-right font-medium">{{ $widget->ai_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500">Tone</dt>
                        <dd class="text-white text-right capitalize">{{ $widget->tone }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500">Knowledge bases</dt>
                        <dd class="text-white text-right">{{ $stats['knowledge_bases'] }}</dd>
                    </div>
                    @if($widget->lead_capture_enabled ?? false)
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500">Lead capture</dt>
                        <dd class="text-emerald-400 text-right text-xs font-medium">Enabled</dd>
                    </div>
                    @endif
                </dl>
                <a href="{{ route('widgets.edit', $widget) }}" class="inline-block mt-3 text-xs text-brand-400 hover:text-brand-300 transition">Edit AI settings →</a>
            </div>

            <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white mb-3">Integrations</h3>
                <ul class="space-y-2.5 text-xs">
                    <li class="flex items-center gap-2">
                        @if($widget->webhook_enabled && $widget->webhook_url)
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                        <span class="text-gray-300">Outbound webhook active</span>
                        @else
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-700 shrink-0"></span>
                        <span class="text-gray-600">Webhook off</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2">
                        @if($widget->csat_prompt_enabled ?? false)
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                        <span class="text-gray-300">CSAT prompts enabled</span>
                        @else
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-700 shrink-0"></span>
                        <span class="text-gray-600">CSAT off</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2">
                        @if($widget->human_takeover_enabled ?? false)
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                        <span class="text-gray-300">Human takeover enabled</span>
                        @else
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-700 shrink-0"></span>
                        <span class="text-gray-600">Human takeover off</span>
                        @endif
                    </li>
                </ul>
                <a href="{{ route('widgets.edit', $widget) }}" class="inline-block mt-3 text-xs text-brand-400 hover:text-brand-300 transition">Configure integrations →</a>
            </div>
        </div>
    </div>

    {{-- Recent conversations --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-white">Recent Conversations</h2>
            <a href="{{ route('conversations.index', ['widget_id' => $widget->id]) }}"
               class="text-xs text-brand-400 hover:text-brand-300 transition">View all →</a>
        </div>
        <div class="divide-y divide-white/4">
            @forelse($widget->conversations as $conv)
            @php
                $statusColors = [
                    'ai_active'    => 'bg-brand-500/10 text-brand-400',
                    'human_active' => 'bg-emerald-500/10 text-emerald-400',
                    'open'         => 'bg-accent-500/10 text-accent-400',
                    'resolved'     => 'bg-gray-500/10 text-gray-400',
                    'spam'         => 'bg-rose-500/10 text-rose-400',
                ];
            @endphp
            <a href="{{ route('conversations.show', $conv) }}"
               class="flex items-center gap-4 px-5 py-4 hover:bg-white/1.5 transition">
                <div class="w-9 h-9 rounded-xl bg-linear-to-br from-gray-700 to-gray-800 flex items-center justify-center text-xs font-bold text-white shrink-0">
                    {{ strtoupper(substr($conv->visitor?->name ?? $conv->visitor?->email ?? 'V', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">
                        {{ $conv->visitor?->name ?? $conv->visitor?->email ?? 'Visitor #' . $conv->visitor_id }}
                    </p>
                    <p class="text-xs text-gray-500 truncate">{{ $conv->lastMessage?->content ?? 'No messages yet' }}</p>
                </div>
                <div class="text-right shrink-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusColors[$conv->status] ?? 'bg-gray-500/10 text-gray-400' }}">
                        {{ str_replace('_', ' ', ucfirst($conv->status)) }}
                    </span>
                    <p class="text-[10px] text-gray-600 mt-1">{{ $conv->last_message_at?->diffForHumans(short: true) ?? $conv->created_at->diffForHumans(short: true) }}</p>
                </div>
                @if($conv->csat_score)
                <div class="shrink-0 ml-1">
                    <span class="text-amber-400 text-xs font-semibold">{{ $conv->csat_score }}★</span>
                </div>
                @endif
            </a>
            @empty
            <div class="py-12 text-center">
                <svg class="w-10 h-10 text-gray-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                <p class="text-sm text-gray-500">No conversations yet.</p>
                <p class="text-xs text-gray-600 mt-1">Embed the widget on your site to start collecting chats.</p>
                <a href="{{ route('widgets.embed', $widget) }}" class="inline-block mt-3 text-xs text-brand-400 hover:text-brand-300 transition">Get embed code →</a>
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('widgetConvChart'), {
    type: 'bar',
    data: {
        labels: @json($convChart['labels']),
        datasets: [{
            data: @json($convChart['data']),
            backgroundColor: 'rgba(255, 106, 43,0.45)',
            hoverBackgroundColor: 'rgba(255, 106, 43,0.7)',
            borderRadius: 4,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false }, tooltip: { callbacks: {
            label: ctx => ' ' + ctx.parsed.y + ' conversations'
        }}},
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,0.03)' },
                ticks: { color: '#6b7280', font: { size: 10 }, maxTicksLimit: 10 }
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.03)' },
                ticks: { color: '#6b7280', font: { size: 10 }, precision: 0 },
                beginAtZero: true,
            }
        }
    }
});
</script>
@endpush
@endsection
