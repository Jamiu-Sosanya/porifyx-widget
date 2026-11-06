@extends('layouts.admin')
@section('title', 'Marketing site')
@section('page-title', 'Marketing site')
@section('content')
@php
    $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white';
    $swatch = ['aurora' => 'linear-gradient(135deg,#f6f7fb 50%,#08122a 50%)', 'midnight' => 'radial-gradient(circle at 80% 0,#2b4a7a,#08122a 60%)',
               'daylight' => 'linear-gradient(180deg,#fff,#eef1f7)', 'sunset' => 'linear-gradient(160deg,#ff6a2b,#7d2b12 45%,#0d1a33)', 'prism' => 'radial-gradient(circle at 10% 10%,#ffc9aa,transparent 50%),radial-gradient(circle at 90% 0,#aec9ec,transparent 50%),#fbfcff'];
@endphp
<form method="POST" action="{{ route('admin.marketing.save') }}" x-data="{ theme: @js($current), preview: null }" class="space-y-6">
    @csrf @method('PUT')
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6">
        <h3 class="text-base font-semibold text-white">Theme</h3>
        <p class="mt-1 text-xs text-gray-500">Preview any theme on the real homepage, then apply it.</p>
        <div class="mt-4 grid sm:grid-cols-2 xl:grid-cols-5 gap-3">
            @foreach(\App\Support\MarketingTheme::THEMES as $key => [$label])
            <div class="rounded-2xl border p-3 transition" :class="theme === '{{ $key }}' ? 'border-brand-500 ring-2 ring-brand-500/30' : 'border-white/10'">
                <div class="h-24 rounded-xl border border-white/10" style="background: {{ $swatch[$key] }}"></div>
                <p class="mt-3 text-sm font-semibold text-white">{{ \Illuminate\Support\Str::before($label, ' (') }}</p>
                <p class="text-[11px] text-gray-500">{{ \Illuminate\Support\Str::between($label, '(', ')') }}</p>
                <div class="mt-3 flex gap-2">
                    <button type="button" @click="preview = '{{ $key }}'" class="flex-1 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">Preview</button>
                    <button type="button" @click="theme = '{{ $key }}'" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-semibold" :class="theme === '{{ $key }}' ? 'bg-brand-500 text-white' : 'bg-white/5 text-gray-300'" x-text="theme === '{{ $key }}' ? 'Selected' : 'Select'"></button>
                </div>
            </div>
            @endforeach
        </div>
        <input type="hidden" name="marketing_theme" :value="theme">
    </section>

    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 grid sm:grid-cols-2 gap-3">
        <h3 class="sm:col-span-2 text-base font-semibold text-white">Homepage headline</h3>
        <label class="text-xs text-gray-400">Headline<input name="hero_title" value="{{ $hero['title'] }}" maxlength="120" class="mt-1 {{ $field }}"></label>
        <label class="text-xs text-gray-400">Coloured ending<input name="hero_highlight" value="{{ $hero['highlight'] }}" maxlength="80" class="mt-1 {{ $field }}"></label>
        <label class="sm:col-span-2 text-xs text-gray-400">Subtitle<textarea name="hero_subtitle" rows="2" maxlength="400" class="mt-1 {{ $field }}">{{ $hero['subtitle'] }}</textarea></label>
        <label class="sm:col-span-2 text-xs text-gray-400">Guarantee line under pricing (optional)<input name="money_back_text" value="{{ $guarantee }}" maxlength="160" class="mt-1 {{ $field }}"></label>
    </section>

    <button class="px-6 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold">Apply</button>

    {{-- Live preview --}}
    <div x-show="preview" x-cloak class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm p-3 sm:p-6 flex flex-col" @keydown.escape.window="preview = null">
        <div class="flex items-center justify-between gap-3 mb-3">
            <p class="text-sm font-semibold text-white">Previewing: <span x-text="preview"></span></p>
            <div class="flex gap-2">
                <button type="button" @click="theme = preview; preview = null" class="px-4 py-2 rounded-lg bg-brand-500 text-white text-xs font-semibold">Use this theme</button>
                <button type="button" @click="preview = null" class="px-4 py-2 rounded-lg bg-white/10 text-white text-xs font-semibold">Close</button>
            </div>
        </div>
        <template x-if="preview"><iframe :src="@js(url('/')) + '?preview_theme=' + preview" class="flex-1 w-full rounded-2xl bg-white border border-white/10" title="Theme preview"></iframe></template>
    </div>
</form>
@endsection
