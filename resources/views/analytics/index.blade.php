@extends('layouts.app')
@section('title', 'Analytics')
@section('page-title', 'Analytics')
@section('content')

{{-- Filters --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-2">
        @foreach(['7' => '7 Days', '30' => '30 Days', '90' => '90 Days'] as $val => $label)
        <a href="?period={{ $val }}&widget_id={{ $widgetId }}" class="px-4 py-2 text-xs font-medium rounded-xl transition {{ $period == $val ? 'bg-brand-500/20 text-brand-400 border border-brand-500/20' : 'bg-white/5 text-gray-400 hover:text-white hover:bg-white/10 border border-transparent' }}">{{ $label }}</a>
        @endforeach
    </div>
    <select onchange="window.location='?period={{ $period }}&widget_id='+this.value" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
        <option value="">All Widgets</option>
        @foreach($widgets as $w)<option value="{{ $w->id }}" {{ $widgetId == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>@endforeach
    </select>
</div>

{{-- Summary Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3 mb-6">
    @foreach([
        ['Conversations', $totalConversations, 'brand'],
        ['Messages',      $totalMessages,      'emerald'],
        ['Leads',         $totalLeads,         'accent'],
        ['Visitors',      $totalVisitors,      'sky'],
        ['Resolution',    round($avgResolution).'%', 'green'],
        ['AI Accuracy',   $aiAccuracy.'%',     'amber'],
        ['Avg Response',  round($avgResponseTime).'ms', 'rose'],
        ['Fallbacks',     $totalFallbacks,     'gray'],
    ] as [$label, $value, $color])
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-3 text-center hover:border-{{ $color }}-500/20 transition">
        <p class="text-lg font-bold text-white">{{ is_numeric($value) ? number_format($value) : $value }}</p>
        <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5">{{ $label }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    {{-- Main Performance Chart --}}
    <div class="xl:col-span-2 bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">Performance Over Time</h3>
        <div class="relative" style="height:260px"><canvas id="analyticsChart"></canvas></div>
    </div>

    {{-- Conversion Funnel --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">Conversion Funnel</h3>
        <div class="space-y-3">
            @foreach(['visitors' => ['Visitors','sky'], 'conversations' => ['Conversations','brand'], 'leads' => ['Leads','accent'], 'hot_leads' => ['Hot Leads','rose'], 'won' => ['Won Deals','emerald']] as $key => [$label, $color])
            @php $maxFunnel = max($funnel['visitors'], 1); $pct = round(($funnel[$key] / $maxFunnel) * 100); @endphp
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-400">{{ $label }}</span>
                    <span class="text-white font-medium">{{ number_format($funnel[$key]) }}
                        @if($key !== 'visitors' && $funnel['visitors'] > 0)
                        <span class="text-gray-500 font-normal">({{ round(($funnel[$key] / $funnel['visitors']) * 100, 1) }}%)</span>
                        @endif
                    </span>
                </div>
                <div class="h-2.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-{{ $color }}-600 to-{{ $color }}-400 rounded-full transition-all duration-1000" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Sentiment + CSAT Row --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    {{-- Sentiment Trend --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-white">Sentiment Trend</h3>
                <p class="text-xs text-gray-500 mt-0.5">Average visitor message sentiment per day</p>
            </div>
            @if($overallSentiment !== null)
            @php
                $sc = $overallSentiment >= 0.15 ? 'emerald' : ($overallSentiment >= -0.15 ? 'amber' : 'rose');
                $sl = $overallSentiment >= 0.5 ? 'Very Positive' : ($overallSentiment >= 0.15 ? 'Positive' : ($overallSentiment >= -0.15 ? 'Neutral' : ($overallSentiment >= -0.5 ? 'Negative' : 'Very Negative')));
            @endphp
            <div class="text-right">
                <p class="text-2xl font-bold text-{{ $sc }}-400">{{ $overallSentiment > 0 ? '+' : '' }}{{ $overallSentiment }}</p>
                <p class="text-xs text-{{ $sc }}-400">{{ $sl }}</p>
            </div>
            @endif
        </div>
        <div class="relative" style="height:180px"><canvas id="sentimentChart"></canvas></div>
        @if(array_sum($sentimentBuckets) > 0)
        <div class="mt-4 grid grid-cols-5 gap-1 text-center">
            @foreach(['very_positive' => ['Very+','emerald'], 'positive' => ['Pos.','teal'], 'neutral' => ['Neutral','amber'], 'negative' => ['Neg.','orange'], 'very_negative' => ['Very-','rose']] as $key => [$label, $color])
            <div class="bg-{{ $color }}-500/10 rounded-lg p-2">
                <p class="text-xs font-bold text-{{ $color }}-400">{{ number_format($sentimentBuckets[$key] ?? 0) }}</p>
                <p class="text-[9px] text-gray-500">{{ $label }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- CSAT Trend --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-white">CSAT Scores</h3>
                <p class="text-xs text-gray-500 mt-0.5">Customer satisfaction ratings over time</p>
            </div>
            @if($avgCsat !== null)
            <div class="text-right">
                <p class="text-2xl font-bold text-amber-400">{{ $avgCsat }}<span class="text-base font-normal text-gray-500">/5</span></p>
                <div class="flex items-center gap-0.5 justify-end mt-0.5">
                    @for($s = 1; $s <= 5; $s++)
                    <span class="text-xs {{ $s <= round($avgCsat) ? 'text-amber-400' : 'text-gray-700' }}">★</span>
                    @endfor
                </div>
                <p class="text-[10px] text-gray-500 mt-0.5">{{ number_format($totalCsatResponses) }} ratings</p>
            </div>
            @else
            <span class="text-xs text-gray-500">No ratings yet</span>
            @endif
        </div>
        <div class="relative" style="height:180px"><canvas id="csatChart"></canvas></div>
    </div>
</div>

{{-- Device, Language, Heatmap Row --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    {{-- Device Distribution --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">Device Distribution</h3>
        @if(!empty($devices))
        <div class="flex justify-center mb-4" style="height: 160px">
            <canvas id="deviceChart"></canvas>
        </div>
        <div class="space-y-2">
            @php $deviceTotal = array_sum($devices); @endphp
            @foreach(array_slice($devices, 0, 5, true) as $device => $count)
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-400 capitalize">{{ $device ?: 'Unknown' }}</span>
                <div class="flex items-center gap-2">
                    <div class="w-20 h-1.5 bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-brand-500 rounded-full" style="width: {{ $deviceTotal > 0 ? round(($count/$deviceTotal)*100) : 0 }}%"></div>
                    </div>
                    <span class="text-white font-medium w-8 text-right">{{ $deviceTotal > 0 ? round(($count/$deviceTotal)*100) : 0 }}%</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="flex flex-col items-center justify-center h-40 text-center">
            <p class="text-3xl mb-2">📱</p>
            <p class="text-sm text-gray-500">No device data yet</p>
        </div>
        @endif
    </div>

    {{-- Language Distribution --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">Languages</h3>
        @if(!empty($languages))
        @php $langTotal = array_sum($languages); $langColors = ['#ff6a2b','#10b981','#f59e0b','#ef4444','#6c97d6','#8fb4e3','#84cc16','#f97316','#ec4899','#14b8a6']; $li = 0; @endphp
        <div class="flex justify-center mb-4" style="height: 160px">
            <canvas id="langChart"></canvas>
        </div>
        <div class="space-y-2">
            @foreach(array_slice($languages, 0, 6, true) as $lang => $count)
            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $langColors[$li++ % count($langColors)] }}"></span>
                    <span class="text-gray-400 uppercase">{{ $lang ?: 'unknown' }}</span>
                </div>
                <span class="text-white font-medium">{{ $langTotal > 0 ? round(($count/$langTotal)*100) : 0 }}%</span>
            </div>
            @endforeach
        </div>
        @else
        <div class="flex flex-col items-center justify-center h-40 text-center">
            <p class="text-3xl mb-2">🌐</p>
            <p class="text-sm text-gray-500">No language data yet</p>
            <p class="text-xs text-gray-600 mt-1">Enable auto-language detection</p>
        </div>
        @endif
    </div>

    {{-- Top Topics --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">Top Topics</h3>
        @if($topTopics->isNotEmpty())
        @php $maxTopic = $topTopics->first(); @endphp
        <div class="space-y-2.5">
            @foreach($topTopics as $topic => $count)
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-400 capitalize truncate pr-2">{{ $topic }}</span>
                    <span class="text-white font-medium shrink-0">{{ number_format($count) }}</span>
                </div>
                <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-brand-500 to-accent-500 rounded-full" style="width: {{ $maxTopic > 0 ? round(($count/$maxTopic)*100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="flex flex-col items-center justify-center h-40 text-center">
            <p class="text-3xl mb-2">💬</p>
            <p class="text-sm text-gray-500">No topic data yet</p>
        </div>
        @endif
    </div>
</div>

{{-- Channel Analytics --}}
@if($channelStats->isNotEmpty())
@php
$channelMeta = [
    'whatsapp'  => ['name' => 'WhatsApp',   'emoji' => '💬', 'color' => 'emerald'],
    'telegram'  => ['name' => 'Telegram',   'emoji' => '✈️',  'color' => 'sky'],
    'facebook'  => ['name' => 'Facebook',   'emoji' => '📘', 'color' => 'blue'],
    'instagram' => ['name' => 'Instagram',  'emoji' => '📸', 'color' => 'pink'],
    'slack'     => ['name' => 'Slack',      'emoji' => '💼', 'color' => 'accent'],
    'discord'   => ['name' => 'Discord',    'emoji' => '🎮', 'color' => 'brand'],
    'sms'       => ['name' => 'SMS',        'emoji' => '📱', 'color' => 'rose'],
    'twitter'   => ['name' => 'Twitter/X',  'emoji' => '🐦', 'color' => 'gray'],
    'email'     => ['name' => 'Email',      'emoji' => '📧', 'color' => 'accent'],
    'web'       => ['name' => 'Web Widget', 'emoji' => '🌐', 'color' => 'amber'],
];
$channelTotal = max($channelStats->sum(fn($s) => $s['sent'] + $s['received']), 1);
@endphp
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
    <div class="flex items-start justify-between mb-5">
        <div>
            <h3 class="text-base font-semibold text-white">Channel Breakdown</h3>
            <p class="text-xs text-gray-500 mt-0.5">Messages across all connected channels</p>
        </div>
        <div class="flex items-center gap-4 text-xs text-gray-400">
            <span>{{ $activeChannels }} active channel{{ $activeChannels !== 1 ? 's' : '' }}</span>
            <span class="text-white font-semibold">{{ number_format($totalChannelMessages) }} total messages</span>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        @foreach($channelStats as $type => $stat)
        @php $meta = $channelMeta[$type] ?? ['name' => $type, 'emoji' => '📡', 'color' => 'gray']; @endphp
        <div class="bg-white/2 border border-white/5 rounded-xl p-4 hover:border-white/10 transition">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-{{ $meta['color'] }}-500/10 rounded-xl flex items-center justify-center text-xl shrink-0">
                    {{ $meta['emoji'] }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-white text-sm">{{ $meta['name'] }}</p>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium {{ $stat['active'] > 0 ? 'bg-emerald-500/15 text-emerald-400' : 'bg-gray-500/15 text-gray-500' }}">
                        {{ $stat['active'] > 0 ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <span class="text-lg font-bold text-white">{{ number_format($stat['sent'] + $stat['received']) }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs mb-3">
                <div>
                    <p class="text-gray-500">Sent</p>
                    <p class="text-white font-semibold">{{ number_format($stat['sent']) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Received</p>
                    <p class="text-white font-semibold">{{ number_format($stat['received']) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Today</p>
                    <p class="text-white font-semibold">{{ number_format($stat['today']) }}</p>
                </div>
            </div>
            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                @php $pct = round((($stat['sent'] + $stat['received']) / $channelTotal) * 100); @endphp
                <div class="h-full bg-{{ $meta['color'] }}-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
            </div>
            @if($stat['last_active'])
            <p class="text-[10px] text-gray-600 mt-2">Last active: {{ \Carbon\Carbon::parse($stat['last_active'])->diffForHumans() }}</p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Channel volume bar chart --}}
    @if($totalChannelMessages > 0)
    <div class="relative" style="height: 180px">
        <canvas id="channelChart"></canvas>
    </div>
    @endif
</div>
@endif

{{-- Conversation Heatmap --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-base font-semibold text-white">Conversation Heatmap</h3>
            <p class="text-xs text-gray-500 mt-0.5">Activity by hour of day and day of week</p>
        </div>
        <div class="flex items-center gap-2 text-[10px] text-gray-500">
            <span>Low</span>
            <div class="flex gap-0.5">
                @foreach(['bg-brand-900/30','bg-brand-700/40','bg-brand-600/60','bg-brand-500/80','bg-brand-400'] as $cls)
                <div class="w-4 h-4 rounded {{ $cls }}"></div>
                @endforeach
            </div>
            <span>High</span>
        </div>
    </div>
    @php
        $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    @endphp
    <div class="overflow-x-auto">
        <div class="min-w-max">
            {{-- Hour labels --}}
            <div class="flex items-center mb-1 ml-10">
                @for($h = 0; $h < 24; $h++)
                <div class="w-7 text-center text-[9px] text-gray-600">{{ $h % 6 === 0 ? $h.':00' : '' }}</div>
                @endfor
            </div>
            @foreach($days as $di => $day)
            <div class="flex items-center mb-0.5">
                <div class="w-10 text-[10px] text-gray-500 text-right pr-2 shrink-0">{{ $day }}</div>
                @for($h = 0; $h < 24; $h++)
                @php
                    $val = $heatmap[$di][$h] ?? 0;
                    $pct = $heatmapMax > 0 ? $val / $heatmapMax : 0;
                    $opacity = $pct < 0.01 ? '10' : ($pct < 0.25 ? '30' : ($pct < 0.5 ? '50' : ($pct < 0.75 ? '70' : '90')));
                @endphp
                <div class="w-7 h-5 rounded-sm mx-0.5 bg-brand-500/{{ $opacity }} hover:ring-1 hover:ring-brand-400/50 transition cursor-default"
                    title="{{ $day }} {{ $h }}:00 — {{ $val }} conversation{{ $val !== 1 ? 's' : '' }}"></div>
                @endfor
            </div>
            @endforeach
        </div>
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
        <span class="ml-auto text-xs text-gray-500">{{ $insights->count() }} insights</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach($insights as $insight)
        <div class="bg-white/2 border border-white/5 rounded-xl p-4 hover:border-white/10 transition">
            <div class="flex items-start gap-3">
                <span class="text-xl shrink-0">{{ $insight->sentiment === 'positive' ? '📈' : ($insight->sentiment === 'negative' ? '📉' : ($insight->sentiment === 'actionable' ? '💡' : '📊')) }}</span>
                <div>
                    <p class="text-sm text-gray-200 leading-relaxed">{{ $insight->insight }}</p>
                    <p class="text-[10px] text-gray-500 mt-2">Importance: {{ $insight->importance }}/10 · {{ $insight->created_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@php
$deviceChartData   = array_values(array_slice($devices ?? [], 0, 5));
$deviceChartLabels = array_keys(array_slice($devices ?? [], 0, 5, true));
$langChartData     = array_values(array_slice($languages ?? [], 0, 8));
$langChartLabels   = array_keys(array_slice($languages ?? [], 0, 8, true));

// Channel chart globals (safe fallback when no channels configured)
$chLabels = $channelStats->isNotEmpty()
    ? $channelStats->keys()->map(fn($t) => ['whatsapp'=>'WhatsApp','telegram'=>'Telegram','facebook'=>'Facebook','instagram'=>'Instagram','slack'=>'Slack','discord'=>'Discord','sms'=>'SMS','twitter'=>'Twitter/X','email'=>'Email','web'=>'Web Widget'][$t] ?? $t)->values()->toArray()
    : [];
$chSent  = $channelStats->isNotEmpty() ? $channelStats->values()->map(fn($s) => $s['sent'])->toArray() : [];
$chRecv  = $channelStats->isNotEmpty() ? $channelStats->values()->map(fn($s) => $s['received'])->toArray() : [];
@endphp
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartDefaults = {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#9ca3af', usePointStyle: true, padding: 16, font: { size: 11 } } } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#6b7280', font: { size: 10 }, maxTicksLimit: 8 } },
            y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#6b7280', font: { size: 10 } }, beginAtZero: true }
        },
        interaction: { mode: 'index', intersect: false }
    };

    // Performance chart
    new Chart(document.getElementById('analyticsChart'), {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: [
                { label: 'Conversations', data: @json($conversations), borderColor: '#ff6a2b', backgroundColor: 'rgba(255, 106, 43,0.08)',  fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
                { label: 'Messages',      data: @json($messages),      borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.04)',   fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
                { label: 'Leads',         data: @json($leads),         borderColor: '#6c97d6', backgroundColor: 'rgba(8, 145, 178,0.04)',   fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
                { label: 'Visitors',      data: @json($visitors),      borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.04)',   fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
            ]
        },
        options: chartDefaults
    });

    // Sentiment trend chart
    const sentimentData = @json($sentimentData);
    new Chart(document.getElementById('sentimentChart'), {
        type: 'line',
        data: {
            labels: @json($sentimentLabels),
            datasets: [{
                label: 'Avg Sentiment',
                data: sentimentData,
                borderColor: '#10b981',
                backgroundColor: function(ctx) {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 180);
                    g.addColorStop(0, 'rgba(16,185,129,0.2)'); g.addColorStop(1, 'rgba(16,185,129,0)');
                    return g;
                },
                fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4,
                spanGaps: true
            }]
        },
        options: {
            ...chartDefaults,
            plugins: { legend: { display: false } },
            scales: {
                x: chartDefaults.scales.x,
                y: { ...chartDefaults.scales.y, min: -1, max: 1, ticks: { color: '#6b7280', font: { size: 10 }, callback: v => v > 0 ? '+'+v : v } }
            }
        }
    });

    // CSAT chart
    new Chart(document.getElementById('csatChart'), {
        type: 'bar',
        data: {
            labels: @json($csatLabels),
            datasets: [{
                label: 'Avg CSAT',
                data: @json($csatData),
                backgroundColor: 'rgba(245,158,11,0.2)',
                borderColor: '#f59e0b',
                borderWidth: 1.5, borderRadius: 4,
                spanGaps: true
            }]
        },
        options: {
            ...chartDefaults,
            plugins: { legend: { display: false } },
            scales: {
                x: chartDefaults.scales.x,
                y: { ...chartDefaults.scales.y, min: 0, max: 5, ticks: { color: '#6b7280', font: { size: 10 }, stepSize: 1 } }
            }
        }
    });

    // Device chart
    const deviceEl = document.getElementById('deviceChart');
    if (deviceEl) {
        const dData = @json($deviceChartData);
        const dLabels = @json($deviceChartLabels);
        new Chart(deviceEl, {
            type: 'doughnut',
            data: { labels: dLabels.map(l => l||'Unknown'), datasets: [{ data: dData, backgroundColor: ['rgba(255, 106, 43,0.8)','rgba(16,185,129,0.8)','rgba(245,158,11,0.8)','rgba(239,68,68,0.8)','rgba(8, 145, 178,0.8)'], borderColor: 'transparent', hoverOffset: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
        });
    }

    // Channel chart
    const channelEl = document.getElementById('channelChart');
    if (channelEl) {
        const chLabels = @json($chLabels ?? []);
        const chSent   = @json($chSent ?? []);
        const chRecv   = @json($chRecv ?? []);
        new Chart(channelEl, {
            type: 'bar',
            data: {
                labels: chLabels,
                datasets: [
                    { label: 'Sent',     data: chSent, backgroundColor: 'rgba(255, 106, 43,0.7)',  borderColor: '#ff6a2b', borderWidth: 1, borderRadius: 4 },
                    { label: 'Received', data: chRecv, backgroundColor: 'rgba(16,185,129,0.7)', borderColor: '#10b981', borderWidth: 1, borderRadius: 4 },
                ]
            },
            options: {
                ...chartDefaults,
                plugins: { legend: { labels: { color: '#9ca3af', font: { size: 11 }, usePointStyle: true } } },
                scales: {
                    x: { ...chartDefaults.scales.x, stacked: false },
                    y: { ...chartDefaults.scales.y, stacked: false },
                }
            }
        });
    }

    // Language chart
    const langEl = document.getElementById('langChart');
    if (langEl) {
        const lData   = @json($langChartData);
        const lLabels = @json($langChartLabels);
        new Chart(langEl, {
            type: 'doughnut',
            data: { labels: lLabels.map(l => (l||'??').toUpperCase()), datasets: [{ data: lData, backgroundColor: ['rgba(255, 106, 43,0.8)','rgba(16,185,129,0.8)','rgba(245,158,11,0.8)','rgba(239,68,68,0.8)','rgba(8, 145, 178,0.8)','rgba(6,182,212,0.8)','rgba(132,204,22,0.8)','rgba(249,115,22,0.8)'], borderColor: 'transparent', hoverOffset: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
        });
    }
});
</script>
@endpush
@endsection
