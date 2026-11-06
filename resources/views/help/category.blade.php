@extends('help.layout')
@section('title', $category->name)
@section('description', $category->description ?: $category->name)
@section('content')
<nav class="text-sm text-slate-500"><a href="{{ route('help.index', $widget->uuid) }}" class="hover:text-slate-800">Help centre</a> › {{ $category->name }}</nav>
<h1 class="mt-3 text-2xl font-extrabold tracking-tight">{{ $category->icon }} {{ $category->name }}</h1>
@if($category->description)<p class="mt-1 text-slate-500">{{ $category->description }}</p>@endif
<ul class="mt-6 divide-y divide-slate-200 rounded-2xl bg-white border border-slate-200">
    @forelse($articles as $a)
    <li><a href="{{ route('help.article', [$widget->uuid, $a->slug]) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-slate-50"><span>{{ $a->title }}</span><span class="text-slate-400">→</span></a></li>
    @empty
    <li class="px-5 py-4 text-slate-500">No articles in this category yet.</li>
    @endforelse
</ul>
@endsection
