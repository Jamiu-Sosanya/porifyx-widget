@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('content')

{{-- Unread Alert Banner --}}
@if($unreadConversations > 0)
<div class="flex items-center gap-3 mb-6 px-4 py-3 bg-amber-500/10 border border-amber-500/20 rounded-2xl" x-data="{ show: true }" x-show="show" x-transition>
    <span class="flex h-2 w-2 relative shrink-0">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
    </span>
    <p class="text-sm text-amber-200 flex-1">
        You have <strong class="text-amber-100">{{ $unreadConversations }} unread conversation{{ $unreadConversations !== 1 ? 's' : '' }}</strong> waiting for a response.
    </p>
    <a href="{{ route('conversations.index') }}" class="text-xs font-medium text-amber-300 hover:text-amber-200 transition whitespace-nowrap">View →</a>
    <button @click="show=false" class="p-1 text-amber-500 hover:text-amber-300 transition shrink-0">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
    </button>
</div>
@endif

{{-- KPI Stats Grid --}}
<div class="grid grid-cols-2 xl:grid-cols-4 mb-8 bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden divide-white/5 [&>*]:border-white/5 [&>*:nth-child(odd)]:border-r xl:[&>*]:border-r xl:[&>*:last-child]:border-r-0 [&>*:nth-child(-n+2)]:border-b xl:[&>*]:border-b-0">
    @php
    $kpiCards = [
        ['label' => 'Total Widgets',   'value' => $totalWidgets,        'change' => null,        'icon' => 'M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42', 'color' => 'brand', 'gradient' => 'from-brand-500/10 to-brand-500/5'],
        ['label' => 'Conversations',   'value' => $periodConversations, 'change' => $convChange, 'icon' => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155', 'color' => 'emerald', 'gradient' => 'from-emerald-500/10 to-emerald-500/5'],
        ['label' => 'Leads Captured',  'value' => $periodLeads,         'change' => $leadChange, 'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z', 'color' => 'accent', 'gradient' => 'from-accent-500/10 to-accent-500/5'],
        ['label' => 'Hot Leads',        'value' => $hotLeads,            'change' => null,        'icon' => 'M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z', 'color' => 'rose', 'gradient' => 'from-rose-500/10 to-rose-500/5'],
    ];
    @endphp
    @foreach($kpiCards as $card)
    <div class="relative p-5 sm:p-6">
        <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-full bg-{{ $card['color'] }}-500/15 grid place-items-center shrink-0">
                <svg class="w-4 h-4 text-{{ $card['color'] }}-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
            </span>
            <p class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 truncate">{{ $card['label'] }}</p>
        </div>
        <div class="mt-4 flex items-end justify-between gap-3">
            <p class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white leading-none">{{ number_format($card['value']) }}</p>
            @if($card['change'] !== null)
            <span class="px-2 py-1 rounded-full text-[11px] font-bold {{ $card['change'] >= 0 ? 'bg-emerald-500/15 text-emerald-400' : 'bg-rose-500/15 text-rose-400' }}" title="vs previous period">{{ $card['change'] >= 0 ? '+' : '−' }}{{ abs($card['change']) }}%</span>
            @endif
        </div>
        <span class="absolute left-6 right-6 bottom-0 h-0.5 rounded-full bg-{{ $card['color'] }}-500/50"></span>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8 xl:items-start">
    {{-- Overview Chart --}}
    <div class="xl:col-span-2 bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-white">Overview</h3>
            <div class="flex items-center gap-2">
                @foreach(['7' => '7d', '30' => '30d', '90' => '90d'] as $val => $label)
                <a href="?period={{ $val }}" class="px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $period == $val ? 'bg-brand-500/20 text-brand-400' : 'text-gray-500 hover:text-white hover:bg-white/5' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="relative" style="height:260px"><canvas id="overviewChart"></canvas></div>
    </div>

    {{-- Right column: Sentiment + Usage --}}
    <div class="flex flex-col gap-4">
        {{-- Sentiment Score Widget --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-white">Avg Sentiment</h3>
                <span class="text-xs text-gray-500">{{ $period }}d</span>
            </div>
            @if($avgSentiment !== null)
            @php $sentimentPct = (($avgSentiment + 1) / 2) * 100; @endphp
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl font-bold
                    {{ $sentimentColor === 'emerald' ? 'text-emerald-400' : ($sentimentColor === 'rose' ? 'text-rose-400' : 'text-amber-400') }}">
                    {{ $avgSentiment > 0 ? '+' : '' }}{{ $avgSentiment }}
                </span>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                    {{ $sentimentColor === 'emerald' ? 'bg-emerald-500/10 text-emerald-400' : ($sentimentColor === 'rose' ? 'bg-rose-500/10 text-rose-400' : 'bg-amber-500/10 text-amber-400') }}">
                    {{ $sentimentLabel }}
                </span>
            </div>
            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden mb-1">
                <div class="h-full rounded-full transition-all duration-700"
                    style="width:{{ $sentimentPct }}%; background: {{ $sentimentColor === 'emerald' ? '#10b981' : ($sentimentColor === 'rose' ? '#f43f5e' : '#f59e0b') }}"></div>
            </div>
            <div class="flex justify-between text-[9px] text-gray-700">
                <span>Neg</span><span>Neutral</span><span>Pos</span>
            </div>
            @else
            <div class="flex items-center gap-3 py-1">
                <span class="text-2xl">😐</span>
                <div>
                    <p class="text-sm text-gray-400 font-medium">No data yet</p>
                    <p class="text-[10px] text-gray-600">Enable sentiment in widget settings</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Usage This Month --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-white mb-4">Usage This Month</h3>
            <div class="space-y-4">
                @php
                $usageItems = [
                    ['label' => 'Messages', 'used' => $messagesUsed, 'limit' => $messagesLimit, 'color' => 'brand'],
                    ['label' => 'Leads',    'used' => $leadsUsed,    'limit' => $leadsLimit,    'color' => 'accent'],
                    ['label' => 'Widgets',  'used' => $totalWidgets,  'limit' => $plan?->widgets_limit ?? 0, 'color' => 'emerald'],
                ];
                @endphp
                @foreach($usageItems as $usage)
                @php
                $pct = $usage['limit'] > 0 ? min(100, ($usage['used'] / $usage['limit']) * 100) : ($usage['limit'] == -1 ? 10 : 0);
                $isUnlimited = $usage['limit'] == -1;
                $isWarning = !$isUnlimited && $pct >= 80;
                @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="text-gray-400">{{ $usage['label'] }}</span>
                        <span class="{{ $isWarning ? 'text-amber-400' : 'text-white' }} font-medium">
                            {{ number_format($usage['used']) }} / {{ $isUnlimited ? '∞' : number_format($usage['limit']) }}
                        </span>
                    </div>
                    @php
                    $barColors = ['brand' => '#ff6a2b', 'accent' => '#6c97d6', 'emerald' => '#10b981'];
                    $barBg = $isWarning ? '#f59e0b' : ($barColors[$usage['color']] ?? '#ff6a2b');
                    @endphp
                    <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700" style="width:{{ $isUnlimited ? 10 : $pct }}%; background:{{ $barBg }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @if($plan)
            <div class="mt-4 pt-4 border-t border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-brand-500/20 rounded-lg flex items-center justify-center">
                        <svg class="w-3 h-3 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>
                    </div>
                    <p class="text-xs font-medium text-white">{{ $plan->name }}</p>
                </div>
                <span class="text-[10px] text-gray-500">{{ $subscription?->isTrialing() ? $subscription->daysLeftOnTrial() . 'd trial' : 'Active' }}</span>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Top Widgets Performance --}}
@if($topWidgets->count() > 0)
<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden mb-8">
    <div class="flex items-center justify-between p-5 border-b border-white/5">
        <h3 class="text-base font-semibold text-white">Top Widgets Performance</h3>
        <a href="{{ route('widgets.index') }}" class="text-xs text-brand-400 hover:text-brand-300 transition">Manage →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="px-5 py-3 text-left text-[11px] font-medium text-gray-500 uppercase tracking-wider">Widget</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider">Conversations</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider">Leads</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider">Conv. Rate</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($topWidgets as $w)
                @php
                    $convRate = $w->period_conversations > 0 ? round(($w->period_leads / $w->period_conversations) * 100, 1) : 0;
                @endphp
                <tr class="hover:bg-white/2 transition">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0" style="background: {{ $w->primary_color ?? '#ff6a2b' }}22">
                                <span style="color: {{ $w->primary_color ?? '#ff6a2b' }}">●</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white">{{ $w->name }}</p>
                                <p class="text-[10px] text-gray-500">{{ $w->ai_provider ?? 'openai' }} · {{ $w->theme ?? 'dark' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right text-sm text-white font-medium">{{ number_format($w->period_conversations) }}</td>
                    <td class="px-5 py-3 text-right text-sm text-accent-400 font-medium">{{ number_format($w->period_leads) }}</td>
                    <td class="px-5 py-3 text-right">
                        <span class="text-sm font-medium {{ $convRate >= 10 ? 'text-emerald-400' : ($convRate >= 5 ? 'text-amber-400' : 'text-gray-400') }}">{{ $convRate }}%</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $w->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-500/10 text-gray-400' }}">
                            <span class="w-1 h-1 rounded-full {{ $w->is_active ? 'bg-emerald-400' : 'bg-gray-500' }}"></span>
                            {{ $w->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('widgets.edit', $w) }}" class="text-xs text-brand-400 hover:text-brand-300 transition">Edit →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Recent Conversations --}}
    <div class="xl:col-span-2 bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-white/5">
            <h3 class="text-base font-semibold text-white">Recent Conversations</h3>
            <a href="{{ route('conversations.index') }}" class="text-xs text-brand-400 hover:text-brand-300 transition">View all →</a>
        </div>
        <div class="divide-y divide-white/5">
            @forelse($recentConversations->take(5) as $conv)
            <a href="{{ route('conversations.show', $conv) }}" class="flex items-center gap-4 p-4 hover:bg-white/2 transition group">
                <div class="w-9 h-9 rounded-xl bg-linear-to-br from-gray-700 to-gray-800 flex items-center justify-center text-xs font-bold text-white shrink-0">
                    {{ strtoupper(substr($conv->visitor?->name ?? $conv->visitor?->email ?? 'V', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate group-hover:text-brand-300 transition">{{ $conv->visitor?->name ?? $conv->visitor?->email ?? 'Visitor #' . $conv->visitor_id }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $conv->lastMessage?->content ?? 'No messages' }}</p>
                </div>
                <div class="text-right shrink-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $conv->status_badge }}">{{ str_replace('_', ' ', $conv->status) }}</span>
                    <p class="text-[10px] text-gray-600 mt-1">{{ $conv->last_message_at?->diffForHumans(short: true) }}</p>
                </div>
            </a>
            @empty
            <div class="p-8 text-center text-sm text-gray-500">No conversations yet. Create a widget to get started!</div>
            @endforelse
        </div>
    </div>

    {{-- Live Activity Feed --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-white/5">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <h3 class="text-base font-semibold text-white">Activity Feed</h3>
            </div>
        </div>
        <div class="divide-y divide-white/5 overflow-y-auto" style="max-height: 360px;">
            @forelse($activityFeed as $event)
            <a href="{{ $event['url'] }}" class="flex items-start gap-3 p-3.5 hover:bg-white/2 transition">
                <div class="w-7 h-7 rounded-lg bg-{{ $event['color'] }}-500/10 flex items-center justify-center shrink-0 mt-0.5">
                    @if($event['icon'] === 'chat')
                    <svg class="w-3.5 h-3.5 text-{{ $event['color'] }}-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                    @else
                    <svg class="w-3.5 h-3.5 text-{{ $event['color'] }}-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-white">{{ $event['title'] }}</p>
                    <p class="text-[11px] text-gray-500 truncate mt-0.5">{{ $event['desc'] }}</p>
                </div>
                <p class="text-[10px] text-gray-600 shrink-0 mt-0.5">{{ $event['time']->diffForHumans(short: true) }}</p>
            </a>
            @empty
            <div class="p-8 text-center text-sm text-gray-500">No activity yet.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Recent Leads + AI Insights --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
    {{-- Recent Leads --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-white/5">
            <h3 class="text-base font-semibold text-white">Recent Leads</h3>
            <a href="{{ route('leads.index') }}" class="text-xs text-brand-400 hover:text-brand-300 transition">View all →</a>
        </div>
        <div class="divide-y divide-white/5">
            @forelse($recentLeads->take(5) as $lead)
            <a href="{{ route('leads.show', $lead) }}" class="flex items-center gap-4 p-4 hover:bg-white/2 transition group">
                <div class="w-9 h-9 rounded-xl bg-linear-to-br from-accent-600/20 to-accent-600/20 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate group-hover:text-accent-300 transition">{{ $lead->name ?? $lead->email ?? 'Unknown' }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $lead->company ?? $lead->service_interest ?? 'No details' }}</p>
                </div>
                <div class="text-right shrink-0">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $lead->temperature_color }}">
                        @if($lead->temperature === 'hot')🔥@elseif($lead->temperature === 'warm')☀️@else❄️@endif
                        {{ ucfirst($lead->temperature) }}
                    </span>
                    <p class="text-[10px] text-gray-600 mt-1">Score: {{ $lead->score }}</p>
                </div>
            </a>
            @empty
            <div class="p-8 text-center text-sm text-gray-500">No leads yet. They'll appear as visitors engage with your widgets.</div>
            @endforelse
        </div>
    </div>

    {{-- AI Insights --}}
    @if($insights->isNotEmpty())
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-6 h-6 bg-brand-500/20 rounded-lg flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
            </div>
            <h3 class="text-base font-semibold text-white">AI Insights</h3>
        </div>
        <div class="space-y-3">
            @foreach($insights as $insight)
            <div class="flex items-start gap-3 p-3 bg-white/2 rounded-xl border border-white/5">
                <span class="text-lg shrink-0 mt-0.5">{{ $insight->sentiment === 'positive' ? '📈' : ($insight->sentiment === 'negative' ? '📉' : ($insight->sentiment === 'actionable' ? '💡' : '📊')) }}</span>
                <div>
                    <p class="text-sm text-gray-200 leading-relaxed">{{ $insight->insight }}</p>
                    <p class="text-[10px] text-gray-500 mt-1">Importance: {{ $insight->importance }}/10</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 flex flex-col items-center justify-center text-center gap-3">
        <div class="w-12 h-12 bg-brand-500/10 rounded-2xl flex items-center justify-center">
            <svg class="w-6 h-6 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-white">No AI Insights Yet</p>
            <p class="text-xs text-gray-500 mt-1">Insights will appear after your widgets have accumulated conversation data.</p>
        </div>
    </div>
    @endif
</div>

{{-- Conversation Summaries --}}
<div class="mt-6 bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b border-white/5">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 bg-accent-500/20 rounded-lg flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.118a1.125 1.125 0 0 1 .865-.501 48.394 48.394 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
            </div>
            <h3 class="text-base font-semibold text-white">Recent Conversation Summaries</h3>
        </div>
        <a href="{{ route('conversations.index') }}" class="text-xs text-brand-400 hover:text-brand-300 transition">View all →</a>
    </div>
    
    @if($conversationSummaries->isNotEmpty())
    <div class="divide-y divide-white/5">
        @foreach($conversationSummaries as $summary)
        <a href="{{ $summary['url'] }}" class="block p-5 hover:bg-white/2 transition group">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1.5">
                        <p class="text-sm font-medium text-white">{{ $summary['visitor_name'] }}</p>
                        <span class="text-[10px] text-gray-500">·</span>
                        <p class="text-[11px] text-gray-500">{{ $summary['widget_name'] }}</p>
                    </div>
                    <p class="text-sm text-gray-300 line-clamp-2 group-hover:text-gray-200 transition">{{ $summary['summary'] }}</p>
                </div>
                <div class="text-right shrink-0">
                    {{-- Sentiment Badge --}}
                    @if($summary['sentiment'] !== null)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $summary['sentiment'] >= 0.15 ? 'bg-emerald-500/10 text-emerald-400' : ($summary['sentiment'] >= -0.15 ? 'bg-amber-500/10 text-amber-400' : 'bg-rose-500/10 text-rose-400') }} mb-2">
                        {{ $summary['sentiment'] >= 0.15 ? '😊' : ($summary['sentiment'] >= -0.15 ? '😐' : '😟') }}
                        {{ number_format($summary['sentiment'], 2) }}
                    </span>
                    @endif
                    
                    {{-- Quality Grade --}}
                    @if($summary['quality_grade'])
                    <div class="inline-block px-2 py-0.5 rounded-lg text-[10px] font-bold ml-2
                        {{ $summary['quality_grade'] === 'A' ? 'bg-emerald-500/20 text-emerald-300' : 
                           ($summary['quality_grade'] === 'B' ? 'bg-accent-500/20 text-accent-300' : 
                            ($summary['quality_grade'] === 'C' ? 'bg-amber-500/20 text-amber-300' : 'bg-rose-500/20 text-rose-300')) }}">
                        Grade {{ $summary['quality_grade'] }}
                    </div>
                    @endif
                </div>
            </div>
            
            {{-- Action Items --}}
            @if($summary['action_items'] && count($summary['action_items']) > 0)
            <div class="mt-3 pt-3 border-t border-white/5">
                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-wider mb-2">Action Items</p>
                <ul class="space-y-1">
                    @foreach(array_slice($summary['action_items'], 0, 2) as $item)
                    <li class="flex items-start gap-2 text-[11px] text-gray-400">
                        <span class="text-accent-400 mt-0.5">→</span>
                        <span class="line-clamp-1">{{ $item }}</span>
                    </li>
                    @endforeach
                    @if(count($summary['action_items']) > 2)
                    <li class="text-[10px] text-gray-600">+{{ count($summary['action_items']) - 2 }} more</li>
                    @endif
                </ul>
            </div>
            @endif
            
            {{-- Metadata --}}
            <div class="flex items-center gap-4 mt-3 pt-3 border-t border-white/5 text-[10px] text-gray-600">
                <span>📊 {{ $summary['message_count'] }} messages</span>
                @if($summary['engagement_score'])
                <span>⚡ Engagement: {{ $summary['engagement_score'] }}/100</span>
                @endif
                <span class="ml-auto">{{ $summary['generated_at']->diffForHumans(short: true) }}</span>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div class="p-8 text-center">
        <div class="w-12 h-12 bg-accent-500/10 rounded-2xl flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.118a1.125 1.125 0 0 1 .865-.501 48.394 48.394 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
        </div>
        <p class="text-sm font-medium text-white">No Conversation Summaries Yet</p>
        <p class="text-xs text-gray-500 mt-1">Summaries will appear as your AI processes completed conversations.</p>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('overviewChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                { label: 'Conversations', data: @json($chartConversations), borderColor: '#ff6a2b', backgroundColor: 'rgba(255, 106, 43,0.08)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5 },
                { label: 'Messages',      data: @json($chartMessages),      borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.04)',  fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5 },
                { label: 'Leads',         data: @json($chartLeads),         borderColor: '#6c97d6', backgroundColor: 'rgba(8, 145, 178,0.04)',  fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top', labels: { color: '#9ca3af', usePointStyle: true, pointStyle: 'circle', padding: 20, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString() } }
            },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#6b7280', font: { size: 10 }, maxTicksLimit: 10 } },
                y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#6b7280', font: { size: 10 } }, beginAtZero: true }
            },
            interaction: { mode: 'index', intersect: false }
        }
    });
});
</script>
@endpush
@endsection
