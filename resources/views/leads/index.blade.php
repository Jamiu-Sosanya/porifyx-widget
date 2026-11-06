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
            @foreach($pipeline['statuses'] as $st)
            <option value="{{ $st['key'] }}" {{ request('status') === $st['key'] ? 'selected' : '' }}>{{ $st['label'] }}</option>
            @endforeach
        </select>
        <a href="{{ route('leads.export') }}" class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-xl transition">📥 Export</a>
    </form>

    {{-- View toggle --}}
    <div x-data="{ view: localStorage.getItem('leadsView') || 'table' }" class="flex items-center gap-1 bg-white/5 border border-white/10 rounded-xl p-1">
        <button @click="view='table'; localStorage.setItem('leadsView','table'); $dispatch('leads-view', 'table')"
            :class="view === 'table' ? 'bg-brand-500/20 text-brand-400' : 'text-gray-500 hover:text-white'"
            class="p-2 rounded-lg transition" title="Table view">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0c0 .621.504 1.125 1.125 1.125M3.375 8.25c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m17.25-3.75h-1.5c-.621 0-1.125.504-1.125 1.125v1.5" /></svg>
        </button>
        <button @click="view='kanban'; localStorage.setItem('leadsView','kanban'); $dispatch('leads-view', 'kanban')"
            :class="view === 'kanban' ? 'bg-brand-500/20 text-brand-400' : 'text-gray-500 hover:text-white'"
            class="p-2 rounded-lg transition" title="Kanban view">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" /></svg>
        </button>

        {{-- TABLE VIEW --}}
        <template x-if="false"><!---></template>
    </div>
</div>

{{-- TABLE VIEW --}}
<div x-data="{ view: localStorage.getItem('leadsView') || 'table' }" @leads-view.window="view = $event.detail">

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
                            <td class="px-4 py-3">@php $stx = collect($pipeline['statuses'])->firstWhere('key', $lead->status) ?? ['label' => ucfirst($lead->status), 'color' => '#6b7280']; @endphp<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold" style="background: {{ $stx['color'] }}26; color: {{ $stx['color'] }}">{{ $stx['label'] }}</span></td>
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

    {{-- BOARD VIEW: drag a card to another column to change its status --}}
    <div x-show="view === 'kanban'" x-cloak
         x-data="{ drag: null, async drop(key, el) {
                if (!this.drag || this.drag.dataset.status === key) return;
                const card = this.drag; el.querySelector('[data-cards]').prepend(card); card.dataset.status = key;
                const r = await fetch(card.dataset.url, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ status: key }) });
                if (!r.ok) location.reload();
                document.querySelectorAll('[data-count]').forEach(c => c.textContent = c.closest('[data-col]').querySelectorAll('[data-lead]').length);
             } }">
        <div class="flex items-center justify-between gap-3 mb-3">
            <p class="text-xs text-gray-500">Drag cards between columns. Showing up to 300 leads with the current filters.</p>
            <button type="button" @click="$dispatch('open-pipeline')" class="shrink-0 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-200">Edit columns & scoring</button>
        </div>
        <div class="flex gap-4 overflow-x-auto pb-3 snap-x">
            @foreach($pipeline['statuses'] as $st)
            @php $colLeads = $boardLeads->where('status', $st['key']); @endphp
            <div data-col class="snap-start shrink-0 w-[260px] flex flex-col rounded-2xl bg-white/[0.02] border border-white/5 p-2.5"
                 @dragover.prevent @drop.prevent="drop('{{ $st['key'] }}', $el)">
                <div class="flex items-center justify-between px-1.5 py-1 mb-2">
                    <span class="flex items-center gap-2 text-xs font-semibold text-gray-200"><span class="w-2.5 h-2.5 rounded-full" style="background: {{ $st['color'] }}"></span>{{ $st['label'] }}</span>
                    <span data-count class="text-[10px] font-semibold text-gray-500 bg-white/5 rounded-full px-2 py-0.5">{{ $colLeads->count() }}</span>
                </div>
                <div data-cards class="space-y-2 min-h-16 flex-1">
                    @foreach($colLeads as $lead)
                    <a href="{{ route('leads.show', $lead) }}" data-lead data-status="{{ $lead->status }}" data-url="{{ route('leads.update', $lead) }}"
                       draggable="true" @dragstart="drag = $el" @dragend="drag = null"
                       class="block rounded-xl bg-gray-900/80 border border-white/5 p-3 hover:border-white/15 transition cursor-grab active:cursor-grabbing" style="border-left: 3px solid {{ $st['color'] }}">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-white truncate">{{ $lead->name ?: ($lead->email ?: 'Unknown') }}</p>
                            <span class="text-[10px] shrink-0">@if($lead->temperature === 'hot')🔥@elseif($lead->temperature === 'warm')☀️@else❄️@endif {{ $lead->score }}</span>
                        </div>
                        @if($lead->email && $lead->name)<p class="text-[11px] text-gray-500 truncate mt-0.5">{{ $lead->email }}</p>@endif
                        <p class="text-[10px] text-gray-600 mt-1.5">{{ $lead->widget?->name }} · {{ $lead->created_at->diffForHumans(short: true) }}</p>
                    </a>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Pipeline settings --}}
    <div x-data="{ open: false, statuses: @js($pipeline['statuses']) }" @open-pipeline.window="open = true" x-show="open" x-cloak
         class="fixed inset-0 z-50 grid place-items-center bg-black/60 backdrop-blur-sm p-4" @keydown.escape.window="open = false">
        <form method="POST" action="{{ route('leads.pipeline') }}" @click.outside="open = false" class="w-full max-w-lg max-h-[90dvh] overflow-y-auto rounded-2xl bg-[#0d1a33] border border-white/10 p-5 sm:p-6 space-y-4">
            @csrf @method('PUT')
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-white">Lead pipeline</h3>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-white" aria-label="Close">✕</button>
            </div>
            <div class="space-y-2">
                <p class="text-xs text-gray-400">Board columns (statuses). Removing one moves its leads to the first column.</p>
                <template x-for="(st, i) in statuses" :key="i">
                    <div class="flex items-center gap-2">
                        <input type="color" :name="`statuses[${i}][color]`" x-model="st.color" class="w-9 h-9 shrink-0 rounded-lg bg-transparent">
                        <input :name="`statuses[${i}][label]`" x-model="st.label" required maxlength="30" class="flex-1 min-w-0 bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white">
                        <input type="hidden" :name="`statuses[${i}][key]`" :value="st.key || ''">
                        <button type="button" @click="statuses.splice(i, 1)" x-show="statuses.length > 2" class="text-xs text-rose-400 px-1">Remove</button>
                    </div>
                </template>
                <button type="button" x-show="statuses.length < 12" @click="statuses.push({ key: '', label: '', color: '#8fb4e3' })" class="text-xs font-semibold text-brand-400">+ Add column</button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <label class="text-xs text-gray-400">Warm from score<input name="warm" type="number" min="1" max="99" value="{{ $pipeline['warm'] }}" class="mt-1 w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white"></label>
                <label class="text-xs text-gray-400">Hot from score<input name="hot" type="number" min="2" max="100" value="{{ $pipeline['hot'] }}" class="mt-1 w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white"></label>
            </div>
            <button class="w-full py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save pipeline</button>
        </form>
    </div>
</div>
@endsection
