{{-- Plan limit notice: $metric (widgets|automations|training_sources|team_members), $label --}}
@php
    $pfxU = auth()->user();
    $pfxPlan = $pfxU?->currentPlan();
    $pfxCol = ['widgets' => 'widgets_limit', 'automations' => 'workflow_automations_limit', 'training_sources' => 'training_sources_limit', 'team_members' => 'team_members_limit'][$metric] ?? null;
    $pfxLimit = $pfxPlan && $pfxCol ? (int) $pfxPlan->{$pfxCol} : -1;
    $pfxUsed = match ($metric) {
        'widgets' => $pfxU?->widgets()->count() ?? 0,
        'automations' => $pfxU?->automations()->count() ?? 0,
        'team_members' => \App\Models\TeamMember::where('owner_id', $pfxU?->id)->count(),
        'training_sources' => \App\Models\TrainingSource::whereHas('knowledgeBase', fn ($q) => $q->where('user_id', $pfxU?->id))->count(),
        default => 0,
    };
@endphp
@if($pfxU && ! $pfxU->isAdmin() && $pfxLimit !== -1 && $pfxUsed >= $pfxLimit)
<div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-amber-500/30 bg-gradient-to-r from-amber-500/10 to-brand-500/10 p-4">
    <span class="text-xl">✨</span>
    <div class="flex-1">
        <p class="text-sm font-semibold text-amber-200">You're at your plan's {{ $label }} limit ({{ $pfxUsed }} of {{ $pfxLimit }} used).</p>
        <p class="text-xs text-amber-200/70">Upgrade to add more {{ $label }} without losing what you already have.</p>
    </div>
    <a href="{{ route('settings.billing') }}" class="shrink-0 text-center px-4 py-2 rounded-xl bg-amber-300 text-amber-950 text-sm font-bold">Upgrade ↗</a>
</div>
@endif
