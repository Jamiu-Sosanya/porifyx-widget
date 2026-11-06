@extends('layouts.app')
@section('title', 'Knowledge Base: ' . $knowledgeBase->name)
@section('page-title', $knowledgeBase->name)
@section('content')
@include('partials.plan-limit', ['metric' => 'training_sources', 'label' => 'training sources'])
<div class="max-w-4xl">
    <a href="{{ route('widgets.knowledge.index', $widget) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white mb-6 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        Back to knowledge bases
    </a>

    {{-- Add Source --}}
    <div x-data="{ open: false, type: 'url' }" class="mb-6">
        <button @click="open = !open" class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Add Training Source
        </button>
        <div x-show="open" x-transition class="mt-4 bg-gray-900/50 border border-white/5 rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-4">
                @foreach(['url' => '🌐 URL', 'text' => '📝 Text', 'faq' => '❓ FAQ', 'pdf' => '📄 PDF', 'sitemap' => '🗺️ Sitemap'] as $val => $label)
                <button @click="type = '{{ $val }}'" :class="type === '{{ $val }}' ? 'bg-brand-500/20 text-brand-400 border-brand-500/30' : 'bg-white/5 text-gray-400 border-white/10 hover:bg-white/10'"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition">{{ $label }}</button>
                @endforeach
            </div>
            <form method="POST" action="{{ route('widgets.knowledge.addSource', [$widget, $knowledgeBase]) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="type" :value="type">
                <div><label class="block text-sm font-medium text-gray-300 mb-1">Name</label><input name="name" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition" placeholder="Source name"></div>
                <div x-show="type === 'url' || type === 'sitemap'">
                    <label class="block text-sm font-medium text-gray-300 mb-1">URL</label>
                    <input name="source_url" type="url" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition" placeholder="https://example.com">
                </div>
                <div x-show="type === 'text' || type === 'faq'">
                    <label class="block text-sm font-medium text-gray-300 mb-1" x-text="type === 'faq' ? 'FAQ Content (Q: ... A: ... format)' : 'Text Content'"></label>
                    <textarea name="content" rows="6" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none font-mono text-sm" placeholder="Paste your content here..."></textarea>
                </div>
                <div x-show="type === 'pdf'">
                    <label class="block text-sm font-medium text-gray-300 mb-1">PDF File</label>
                    <input name="file" type="file" accept=".pdf" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-brand-500/20 file:text-brand-400 hover:file:bg-brand-500/30">
                </div>
                <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Add & Process</button>
            </form>
        </div>
    </div>

    {{-- Sources List --}}
    <div class="space-y-3">
        @forelse($knowledgeBase->trainingSources as $source)
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center text-sm">
                        {{ ['url' => '🌐', 'text' => '📝', 'faq' => '❓', 'pdf' => '📄', 'sitemap' => '🗺️'][$source->type] ?? '📎' }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">{{ $source->name }}</p>
                        <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                            <span>{{ ucfirst($source->type) }}</span>
                            <span>{{ $source->chunks_generated }} chunks</span>
                            <span>{{ number_format($source->tokens_used) }} tokens</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @php $srcColors = ['completed' => 'bg-emerald-500/10 text-emerald-400', 'processing' => 'bg-amber-500/10 text-amber-400', 'pending' => 'bg-gray-500/10 text-gray-400', 'failed' => 'bg-rose-500/10 text-rose-400']; @endphp
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $srcColors[$source->status] ?? '' }}">{{ ucfirst($source->status) }}</span>
                    <form method="POST" action="{{ route('widgets.knowledge.deleteSource', [$widget, $knowledgeBase, $source]) }}" onsubmit="return confirm('Delete this source and all its chunks?')">
                        @csrf @method('DELETE')
                        <button class="p-1.5 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button>
                    </form>
                </div>
            </div>
            @if($source->error_message)
            <p class="mt-2 text-xs text-rose-400 bg-rose-500/5 rounded-lg p-2">{{ $source->error_message }}</p>
            @endif
        </div>
        @empty
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-8 text-center text-sm text-gray-500">No training sources yet. Add one to start training your AI.</div>
        @endforelse
    </div>
</div>
@endsection
