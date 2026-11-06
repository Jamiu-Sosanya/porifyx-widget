@extends('layouts.app')
@section('title', 'Leads')
@section('page-title', 'Leads')
@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
    @foreach(['total' => ['Total', 'gray', ''], 'hot' => ['Hot', 'rose', '🔥'], 'warm' => ['Warm', 'amber', '☀️'], 'cold' => ['Cold', 'sky', '❄️'], 'new' => ['New', 'blue', '✨'], 'won' => ['Won', 'emerald', '🏆']] as $key => [$label, $color, $emoji])
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-3 text-center hover:border-{{ $color }}-500/20 transition">
        <p class="text-xl font-bold text-white">{{ $emoji }} {{ number_format($stats[$key]) }}</p>
        <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5">{{ $label }}</p>
    </div>
    @endforeach
</div>

{{-- Filters + View Toggle --}}
<div class="flex flex-wrap items-center gap-3 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-3 flex-1">
        <div class="relative flex-1 min-w-50">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            <input name="search" value="{{ request('search') }}" placeholder="Search leads…" class="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition">
        </div>
        <select name="temperature" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none transition">
            <option value="">All Temps</option>
            @foreach(['hot' => '🔥 Hot', 'warm' => '☀️ Warm', 'cold' => '❄️ Cold'] as $val => $label)
            <option value="{{ $val }}" {{ request('temperature') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none transition">
            <option value="">All Status</option>
            @foreach(['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <a href="{{ route('leads.export') }}" class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-xl transition">📥 Export</a>
    </form>

    {{-- View toggle --}}
    <div x-data="{ view: localStorage.getItem('leadsView') || 'table' }" class="flex items-center gap-1 bg-white/5 border border-white/10 rounded-xl p-1">
        <button @click="view='table'; localStorage.setItem('leadsView','table')"
            :class="view === 'table' ? 'bg-brand-500/20 text-brand-400' : 'text-gray-500 hover:text-white'"
            class="p-2 rounded-lg transition" title="Table view">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0c0 .621.504 1.125 1.125 1.125M3.375 8.25c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m17.25-3.75h-1.5c-.621 0-1.125.504-1.125 1.125v1.5" /></svg>
        </button>
        <button @click="view='kanban'; localStorage.setItem('leadsView','kanban')"
            :class="view === 'kanban' ? 'bg-brand-500/20 text-brand-400' : 'text-gray-500 hover:text-white'"
            class="p-2 rounded-lg transition" title="Kanban view">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" /></svg>
        </button>

        {{-- TABLE VIEW --}}
        <template x-if="false"><!---></template>
    </div>
</div>

{{-- TABLE VIEW --}}
<div x-data="{ view: localStorage.getItem('leadsView') || 'table' }">

    <div x-show="view === 'table'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/5 text-left">
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Lead</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Widget</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($leads as $lead)
                        <tr class="hover:bg-white/2 transition group">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-linear-to-br from-accent-600/20 to-accent-600/20 flex items-center justify-center text-xs font-bold text-accent-400 shrink-0">{{ strtoupper(substr($lead->name ?? $lead->email ?? '?', 0, 1)) }}</div>
                                    <div>
                                        <p class="text-sm font-medium text-white group-hover:text-accent-300 transition">{{ $lead->name ?? 'Unknown' }}</p>
                                        <p class="text-xs text-gray-500">{{ $lead->email ?? $lead->phone ?? 'No contact' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $lead->temperature_color }}">
                                        @if($lead->temperature === 'hot')🔥@elseif($lead->temperature === 'warm')☀️@else❄️@endif {{ ucfirst($lead->temperature) }}
                                    </span>
                                    <span class="text-xs text-gray-400 font-medium">{{ $lead->score }}<span class="text-gray-600">/100</span></span>
                                </div>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-medium {{ $lead->status_badge }}">{{ ucfirst($lead->status) }}</span></td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ $lead->widget?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $lead->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('leads.show', $lead) }}" class="text-xs text-brand-400 hover:text-brand-300 transition">View →</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No leads yet. They'll appear as visitors engage with your AI widgets.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6">{{ $leads->withQueryString()->links() }}</div>
    </div>

    {{-- KANBAN VIEW --}}
    @php
    $kanbanCols = [
        'new'       => ['New',       'blue',    '✨', $leads->where('status','new')],
        'contacted' => ['Contacted', 'sky',     '📞', $leads->where('status','contacted')],
        'qualified' => ['Qualified', 'accent',  '✅', $leads->where('status','qualified')],
        'proposal'  => ['Proposal',  'amber',   '📄', $leads->where('status','proposal')],
        'won'       => ['Won',       'emerald', '🏆', $leads->where('status','won')],
        'lost'      => ['Lost',      'gray',    '✗',  $leads->where('status','lost')],
    ];
    @endphp
    <div x-show="view === 'kanban'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 overflow-x-auto pb-2">
            @foreach($kanbanCols as $status => [$label, $color, $emoji, $colLeads])
            <div class="flex flex-col min-w-50">
                {{-- Column Header --}}
                <div class="flex items-center justify-between mb-3 px-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm">{{ $emoji }}</span>
                        <span class="text-xs font-semibold text-{{ $color }}-400">{{ $label }}</span>
                    </div>
                    <span class="text-[10px] font-medium text-gray-500 bg-white/5 rounded-full px-1.5 py-0.5">{{ $colLeads->count() }}</span>
                </div>

                {{-- Cards --}}
                <div class="space-y-2.5 flex-1">
                    @forelse($colLeads->take(10) as $lead)
                    <a href="{{ route('leads.show', $lead) }}" class="block bg-gray-900/70 border border-white/5 rounded-xl p-3.5 hover:border-{{ $color }}-500/20 hover:bg-{{ $color }}-500/5 transition group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-7 h-7 rounded-lg bg-{{ $color }}-500/10 flex items-center justify-center text-[10px] font-bold text-{{ $color }}-400 shrink-0">
                                {{ strtoupper(substr($lead->name ?? $lead->email ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex items-center gap-1 text-[10px] font-medium {{ $lead->temperature === 'hot' ? 'text-rose-400' : ($lead->temperature === 'warm' ? 'text-amber-400' : 'text-accent-400') }}">
                                @if($lead->temperature === 'hot')🔥@elseif($lead->temperature === 'warm')☀️@else❄️@endif
                            </div>
                        </div>
                        <p class="text-xs font-medium text-white truncate group-hover:text-{{ $color }}-300 transition">{{ $lead->name ?? 'Unknown' }}</p>
                        @if($lead->email)
                        <p class="text-[10px] text-gray-500 truncate mt-0.5">{{ $lead->email }}</p>
                        @endif
                        @if($lead->company)
                        <p class="text-[10px] text-gray-500 truncate">{{ $lead->company }}</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex-1 h-1 bg-white/5 rounded-full overflow-hidden mr-2">
                                <div class="h-full bg-{{ $color }}-500/60 rounded-full" style="width: {{ $lead->score }}%"></div>
                            </div>
                            <span class="text-[10px] text-gray-600 shrink-0">{{ $lead->score }}</span>
                        </div>
                        <p class="text-[9px] text-gray-600 mt-1.5">{{ $lead->created_at->diffForHumans(short: true) }}</p>
                    </a>
                    @empty
                    <div class="border border-dashed border-white/5 rounded-xl p-4 text-center">
                        <p class="text-[10px] text-gray-600">No leads</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
