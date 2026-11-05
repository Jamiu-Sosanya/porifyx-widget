@extends('layouts.app')
@section('title', 'Knowledge Base')
@section('page-title', 'Knowledge Base')
@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('widgets.edit', $widget) }}" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg></a>
            <div>
                <h2 class="text-lg font-semibold text-white">{{ $widget->name }} — Knowledge</h2>
                <p class="text-xs text-gray-500">Train your AI with your data</p>
            </div>
        </div>
    </div>

    {{-- Create KB --}}
    <div x-data="{ open: false }" class="mb-6">
        <button @click="open = !open" class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            New Knowledge Base
        </button>
        <div x-show="open" x-transition class="mt-4 bg-gray-900/50 border border-white/5 rounded-2xl p-5">
            <form method="POST" action="{{ route('widgets.knowledge.store', $widget) }}" class="space-y-4">
                @csrf
                <div><label class="block text-sm font-medium text-gray-300 mb-1">Name</label><input name="name" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition" placeholder="e.g. Product Documentation"></div>
                <div><label class="block text-sm font-medium text-gray-300 mb-1">Description</label><textarea name="description" rows="2" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none" placeholder="What data does this contain?"></textarea></div>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Create</button>
            </form>
        </div>
    </div>

    {{-- KB List --}}
    <div class="space-y-4">
        @forelse($knowledgeBases as $kb)
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 hover:border-brand-500/20 transition">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                        {{ $kb->name }}
                        @if($kb->is_active)<span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 text-[10px] rounded-full font-medium">Active</span>@endif
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $kb->description ?? 'No description' }}</p>
                </div>
                @php
                $statusColors = ['ready' => 'bg-emerald-500/10 text-emerald-400', 'processing' => 'bg-amber-500/10 text-amber-400', 'pending' => 'bg-gray-500/10 text-gray-400', 'failed' => 'bg-rose-500/10 text-rose-400'];
                @endphp
                <span class="px-2.5 py-1 rounded-full text-[10px] font-medium {{ $statusColors[$kb->status] ?? '' }}">{{ ucfirst($kb->status) }}</span>
            </div>
            <div class="grid grid-cols-3 gap-3 text-center mb-4">
                <div class="bg-white/[0.02] rounded-xl p-2"><p class="text-sm font-bold text-white">{{ $kb->training_sources_count ?? 0 }}</p><p class="text-[10px] text-gray-500">Sources</p></div>
                <div class="bg-white/[0.02] rounded-xl p-2"><p class="text-sm font-bold text-white">{{ number_format($kb->chunks_count ?? 0) }}</p><p class="text-[10px] text-gray-500">Chunks</p></div>
                <div class="bg-white/[0.02] rounded-xl p-2"><p class="text-sm font-bold text-white">{{ number_format($kb->total_tokens) }}</p><p class="text-[10px] text-gray-500">Tokens</p></div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('widgets.knowledge.show', [$widget, $kb]) }}" class="flex-1 text-center px-3 py-2 bg-white/5 hover:bg-white/10 text-gray-300 text-xs font-medium rounded-xl transition">Manage Sources</a>
                @if(!$kb->is_active)
                <form method="POST" action="{{ route('widgets.knowledge.activate', [$widget, $kb]) }}" class="flex-1">@csrf<button class="w-full px-3 py-2 bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 text-xs font-medium rounded-xl transition">Activate</button></form>
                @endif
                <form method="POST" action="{{ route('widgets.knowledge.retrain', [$widget, $kb]) }}" class="shrink-0">@csrf<button class="px-3 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-medium rounded-xl transition">↻ Retrain</button></form>
            </div>
        </div>
        @empty
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-12 text-center">
            <h3 class="text-sm font-medium text-white mb-1">No knowledge bases yet</h3>
            <p class="text-xs text-gray-500">Create one and add training sources to teach your AI.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
