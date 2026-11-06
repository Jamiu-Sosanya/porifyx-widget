@extends('layouts.app')
@section('title', 'Integrations')
@section('page-title', 'Integrations')
@section('content')
@php
    $pill = ['on' => 'bg-emerald-500/15 text-emerald-400', 'off' => 'bg-white/5 text-gray-400', 'warn' => 'bg-amber-500/15 text-amber-400', 'info' => 'bg-accent-500/15 text-accent-300'];
    $all = collect($groups)->flatten(1);
@endphp
<div x-data="{ q: '', cat: 'all' }">
    <div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-5">
        <p class="flex-1 text-sm text-gray-400">Connect {{ $w->name }} to your website, CRM, calendars, channels and team tools.
            <span class="text-emerald-400 font-semibold">{{ $all->where('3.0', 'on')->count() }} connected</span>.</p>
        @if($widgets->count() > 1)
        <select onchange="location.href='?widget=' + this.value" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white">
            @foreach($widgets as $ww)<option value="{{ $ww->id }}" @selected($ww->id === $w->id)>{{ $ww->name }}</option>@endforeach
        </select>
        @endif
        <input x-model="q" placeholder="Search integrations" class="bg-white/5 border border-white/10 rounded-xl px-3.5 py-2 text-sm text-white lg:w-64">
    </div>
    <div class="flex flex-wrap gap-1.5 mb-6">
        <button type="button" @click="cat = 'all'" :class="cat === 'all' ? 'bg-brand-500 text-white' : 'bg-white/5 text-gray-300'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">All</button>
        @foreach(array_keys($groups) as $g)
        <button type="button" @click="cat = @js($g)" :class="cat === @js($g) ? 'bg-brand-500 text-white' : 'bg-white/5 text-gray-300'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">{{ $g }}</button>
        @endforeach
    </div>

    @foreach($groups as $g => $items)
    <section class="mb-8" x-show="cat === 'all' || cat === @js($g)">
        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $g }}</h3>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
            @foreach($items as [$name, $icon, $desc, [$state, $label], $url])
            <a href="{{ $url }}" x-show="!q || @js(mb_strtolower($name . ' ' . $desc)).includes(q.toLowerCase())"
               class="group flex gap-3 rounded-2xl border border-white/5 bg-gray-900/50 p-4 hover:border-brand-500/40 hover:bg-white/[0.03] transition">
                <span class="grid place-items-center w-11 h-11 shrink-0 rounded-xl bg-white/5 text-xl">{{ $icon }}</span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-2"><span class="text-sm font-semibold text-white truncate">{{ $name }}</span>
                        <span class="ml-auto shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $pill[$state] }}">{{ $label }}</span></span>
                    <span class="mt-1 block text-xs text-gray-400 leading-relaxed">{{ $desc }}</span>
                    <span class="mt-2 block text-[11px] font-semibold text-brand-400 opacity-0 group-hover:opacity-100 transition">{{ $state === 'on' ? 'Manage' : 'Set up' }} →</span>
                </span>
            </a>
            @endforeach
        </div>
    </section>
    @endforeach
</div>
@endsection
