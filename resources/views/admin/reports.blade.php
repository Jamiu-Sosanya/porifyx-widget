@extends('layouts.admin')
@section('title', 'Admin: Reports')
@section('page-title', 'Revenue & Analytics Reports')
@section('content')

{{-- Range selector --}}
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Showing data for the last <strong class="text-white">{{ $range }}</strong> days</p>
    <div class="flex items-center gap-2">
        @foreach([7 => '7D', 30 => '30D', 90 => '90D', 365 => '1Y'] as $days => $label)
        <a href="?range={{ $days }}"
           class="px-3.5 py-1.5 text-xs font-medium rounded-lg transition {{ $range == $days ? 'bg-brand-600 text-white' : 'bg-white/5 text-gray-400 hover:text-white hover:bg-white/10' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

{{-- Key metrics grid --}}
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 mb-6">
    @foreach([
        ['MRR',              '$'.number_format($metrics['mrr'], 0),      'emerald', 'Monthly Recurring Revenue'],
        ['ARR',              '$'.number_format($metrics['arr'], 0),      'accent',  'Annual Run Rate'],
        ['ARPU',             '$'.number_format($metrics['arpu'], 2),     'sky',     'Avg Revenue per User'],
        ['Paying Users',     number_format($metrics['paying_users']),    'brand',  'Active subscribers'],
        ['Churn 30d',        number_format($metrics['churn_30d']),       'rose',    'Cancelled subscriptions'],
        ['New Users',        number_format($metrics['new_users_period']), 'amber',  'In selected period'],
        ['New Convs',        number_format($metrics['new_convs_period']), 'teal',   'In selected period'],
        ['New Leads',        number_format($metrics['new_leads_period']), 'fuchsia','In selected period'],
        ['Trial Users',      number_format($metrics['trial_users']),     'orange',  'Currently on trial'],
        ['Total Users',      number_format($metrics['total_users']),     'gray',    'All time'],
    ] as [$label, $value, $color, $sub])
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-4 hover:border-{{ $color }}-500/20 transition">
        <p class="text-xl font-bold text-white">{{ $value }}</p>
        <p class="text-xs font-medium text-{{ $color }}-400 mt-0.5">{{ $label }}</p>
        <p class="text-[10px] text-gray-600 mt-0.5">{{ $sub }}</p>
    </div>
    @endforeach
</div>

{{-- Charts row 1 --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    {{-- Revenue chart --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-1">Estimated MRR Trend</h3>
        <p class="text-xs text-gray-500 mb-4">Cumulative active subscriptions × avg price</p>
        <canvas id="revenueChart" height="200"></canvas>
    </div>

    {{-- User growth --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-1">User Growth</h3>
        <p class="text-xs text-gray-500 mb-4">New signups vs cumulative total</p>
        <canvas id="userChart" height="200"></canvas>
    </div>
</div>

{{-- Charts row 2 --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    {{-- Conversation volume --}}
    <div class="xl:col-span-2 bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-1">Conversation Volume</h3>
        <p class="text-xs text-gray-500 mb-4">Daily conversation count</p>
        <canvas id="convChart" height="180"></canvas>
    </div>

    {{-- Lead funnel --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Lead Conversion Funnel</h3>
        @php $convCount = max($funnel['conversations'], 1); @endphp
        <div class="space-y-4">
            @foreach([
                ['Conversations', $funnel['conversations'], 100, 'brand'],
                ['Leads Captured', $funnel['leads'], round($funnel['leads']/$convCount*100,1), 'accent'],
                ['Qualified (≥70)', $funnel['qualified'], round($funnel['qualified']/$convCount*100,1), 'emerald'],
            ] as [$label, $count, $pct, $color])
            <div>
                <div class="flex justify-between text-xs mb-1.5">
                    <span class="text-gray-400">{{ $label }}</span>
                    <span class="text-white font-medium">{{ number_format($count) }} <span class="text-gray-600">({{ $pct }}%)</span></span>
                </div>
                <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full bg-{{ $color }}-500/60 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-white/5">
            <p class="text-xs text-gray-500">Lead capture rate:
                <strong class="text-white">{{ $funnel['conversations'] > 0 ? round($funnel['leads']/$funnel['conversations']*100,1) : 0 }}%</strong>
            </p>
        </div>
    </div>
</div>

{{-- Plans revenue + Cohort table --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
    {{-- Plan revenue breakdown --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Revenue by Plan</h3>
        @php $totalMrr = max($metrics['mrr'], 0.01); @endphp
        <div class="space-y-3">
            @forelse($planRevenue as $row)
            <div>
                <div class="flex justify-between text-xs mb-1.5">
                    <span class="text-gray-300 font-medium">{{ $row['plan']?->name ?? 'Unknown' }}</span>
                    <span class="text-white">${{ number_format($row['mrr'], 0) }}/mo
                        <span class="text-gray-600">· {{ $row['count'] }} subs</span>
                    </span>
                </div>
                <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full bg-brand-500/60 rounded-full" style="width: {{ min(round($row['mrr']/$totalMrr*100),100) }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-gray-600 text-sm">No active subscriptions yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Cohort table --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Monthly Signup Cohorts</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left pb-3 text-gray-500 font-semibold">Cohort</th>
                        <th class="text-center pb-3 text-gray-500 font-semibold">Signups</th>
                        <th class="text-center pb-3 text-gray-500 font-semibold">Paying</th>
                        <th class="text-center pb-3 text-gray-500 font-semibold">Conversion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @foreach($cohorts as $cohort)
                    <tr>
                        <td class="py-3 text-gray-300 font-medium">{{ $cohort['month'] }}</td>
                        <td class="py-3 text-center text-white">{{ number_format($cohort['total']) }}</td>
                        <td class="py-3 text-center text-emerald-400 font-medium">{{ number_format($cohort['paying']) }}</td>
                        <td class="py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold
                                {{ $cohort['conversion'] >= 20 ? 'bg-emerald-500/10 text-emerald-400' : ($cohort['conversion'] >= 5 ? 'bg-amber-500/10 text-amber-400' : 'bg-rose-500/10 text-rose-400') }}">
                                {{ $cohort['conversion'] }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartDefaults = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#6b7280', font: { size: 10 } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#6b7280', font: { size: 10 } } },
    }
};

// Revenue chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: @json($revenueChart['labels']),
        datasets: [{
            data: @json($revenueChart['data']),
            borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)',
            borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4,
        }]
    },
    options: { ...chartDefaults, plugins: { ...chartDefaults.plugins }, scales: { ...chartDefaults.scales, y: { ...chartDefaults.scales.y, ticks: { ...chartDefaults.scales.y.ticks, callback: v => '$'+v } } } }
});

// User growth — dual axis
new Chart(document.getElementById('userChart'), {
    type: 'bar',
    data: {
        labels: @json($userGrowth['labels']),
        datasets: [
            {
                label: 'New', data: @json($userGrowth['new']),
                backgroundColor: 'rgba(47, 94, 232,0.5)', borderRadius: 4, yAxisID: 'y',
            },
            {
                label: 'Total', data: @json($userGrowth['cumulative']),
                type: 'line', borderColor: '#0891b2', borderWidth: 2,
                fill: false, tension: 0.4, pointRadius: 0, yAxisID: 'y2',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true, labels: { color: '#9ca3af', font: { size: 10 } } } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#6b7280', font: { size: 10 } } },
            y:  { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#6b7280', font: { size: 10 } }, position: 'left' },
            y2: { ticks: { color: '#0891b2', font: { size: 10 } }, position: 'right', grid: { drawOnChartArea: false } },
        }
    }
});

// Conversation volume
new Chart(document.getElementById('convChart'), {
    type: 'bar',
    data: {
        labels: @json($convChart['labels']),
        datasets: [{
            data: @json($convChart['data']),
            backgroundColor: 'rgba(20,184,166,0.5)', borderRadius: 4,
        }]
    },
    options: chartDefaults
});
</script>
@endpush
@endsection
