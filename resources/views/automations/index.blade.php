@extends('layouts.app')
@section('title', 'Automations')
@section('page-title', 'Automations')
@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-gray-400 text-sm">Automate actions based on events</p>
    <a href="{{ route('automations.create') }}" class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        New Automation
    </a>
</div>

<div class="space-y-3">
    @forelse($automations as $auto)
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 hover:border-brand-500/20 transition">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-{{ $auto->is_active ? 'brand' : 'gray' }}-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-{{ $auto->is_active ? 'brand' : 'gray' }}-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">{{ $auto->name }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        When <span class="text-brand-400">{{ str_replace('_', ' ', $auto->trigger) }}</span>
                        → <span class="text-emerald-400">{{ str_replace('_', ' ', $auto->action) }}</span>
                        @if($auto->widget) · {{ $auto->widget->name }} @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right text-xs text-gray-500">
                    <p>{{ $auto->trigger_count }} runs · {{ $auto->success_rate }}% success</p>
                    <p>{{ $auto->last_triggered_at?->diffForHumans() ?? 'Never run' }}</p>
                </div>
                <a href="{{ route('automations.edit', $auto) }}" class="p-1.5 text-gray-500 hover:text-brand-400 rounded-lg transition" title="Edit">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                </a>
                <form method="POST" action="{{ route('automations.toggle', $auto) }}">@csrf
                    <button class="relative w-11 h-6 rounded-full transition {{ $auto->is_active ? 'bg-brand-600' : 'bg-gray-700' }}">
                        <span class="absolute top-0.5 {{ $auto->is_active ? 'left-5' : 'left-0.5' }} w-5 h-5 bg-white rounded-full transition-all shadow"></span>
                    </button>
                </form>
                <form method="POST" action="{{ route('automations.destroy', $auto) }}" onsubmit="return confirm('Delete this automation?')">@csrf @method('DELETE')
                    <button class="p-1.5 text-gray-500 hover:text-rose-400 rounded-lg transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-12 text-center">
        <div class="w-14 h-14 bg-brand-500/10 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-7 h-7 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg></div>
        <h3 class="text-sm font-medium text-white mb-1">No automations yet</h3>
        <p class="text-xs text-gray-500 mb-4">Create automations to trigger actions like emails, webhooks, or notifications.</p>
        <a href="{{ route('automations.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Create Automation</a>
    </div>
    @endforelse
</div>
<div class="mt-6">{{ $automations->links() }}</div>
@endsection
