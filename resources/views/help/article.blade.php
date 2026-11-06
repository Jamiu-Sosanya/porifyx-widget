@extends('help.layout')
@section('title', $article->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($article->html()), 155))
@section('content')
<nav class="text-sm text-slate-500">
    <a href="{{ route('help.index', $widget->uuid) }}" class="hover:text-slate-800">Help centre</a>
    @if($article->category) › <a href="{{ route('help.category', [$widget->uuid, $article->category->slug]) }}" class="hover:text-slate-800">{{ $article->category->name }}</a>@endif
</nav>
<article class="mt-4 rounded-2xl bg-white border border-slate-200 p-6 sm:p-8">
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $article->title }}</h1>
    <p class="mt-1 text-xs text-slate-400">Updated {{ $article->updated_at->format('j M Y') }}</p>
    <div class="pfx-prose mt-6">{!! $article->html() !!}</div>
</article>
@if($related->isNotEmpty())
<h2 class="mt-8 text-lg font-bold">Related articles</h2>
<ul class="mt-3 divide-y divide-slate-200 rounded-2xl bg-white border border-slate-200">
    @foreach($related as $a)
    <li><a href="{{ route('help.article', [$widget->uuid, $a->slug]) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-slate-50"><span>{{ $a->title }}</span><span class="text-slate-400">→</span></a></li>
    @endforeach
</ul>
@endif
@endsection
