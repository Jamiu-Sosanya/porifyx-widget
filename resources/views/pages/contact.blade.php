@extends('layouts.site')
@section('title', 'Contact sales')
@section('description', "Talk to the {$name} team about pricing, a demo, setup help or anything else.")
@section('eyebrow', 'Contact')
@section('heading', "Let's talk about what your website could be doing.")
@section('lead', 'Tell us a little about your business and what you need. A real person on our team reads every message and replies within one business day, usually much sooner.')
@section('no-cta', true)

@php
    $input = 'w-full rounded-xl px-4 py-3 text-[15px] bg-white border border-slate-300 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 dark:bg-white/[0.04] dark:border-white/15 dark:text-white dark:placeholder:text-slate-500';
    $label = 'block text-sm font-semibold mb-2';
    $sent  = session('contact_sent');
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-5 py-14 md:py-20 grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)] gap-12 items-start">

    {{-- What happens next --}}
    <div class="space-y-8 lg:sticky lg:top-24">
        <div>
            <p class="text-xl font-bold">What happens after you send this</p>
            <ol class="mt-6 space-y-6">
                @foreach([
                    ['We read it properly', 'Your message goes to the right person straight away: sales, support or partnerships. Nothing disappears into a ticket queue.'],
                    ['We reply within one business day', 'Usually within a few hours. If you asked about pricing, we will include figures that fit the size of your business.'],
                    ['We show you, not just tell you', 'If it helps, we will book a 30-minute call and build a working assistant from your own website while you watch.'],
                ] as $i => [$title, $body])
                <li class="flex gap-4">
                    <span class="grid place-items-center w-9 h-9 shrink-0 rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-300 font-extrabold">{{ $i + 1 }}</span>
                    <div><p class="font-semibold">{{ $title }}</p><p class="mt-1 text-sm leading-relaxed {{ $muted }}">{{ $body }}</p></div>
                </li>
                @endforeach
            </ol>
        </div>

        <div class="rounded-2xl p-6 {{ $card }}">
            <p class="font-semibold">Other ways to reach us</p>
            <ul class="mt-4 space-y-4 text-sm">
                <li class="flex gap-3"><span class="text-brand-500">✉</span><div><p class="{{ $muted }}">Email</p><a href="mailto:{{ config('branding.support_email') }}" class="font-semibold break-all hover:text-brand-600">{{ config('branding.support_email') }}</a></div></li>
                <li class="flex gap-3"><span class="text-brand-500">💬</span><div><p class="{{ $muted }}">Quick question?</p><a href="{{ route('home') }}#demo" class="font-semibold hover:text-brand-600">Ask our live assistant</a></div></li>
                <li class="flex gap-3"><span class="text-brand-500">📘</span><div><p class="{{ $muted }}">Setting things up?</p><a href="{{ route('documentation') }}" class="font-semibold hover:text-brand-600">Read the documentation</a></div></li>
            </ul>
        </div>
    </div>

    {{-- Form --}}
    <div id="form" class="scroll-mt-24 rounded-3xl p-6 sm:p-8 md:p-10 shadow-xl shadow-slate-900/5 {{ $card }}">
        @if($sent)
            <div class="text-center py-10">
                <div class="mx-auto grid place-items-center w-16 h-16 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="mt-6 text-2xl font-extrabold">Thanks{{ is_string($sent) ? ', ' . e(strtok($sent, ' ')) : '' }}. Your message is with us.</p>
                <p class="mt-3 {{ $muted }} max-w-md mx-auto">We will reply to your email within one business day. While you wait, you can try the assistant on our homepage or start a free trial.</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ route('register') }}" class="px-6 py-3 rounded-xl bg-brand-500 text-white font-semibold hover:bg-brand-600">Start free trial</a>
                    <a href="{{ route('contact') }}" class="px-6 py-3 rounded-xl border border-slate-300 dark:border-white/20 font-semibold">Send another message</a>
                </div>
            </div>
        @else
        <form method="POST" action="{{ route('contact.send') }}" x-data="{ topic: @js(old('topic', $topic)), sending: false, count: {{ strlen(old('message', '')) }} }" @submit="sending = true" novalidate>
            @csrf
            {{-- Honeypot: hidden from people, tempting to bots --}}
            <div class="hidden" aria-hidden="true"><label>Fax <input type="text" name="fax" tabindex="-1" autocomplete="off"></label></div>

            <fieldset>
                <legend class="{{ $label }}">How can we help?</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Models\ContactRequest::TOPICS as $key => $text)
                    <label class="cursor-pointer">
                        <input type="radio" name="topic" value="{{ $key }}" x-model="topic" class="peer sr-only">
                        <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold border border-slate-300 dark:border-white/15 peer-checked:bg-brand-500 peer-checked:border-brand-500 peer-checked:text-white peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/25 transition">{{ $text }}</span>
                    </label>
                    @endforeach
                </div>
            </fieldset>

            @if($errors->any())
            <div class="mt-6 rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                Please check the highlighted fields and try again.
            </div>
            @endif

            <div class="mt-7 grid sm:grid-cols-2 gap-5">
                @foreach([
                    ['name', 'Full name', 'text', 'Jane Smith', true, 'name'],
                    ['email', 'Work email', 'email', 'jane@company.com', true, 'email'],
                    ['company', 'Company', 'text', 'Company name', false, 'organization'],
                    ['website', 'Website', 'url', 'https://example.com', false, 'url'],
                ] as [$field, $text, $type, $ph, $req, $ac])
                <div>
                    <label for="{{ $field }}" class="{{ $label }}">{{ $text }} @unless($req)<span class="font-normal text-slate-400">(optional)</span>@endunless</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" placeholder="{{ $ph }}" autocomplete="{{ $ac }}" @if($req) required @endif
                           class="{{ $input }} @error($field) !border-rose-500 @enderror" @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                    @error($field)<p id="{{ $field }}-error" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                </div>
                @endforeach

                <div x-show="topic === 'sales'" x-transition>
                    <label for="team_size" class="{{ $label }}">Team size</label>
                    <select id="team_size" name="team_size" class="{{ $input }}" :disabled="topic !== 'sales'">
                        <option value="">Choose one</option>
                        @foreach(\App\Models\ContactRequest::TEAM_SIZES as $v)<option value="{{ $v }}" @selected(old('team_size') === $v)>{{ $v }} people</option>@endforeach
                    </select>
                </div>
                <div x-show="topic === 'sales'" x-transition>
                    <label for="volume" class="{{ $label }}">Chats per month</label>
                    <select id="volume" name="volume" class="{{ $input }}" :disabled="topic !== 'sales'">
                        <option value="">Choose one</option>
                        @foreach(\App\Models\ContactRequest::VOLUMES as $v)<option value="{{ $v }}" @selected(old('volume') === $v)>{{ $v }}</option>@endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="message" class="{{ $label }}">Message</label>
                    <textarea id="message" name="message" rows="6" maxlength="4000" required @input="count = $event.target.value.length"
                              :placeholder="topic === 'sales' ? 'What would you like the assistant to do on your site? Any deadlines or must-haves?' : topic === 'careers' ? 'Tell us about yourself and the kind of work you want to do. Links to your portfolio or GitHub are welcome.' : 'Tell us what you need and we will point you to the right person.'"
                              class="{{ $input }} resize-y @error('message') !border-rose-500 @enderror">{{ old('message') }}</textarea>
                    <div class="mt-1.5 flex justify-between text-sm">
                        <span class="text-rose-600 dark:text-rose-400">@error('message'){{ $message }}@enderror</span>
                        <span class="text-slate-400" x-text="count + ' / 4000'"></span>
                    </div>
                </div>
            </div>

            <label class="mt-6 flex gap-3 text-sm {{ $muted }} cursor-pointer">
                <input type="checkbox" name="consent" value="1" @checked(old('consent')) class="mt-0.5 w-4 h-4 rounded accent-brand-500 shrink-0">
                <span>I agree that {{ $name }} may reply to me by email about this enquiry. We never add you to a mailing list without asking. See our <a href="{{ route('privacy') }}" class="underline">privacy policy</a>.</span>
            </label>
            @error('consent')<p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror

            <button type="submit" :disabled="sending" class="mt-8 w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-brand-500 text-white font-bold hover:bg-brand-600 disabled:opacity-70 shadow-lg shadow-brand-500/25">
                <svg x-show="sending" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3"/></svg>
                <span x-text="sending ? 'Sending…' : 'Send message'">Send message</span>
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
