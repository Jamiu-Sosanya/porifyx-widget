@extends('layouts.admin')
@section('title', 'Search')
@section('page-title', 'Search')
@section('content')
<form class="mb-6">
    <input name="q" value="{{ $q }}" autofocus maxlength="100" placeholder="Search users, widgets, leads, conversations and tickets…"
           class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white">
</form>
@if($results)
<div class="grid lg:grid-cols-2 gap-5">
    @foreach($results as $group => $rows)
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
        <h3 class="text-sm font-semibold text-white">{{ $group }} <span class="text-gray-500 font-normal">({{ $rows->count() }})</span></h3>
        <ul class="mt-2 divide-y divide-white/5">
            @forelse($rows as [$title, $sub, $url])
            <li class="py-2">
                @if($url)<a href="{{ $url }}" class="block hover:text-brand-400">@endif
                <span class="block text-sm text-gray-200 truncate">{{ $title }}</span><span class="block text-xs text-gray-500 truncate">{{ $sub }}</span>
                @if($url)</a>@endif
            </li>
            @empty
            <li class="py-2 text-xs text-gray-500">No matches.</li>
            @endforelse
        </ul>
    </section>
    @endforeach
</div>
@endif
@endsection
