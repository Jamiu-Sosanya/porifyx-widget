@extends('layouts.admin')
@section('title', 'Admin: Cookie consent')
@section('page-title', 'Cookie consent')
@section('content')
@php
    $field = 'w-full rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500';
@endphp
<form method="POST" action="{{ route('admin.consent.update') }}" x-data="{ c: @js($consent) }"
      class="grid xl:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] gap-6 items-start">
    @csrf @method('PUT')

    <div class="space-y-6">
        {{-- Status --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 flex items-center justify-between gap-4">
            <div>
                <p class="font-semibold text-white">Show the cookie banner</p>
                <p class="text-sm text-gray-400">Shown on the website, sign-in pages and dashboard until each visitor makes a choice.</p>
            </div>
            <label class="cursor-pointer shrink-0">
                <input type="checkbox" name="enabled" value="1" x-model="c.enabled" class="peer sr-only">
                <span class="relative block w-11 h-6 rounded-full bg-white/15 peer-checked:bg-brand-500 transition after:absolute after:top-1 after:left-1 after:w-4 after:h-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
            </label>
        </div>

        {{-- Layout --}}
        <fieldset class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
            <legend class="sr-only">Layout</legend>
            <p class="font-semibold text-white">Layout</p>
            <div class="mt-4 grid sm:grid-cols-2 gap-3">
                @foreach($layouts as $key => [$label, $about])
                <label class="cursor-pointer">
                    <input type="radio" name="layout" value="{{ $key }}" x-model="c.layout" class="peer sr-only">
                    <div class="h-full rounded-xl border border-white/10 p-3.5 peer-checked:border-brand-500 peer-checked:bg-brand-500/10 transition">
                        {{-- Tiny diagram of where the banner sits --}}
                        <div class="relative h-14 rounded-lg bg-white/5 overflow-hidden mb-3">
                            @switch($key)
                                @case('bar')<span class="absolute inset-x-0 bottom-0 h-3 bg-brand-400/80"></span>@break
                                @case('box-left')<span class="absolute left-1.5 bottom-1.5 w-10 h-6 rounded bg-brand-400/80"></span>@break
                                @case('box-right')<span class="absolute right-1.5 bottom-1.5 w-10 h-6 rounded bg-brand-400/80"></span>@break
                                @default<span class="absolute inset-0 bg-black/40"></span><span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-14 h-8 rounded bg-brand-400/80"></span>
                            @endswitch
                        </div>
                        <p class="text-sm font-semibold text-white">{{ $label }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $about }}</p>
                    </div>
                </label>
                @endforeach
            </div>
            <label class="mt-4 flex items-start gap-3 text-sm text-gray-300 cursor-pointer">
                <input type="checkbox" name="block_page" value="1" x-model="c.block_page" class="mt-0.5 accent-brand-500">
                <span>Dim the page until the visitor chooses <span class="text-gray-500">(always on for the centre modal)</span></span>
            </label>
        </fieldset>

        {{-- Wording --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 space-y-4">
            <p class="font-semibold text-white">Wording</p>
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1.5">Title</label>
                <input name="title" x-model="c.title" maxlength="80" required class="{{ $field }}">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1.5">Message</label>
                <textarea name="message" x-model="c.message" rows="3" maxlength="600" required class="{{ $field }}"></textarea>
                <p class="mt-1 text-xs text-gray-500">A link to the cookie section of your privacy policy is added automatically.</p>
            </div>
            <div class="grid sm:grid-cols-3 gap-3">
                @foreach(['accept_label' => 'Accept button', 'reject_label' => 'Reject button', 'customize_label' => 'Customise button'] as $name => $label)
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1.5">{{ $label }}</label>
                    <input name="{{ $name }}" x-model="c.{{ $name }}" maxlength="30" required class="{{ $field }}">
                </div>
                @endforeach
            </div>
        </div>

        {{-- Choices --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 space-y-3">
            <p class="font-semibold text-white">Choices offered to visitors</p>
            @foreach([
                'show_reject'    => ['Show a reject button', 'Many privacy laws, including GDPR, expect rejecting to be as easy as accepting.'],
                'show_customize' => ['Let visitors choose categories', 'Adds a panel with a switch for each cookie category below.'],
                'analytics'      => ['Analytics cookies', 'You use tools such as Google Analytics, Plausible or Hotjar.'],
                'marketing'      => ['Marketing cookies', 'You use advertising pixels such as Meta, Google Ads or LinkedIn.'],
            ] as $name => [$label, $about])
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="{{ $name }}" value="1" x-model="c.{{ $name }}" class="mt-1 accent-brand-500">
                <span><span class="block text-sm text-white">{{ $label }}</span><span class="block text-xs text-gray-400">{{ $about }}</span></span>
            </label>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button class="px-5 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold">Save changes</button>
            <button name="reset" value="1" class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-semibold"
                    onclick="return confirm('Save and ask every visitor for consent again?')">Save and ask everyone again</button>
            <a href="{{ route('home') }}?cookie_preview=1" target="_blank" class="text-sm text-brand-400 hover:text-brand-300">Open live preview ↗</a>
        </div>
        <p class="text-xs text-gray-500">Version {{ $consent['version'] }}. Use "ask everyone again" after you add new tracking tools, so earlier answers no longer count.</p>
    </div>

    {{-- Live preview --}}
    <div class="xl:sticky xl:top-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Live preview</p>
        <div class="rounded-2xl border border-white/10 overflow-hidden bg-[#f6f7fb]">
            <div class="flex items-center gap-1.5 px-3 py-2 bg-slate-200">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <span class="ml-3 flex-1 h-5 rounded bg-white text-[10px] text-slate-400 px-2 leading-5 truncate">{{ url('/') }}</span>
            </div>
            <div class="relative h-[460px] p-6 overflow-hidden">
                <div class="space-y-3" aria-hidden="true">
                    <div class="h-6 w-1/2 rounded bg-slate-300"></div>
                    <div class="h-3 w-5/6 rounded bg-slate-200"></div><div class="h-3 w-4/6 rounded bg-slate-200"></div>
                    <div class="grid grid-cols-3 gap-3 pt-4"><div class="h-24 rounded-xl bg-slate-200"></div><div class="h-24 rounded-xl bg-slate-200"></div><div class="h-24 rounded-xl bg-slate-200"></div></div>
                </div>
                <p x-show="!c.enabled" class="absolute inset-0 grid place-items-center text-sm font-semibold text-slate-500 bg-white/70">The banner is switched off</p>
                <div x-show="c.enabled" class="absolute inset-0 pointer-events-none"
                     :class="(c.layout === 'modal' || c.block_page) ? 'bg-slate-900/45' : ''">
                    <div class="absolute bg-white text-slate-900 shadow-2xl border border-slate-200 p-4"
                         :class="{
                            'inset-x-0 bottom-0 border-x-0 border-b-0': c.layout === 'bar',
                            'left-3 bottom-3 w-[300px] rounded-xl': c.layout === 'box-left',
                            'right-3 bottom-3 w-[300px] rounded-xl': c.layout === 'box-right',
                            'left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[340px] rounded-xl': c.layout === 'modal',
                         }">
                        <div :class="c.layout === 'bar' ? 'flex items-center gap-4' : ''">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold" x-text="c.title"></p>
                                <p class="mt-1 text-xs text-slate-600 leading-relaxed" :class="c.layout === 'bar' ? 'line-clamp-2' : 'line-clamp-4'"><span x-text="c.message"></span> <span class="text-brand-600 font-semibold underline">Cookie policy</span></p>
                            </div>
                            <div class="flex flex-wrap gap-1.5 shrink-0" :class="c.layout === 'bar' ? '' : 'mt-3 justify-end'">
                                <span x-show="c.show_customize && (c.analytics || c.marketing)" class="px-2.5 py-1.5 text-[11px] font-semibold text-slate-600" x-text="c.customize_label"></span>
                                <span x-show="c.show_reject" class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold border border-slate-300" x-text="c.reject_label"></span>
                                <span class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-brand-500 text-white" x-text="c.accept_label"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-500">The real banner follows each visitor's light or dark mode automatically.</p>
    </div>
</form>
@endsection
