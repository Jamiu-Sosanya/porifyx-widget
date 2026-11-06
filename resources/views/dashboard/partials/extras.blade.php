@php
    $u = auth()->user();
    $pipe = \App\Support\LeadPipeline::settings($u)['statuses'];
    $counts = \App\Models\Lead::where('user_id', $u->id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
    $total = max(1, $counts->sum());
    $kbIds = \App\Models\KnowledgeBase::where('user_id', $u->id)->pluck('id');
    $src = \App\Models\TrainingSource::whereIn('knowledge_base_id', $kbIds)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
    $srcTotal = max(1, $src->sum());
    $recentLeads = \App\Models\Lead::where('user_id', $u->id)->with('widget:id,name')->latest()->limit(6)->get();
    $top = \App\Models\Widget::where('user_id', $u->id)->withCount(['conversations' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))])->orderByDesc('conversations_count')->limit(4)->get();
@endphp
<div class="grid lg:grid-cols-3 gap-6 mt-6">
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
        <div class="flex items-center justify-between"><h3 class="text-sm font-semibold text-white">Lead pipeline</h3><span class="text-xs text-gray-500">{{ $counts->sum() }} total</span></div>
        <ul class="mt-4 space-y-3">
            @foreach($pipe as $st)
            @php $n = $counts[$st['key']] ?? 0; @endphp
            <li><div class="flex justify-between text-xs"><span class="text-gray-300">{{ $st['label'] }}</span><span class="text-gray-500">{{ $n }} · {{ round($n / $total * 100) }}%</span></div>
                <div class="mt-1 h-1.5 rounded-full bg-white/5"><div class="h-full rounded-full" style="width: {{ $n / $total * 100 }}%; background: {{ $st['color'] }}"></div></div></li>
            @endforeach
        </ul>
    </section>
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
        <div class="flex items-center justify-between"><h3 class="text-sm font-semibold text-white">Knowledge health</h3><span class="text-xs text-emerald-400">{{ round(($src['completed'] ?? 0) / $srcTotal * 100) }}% indexed</span></div>
        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-xl bg-white/[0.03] p-3"><p class="text-lg font-bold text-white">{{ $src['completed'] ?? 0 }}</p><p class="text-[11px] text-gray-500">indexed</p></div>
            <div class="rounded-xl bg-white/[0.03] p-3"><p class="text-lg font-bold text-white">{{ ($src['pending'] ?? 0) + ($src['processing'] ?? 0) }}</p><p class="text-[11px] text-gray-500">in progress</p></div>
            <div class="rounded-xl bg-white/[0.03] p-3"><p class="text-lg font-bold {{ ($src['failed'] ?? 0) ? 'text-rose-400' : 'text-white' }}">{{ $src['failed'] ?? 0 }}</p><p class="text-[11px] text-gray-500">failed</p></div>
        </div>
        <h4 class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Top widgets (30 days)</h4>
        <ul class="mt-2 space-y-2 text-sm">
            @forelse($top as $t)<li><a href="{{ route('widgets.show', $t) }}" class="flex justify-between gap-2 hover:text-white"><span class="truncate text-gray-300">{{ $t->name }} @if($t->is_active)<span class="ml-1 px-1.5 rounded bg-emerald-500/15 text-emerald-400 text-[10px]">Live</span>@endif</span><span class="text-gray-400">{{ $t->conversations_count }} chats</span></a></li>
            @empty<li class="text-xs text-gray-500">No widgets yet.</li>@endforelse
        </ul>
    </section>
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
        <div class="flex items-center justify-between"><h3 class="text-sm font-semibold text-white">Recent leads</h3><a href="{{ route('leads.index') }}" class="text-xs text-brand-400">All leads →</a></div>
        <ul class="mt-3 divide-y divide-white/5">
            @forelse($recentLeads as $l)
            <li class="py-2.5"><a href="{{ route('leads.show', $l) }}" class="flex items-center gap-3">
                <span class="grid place-items-center w-8 h-8 shrink-0 rounded-full bg-brand-500/15 text-brand-400 text-xs font-bold">{{ mb_strtoupper(mb_substr($l->name ?: $l->email ?: '?', 0, 1)) }}</span>
                <span class="min-w-0 flex-1"><span class="block truncate text-sm text-gray-200">{{ $l->name ?: $l->email }}</span><span class="block truncate text-[11px] text-gray-500">{{ $l->widget?->name }} · {{ $l->created_at->diffForHumans(short: true) }}</span></span>
                <span class="text-[11px]">@if($l->temperature === 'hot')🔥@elseif($l->temperature === 'warm')☀️@else❄️@endif</span></a></li>
            @empty
            <li class="py-8 text-center text-xs text-gray-500">📥 No leads yet. Leads captured by your widgets appear here.</li>
            @endforelse
        </ul>
    </section>
</div>
