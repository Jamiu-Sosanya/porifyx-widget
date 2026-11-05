@extends('layouts.app')
@section('title', 'Widgets')
@section('page-title', 'Widgets')
@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-gray-400 text-sm">Manage your AI chat widgets</p>
    <a href="{{ route('widgets.create') }}" class="flex items-center gap-2 px-4 py-2.5 bg-linear-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        New Widget
    </a>
</div>

@if($widgets->isEmpty())
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-12 text-center">
    <div class="w-16 h-16 bg-brand-500/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" /></svg>
    </div>
    <h3 class="text-lg font-semibold text-white mb-2">Create your first widget</h3>
    <p class="text-gray-400 text-sm mb-6 max-w-md mx-auto">Build an AI-powered chat widget, train it with your data, and embed it on your website in minutes.</p>
    <a href="{{ route('widgets.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Create Widget
    </a>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($widgets as $widget)
    <div class="group bg-gray-900/50 border border-white/5 hover:border-brand-500/20 rounded-2xl p-5 transition-all duration-300 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-linear-to-bl from-{{ $widget->is_active ? 'brand' : 'gray' }}-500/5 to-transparent rounded-bl-full"></div>
        <div class="relative">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background-color: {{ $widget->primary_color }}20">
                        <div class="w-4 h-4 rounded-full" style="background-color: {{ $widget->primary_color }}"></div>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">{{ $widget->name }}</h3>
                        <p class="text-xs text-gray-500">{{ $widget->ai_name }} · {{ ucfirst($widget->tone) }}</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $widget->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-500/10 text-gray-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $widget->is_active ? 'bg-emerald-400' : 'bg-gray-500' }} mr-1.5"></span>
                    {{ $widget->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="bg-white/2 rounded-xl p-3 text-center">
                    <p class="text-lg font-bold text-white">{{ number_format($widget->conversations_count ?? 0) }}</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider">Chats</p>
                </div>
                <div class="bg-white/2 rounded-xl p-3 text-center">
                    <p class="text-lg font-bold text-white">{{ number_format($widget->total_messages) }}</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider">Msgs</p>
                </div>
                <div class="bg-white/2 rounded-xl p-3 text-center">
                    <p class="text-lg font-bold text-white">{{ number_format($widget->leads_count ?? 0) }}</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider">Leads</p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('widgets.show', $widget) }}" class="flex-1 min-w-16 flex items-center justify-center gap-1.5 px-3 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-xs font-medium text-gray-300 hover:text-white transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    View
                </a>
                <a href="{{ route('widgets.edit', $widget) }}" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-xs font-medium text-gray-300 hover:text-white transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                    Edit
                </a>
                <a href="{{ route('widgets.knowledge.index', $widget) }}" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-xs font-medium text-gray-300 hover:text-white transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" /></svg>
                    Train
                </a>
                <a href="{{ route('widgets.embed', $widget) }}" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-brand-500/10 hover:bg-brand-500/20 rounded-xl text-xs font-medium text-brand-400 hover:text-brand-300 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg>
                    Embed
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-6">{{ $widgets->links() }}</div>
@endif
@endsection
