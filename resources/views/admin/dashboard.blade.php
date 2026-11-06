@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')
@section('content')
@php
    $pfxChecks = collect(\App\Support\SiteHealth::checks());
    $pfxOpen = \Illuminate\Support\Facades\DB::table('widget_events')->whereNull('resolved_at')->count();
    $pfx24 = \Illuminate\Support\Facades\DB::table('widget_events')->where('created_at', '>=', now()->subDay())->count();
    $pfxPay = \App\Models\ManualPayment::where('status', 'pending')->count();
@endphp
<div class="grid sm:grid-cols-3 gap-3 mb-6">
    <a href="{{ route('admin.monitor') }}" class="rounded-2xl border p-4 {{ $pfxOpen ? 'border-amber-500/30 bg-amber-500/10' : 'border-emerald-500/20 bg-emerald-500/5' }}">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Widget monitor</p>
        <p class="mt-1 text-lg font-bold text-white">{{ $pfxOpen ? $pfxOpen . ' open problem' . ($pfxOpen > 1 ? 's' : '') : 'All clear' }}</p>
        <p class="text-xs text-gray-400">{{ $pfx24 }} event(s) in the last 24 hours →</p>
    </a>
    <a href="{{ route('admin.system') }}" class="rounded-2xl border p-4 {{ $pfxChecks->where(1, 'fail')->count() ? 'border-rose-500/30 bg-rose-500/10' : 'border-white/5 bg-gray-900/50' }}">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Site health</p>
        <p class="mt-1 text-lg font-bold text-white">{{ $pfxChecks->where(1, 'ok')->count() }}/{{ $pfxChecks->count() }} checks passing</p>
        <p class="text-xs text-gray-400">{{ $pfxChecks->where(1, 'fail')->pluck(0)->implode(', ') ?: 'No failures' }} →</p>
    </a>
    <a href="{{ route('admin.payments') }}" class="rounded-2xl border border-white/5 bg-gray-900/50 p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Bank transfers</p>
        <p class="mt-1 text-lg font-bold text-white">{{ $pfxPay }} waiting</p>
        <p class="text-xs text-gray-400">Approve when the money arrives →</p>
    </a>
</div>

{{-- KPI Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3 mb-6">
    @foreach([
        ['Total Users',      $stats['total_users'],          'brand'],
        ['Paying',           $stats['paying_users'],         'accent'],
        ['New (30d)',         $stats['new_users_30d'],        'sky'],
        ['Active Widgets',   $stats['active_widgets'],       'emerald'],
        ['Conversations',    $stats['total_conversations'],  'teal'],
        ['Leads',            $stats['total_leads'],          'amber'],
        ['Churn (30d)',       $stats['churn_last_30d'],       'rose'],
        ['MRR',              '$'.number_format($stats['revenue_monthly'], 0), 'green'],
    ] as [$label, $value, $color])
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-3.5 text-center hover:border-{{ $color }}-500/20 transition">
        <p class="text-lg font-bold text-white">{{ is_numeric($value) ? number_format($value) : $value }}</p>
        <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5">{{ $label }}</p>
    </div>
    @endforeach
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    {{-- MRR Trend --}}
    <div class="xl:col-span-2 bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-white">MRR Trend</h3>
                <p class="text-xs text-gray-500 mt-0.5">Estimated monthly recurring revenue (30 days)</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-emerald-400">${{ number_format($stats['revenue_monthly'], 0) }}</p>
                <p class="text-xs text-gray-500">current MRR</p>
            </div>
        </div>
        <div class="relative" style="height:220px"><canvas id="mrrChart"></canvas></div>
    </div>

    {{-- Plan Distribution Pie --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">Plan Distribution</h3>
        @if($planDistribution->isNotEmpty())
        @php
            $pieColors = ['rgba(255, 106, 43,0.8)','rgba(16,185,129,0.8)','rgba(245,158,11,0.8)','rgba(239,68,68,0.8)','rgba(6,182,212,0.8)','rgba(8, 145, 178,0.8)'];
            $totalPaying = $planDistribution->sum('count');
        @endphp
        <div class="flex justify-center mb-5" style="height: 160px">
            <canvas id="planPieChart"></canvas>
        </div>
        <div class="space-y-2">
            @foreach($planDistribution as $i => $item)
            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $pieColors[$i % count($pieColors)] }}"></span>
                    <span class="text-gray-400">{{ $item['label'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-white font-medium">{{ $item['count'] }}</span>
                    <span class="text-gray-600">({{ $totalPaying > 0 ? round(($item['count']/$totalPaying)*100) : 0 }}%)</span>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="flex flex-col items-center justify-center h-40 text-center">
            <p class="text-3xl mb-2">📊</p>
            <p class="text-sm text-gray-500">No active subscriptions</p>
        </div>
        @endif
    </div>
</div>

{{-- User Growth + System Health --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
    {{-- User Growth Chart --}}
    <div class="xl:col-span-2 bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">User Signups (30 days)</h3>
        <div class="relative" style="height:200px"><canvas id="adminChart"></canvas></div>
    </div>

    {{-- System Health --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">System Health</h3>
        <div class="space-y-3">
            @php
            $healthItems = [
                ['DB Size',       $health['db_size_mb'] . ' MB',    $health['db_size_mb'] < 500 ? 'emerald' : ($health['db_size_mb'] < 1000 ? 'amber' : 'rose')],
                ['Queue Pending', number_format($health['queue_pending']), $health['queue_pending'] < 50 ? 'emerald' : ($health['queue_pending'] < 200 ? 'amber' : 'rose')],
                ['Failed Jobs',   number_format($health['queue_failed']),  $health['queue_failed'] === 0 ? 'emerald' : ($health['queue_failed'] < 10 ? 'amber' : 'rose')],
                ['Cache Driver',  ucfirst($health['cache_driver']), 'sky'],
                ['Queue Driver',  ucfirst($health['queue_driver']), 'sky'],
                ['PHP',           $health['php_version'],            'emerald'],
                ['Environment',   ucfirst($health['app_env']),       $health['app_env'] === 'production' ? 'emerald' : 'amber'],
            ];
            @endphp
            @foreach($healthItems as [$label, $value, $color])
            <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                <span class="text-xs text-gray-500">{{ $label }}</span>
                <span class="text-xs font-medium text-{{ $color }}-400">{{ $value }}</span>
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-3 border-t border-white/5 flex items-center gap-2">
            <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="text-xs text-emerald-400 font-medium">System Operational</span>
        </div>
    </div>
</div>

{{-- Top Customers --}}
@if($topCustomers->isNotEmpty())
<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden mb-6">
    <div class="flex items-center justify-between p-5 border-b border-white/5">
        <h3 class="text-base font-semibold text-white">Top Customers by MRR</h3>
        <a href="{{ route('admin.users') }}" class="text-xs text-brand-400 hover:text-brand-300 transition">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="px-5 py-3 text-left text-[11px] font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-5 py-3 text-left text-[11px] font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider">MRR</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider">Widgets</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider">Leads</th>
                    <th class="px-5 py-3 text-right text-[11px] font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($topCustomers as $item)
                <tr class="hover:bg-white/2 transition">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-linear-to-br from-gray-700 to-gray-800 flex items-center justify-center text-[11px] font-bold text-white shrink-0">
                                {{ strtoupper(substr($item['user']->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm text-white font-medium">{{ $item['user']->name }}</p>
                                <p class="text-[10px] text-gray-500">{{ $item['user']->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-brand-500/10 text-brand-400">{{ $item['plan'] }}</span>
                    </td>
                    <td class="px-5 py-3 text-right text-sm font-bold text-emerald-400">${{ number_format($item['mrr'], 0) }}</td>
                    <td class="px-5 py-3 text-right text-sm text-white">{{ $item['user']->widgets_count }}</td>
                    <td class="px-5 py-3 text-right text-sm text-white">{{ $item['user']->leads_count }}</td>
                    <td class="px-5 py-3 text-right text-xs text-gray-500">{{ $item['user']->created_at->format('M d, Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.users.show', $item['user']) }}" class="text-xs text-brand-400 hover:text-brand-300 transition">View →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Recent Users --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b border-white/5">
        <h3 class="text-base font-semibold text-white">Recent Signups</h3>
        <a href="{{ route('admin.users') }}" class="text-xs text-brand-400 hover:text-brand-300 transition">View all →</a>
    </div>
    <div class="divide-y divide-white/5">
        @foreach($recentUsers->take(8) as $user)
        <a href="{{ route('admin.users.show', $user) }}" class="flex items-center justify-between p-3.5 hover:bg-white/2 transition group">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-linear-to-br from-gray-700 to-gray-800 flex items-center justify-center text-xs font-bold text-white shrink-0">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                <div>
                    <p class="text-sm text-white group-hover:text-brand-300 transition">{{ $user->name }}</p>
                    <p class="text-[10px] text-gray-500">{{ $user->email }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $user->subscription?->plan ? 'bg-brand-500/10 text-brand-400' : 'bg-gray-500/10 text-gray-400' }}">{{ $user->subscription?->plan?->name ?? 'Free' }}</span>
                <span class="text-[10px] text-gray-600">{{ $user->created_at->diffForHumans() }}</span>
            </div>
        </a>
        @endforeach
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // User growth bar chart
    new Chart(document.getElementById('adminChart'), {
        type: 'bar',
        data: { labels: @json($userChart['labels']), datasets: [{ label: 'New Users', data: @json($userChart['data']), backgroundColor: 'rgba(255, 106, 43,0.25)', borderColor: '#ff6a2b', borderWidth: 1.5, borderRadius: 4 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 9 }, maxTicksLimit: 8 } }, y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#6b7280', font: { size: 10 } }, beginAtZero: true } } }
    });

    // MRR trend line chart
    new Chart(document.getElementById('mrrChart'), {
        type: 'line',
        data: {
            labels: @json($mrrChart['labels']),
            datasets: [{ label: 'MRR', data: @json($mrrChart['data']), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' $' + ctx.parsed.y.toLocaleString() } } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#6b7280', font: { size: 10 }, maxTicksLimit: 8 } },
                y: { grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: '#6b7280', font: { size: 10 }, callback: v => '$'+v.toLocaleString() }, beginAtZero: true }
            },
            interaction: { mode: 'index', intersect: false }
        }
    });

    // Plan distribution pie chart
    const planPieEl = document.getElementById('planPieChart');
    if (planPieEl) {
        const planData = @json($planDistribution->pluck('count'));
        const planLabels = @json($planDistribution->pluck('label'));
        new Chart(planPieEl, {
            type: 'doughnut',
            data: { labels: planLabels, datasets: [{ data: planData, backgroundColor: ['rgba(255, 106, 43,0.8)','rgba(16,185,129,0.8)','rgba(245,158,11,0.8)','rgba(239,68,68,0.8)','rgba(6,182,212,0.8)','rgba(8, 145, 178,0.8)'], borderColor: 'transparent', hoverOffset: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { display: false } } }
        });
    }
});
</script>
@endpush
@endsection
