@extends('help.layout')
@section('title', $q !== '' ? 'Search: ' . $q : 'Help centre')
@section('content')
@if($results !== null)
    <h1 class="text-xl font-bold">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('result', $results->count()) }} for “{{ $q }}”</h1>
    <ul class="mt-4 space-y-2">
        @forelse($results as $a)
        <li><a href="{{ route('help.article', [$widget->uuid, $a->slug]) }}" class="block rounded-2xl bg-white border border-slate-200 p-4 hover:border-slate-300">
            <p class="font-semibold">{{ $a->title }}</p>
            <p class="mt-1 text-sm text-slate-500 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($a->html()), 160) }}</p>
        </a></li>
        @empty
        <li class="text-slate-500">Nothing found. Try other words, or ask our assistant in the corner.</li>
        @endforelse
    </ul>
@else
    @if($categories->where('articles_count', '>', 0)->isNotEmpty())
    <div class="grid sm:grid-cols-2 gap-3">
        @foreach($categories->where('articles_count', '>', 0) as $c)
        <a href="{{ route('help.category', [$widget->uuid, $c->slug]) }}" class="flex gap-4 rounded-2xl bg-white border border-slate-200 p-5 hover:border-slate-300 hover:shadow-sm transition">
            <span class="text-2xl">{{ $c->icon ?: '📁' }}</span>
            <span class="min-w-0">
                <span class="block font-semibold">{{ $c->name }}</span>
                @if($c->description)<span class="block text-sm text-slate-500">{{ $c->description }}</span>@endif
                <span class="block mt-1 text-xs text-slate-400">{{ $c->articles_count }} {{ \Illuminate\Support\Str::plural('article', $c->articles_count) }}</span>
            </span>
        </a>
        @endforeach
    </div>
    @endif
    @if($popular->isNotEmpty())
    <h2 class="mt-10 text-lg font-bold">Popular articles</h2>
    <ul class="mt-3 divide-y divide-slate-200 rounded-2xl bg-white border border-slate-200">
        @foreach($popular as $a)
        <li><a href="{{ route('help.article', [$widget->uuid, $a->slug]) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-slate-50"><span>{{ $a->title }}</span><span class="text-slate-400">→</span></a></li>
        @endforeach
    </ul>
    @else
    <p class="text-slate-500">No articles yet.</p>
    @endif
@endif
@endsection
