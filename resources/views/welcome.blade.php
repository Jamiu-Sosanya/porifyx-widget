<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1324">
    <title>{{ config('branding.name') }} — {{ config('branding.tagline') }}</title>
    <meta name="description" content="{{ config('branding.description') }}">
    <meta property="og:title" content="{{ config('branding.name') }}">
    <meta property="og:description" content="{{ config('branding.description') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        html, body { overflow-x: hidden; }
        body { background: #f6f7fb; color: #0b1324; }
        .pfx-mesh { background: radial-gradient(60rem 40rem at 85% -10%, #2f5ee855, transparent 60%), radial-gradient(40rem 30rem at -10% 110%, #06b6d433, transparent 60%), #0b1324; }
        .pfx-grid { background-image: linear-gradient(#ffffff0a 1px, transparent 1px), linear-gradient(90deg, #ffffff0a 1px, transparent 1px); background-size: 48px 48px; mask-image: linear-gradient(to bottom, #000 40%, transparent); }
        .pfx-rise { animation: pfxRise .7s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes pfxRise { from { opacity: 0; transform: translateY(16px); } }
        .pfx-dot { animation: pfxDot 1.2s infinite; }
        .pfx-dot:nth-child(2) { animation-delay: .15s; } .pfx-dot:nth-child(3) { animation-delay: .3s; }
        @keyframes pfxDot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
        @media (prefers-reduced-motion: reduce) { .pfx-rise, .pfx-dot { animation: none; } }
    </style>
</head>
@php
    $name = config('branding.name');
    $fmt  = fn ($v) => ($v === null || $v < 0) ? 'Unlimited' : number_format($v);
@endphp
<body class="antialiased font-sans" x-data="{ yearly: false, menu: false }">

{{-- ===== Hero (dark) ===== --}}
<div class="pfx-mesh text-white relative">
    <div class="absolute inset-0 pfx-grid pointer-events-none"></div>

    <header class="relative z-30">
        <nav class="max-w-6xl mx-auto px-5 h-[72px] flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-extrabold tracking-tight text-lg">
                @include('layouts.partials.logo', ['size' => 34]) <span>{{ $name }}</span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="#features" class="hover:text-white">Features</a>
                <a href="#how" class="hover:text-white">How it works</a>
                <a href="#pricing" class="hover:text-white">Pricing</a>
                <a href="#faq" class="hover:text-white">FAQ</a>
            </div>
            <div class="hidden md:flex items-center gap-2 text-sm font-semibold">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl bg-white text-slate-900 hover:bg-slate-200">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2.5 text-slate-200 hover:text-white">Sign in</a>
                    <a href="{{ route('register') }}" class="px-4 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-400">Start free</a>
                @endauth
            </div>
            <button class="md:hidden p-2 -mr-2" @click="menu = !menu" :aria-expanded="menu" aria-label="Open menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path x-show="!menu" d="M4 7h16M4 12h16M4 17h16"/><path x-show="menu" x-cloak d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </nav>
        <div x-show="menu" x-cloak x-transition.opacity class="md:hidden absolute inset-x-0 top-[72px] bg-[#0b1324] border-y border-white/10 px-5 py-5 flex flex-col gap-4 font-medium" @click="menu = false">
            <a href="#features">Features</a><a href="#how">How it works</a><a href="#pricing">Pricing</a><a href="#faq">FAQ</a>
            <div class="grid grid-cols-2 gap-3 pt-2">
                <a href="{{ route('login') }}" class="py-3 rounded-xl border border-white/20 text-center">Sign in</a>
                <a href="{{ route('register') }}" class="py-3 rounded-xl bg-brand-500 text-center">Start free</a>
            </div>
        </div>
    </header>

    <section class="relative z-10 max-w-6xl mx-auto px-5 pt-10 pb-20 md:pt-16 md:pb-28 grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <div class="min-w-0 pfx-rise">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Live demo — ask the assistant anything
            </span>
            <h1 class="mt-6 text-[2.5rem] leading-[1.05] sm:text-5xl lg:text-[3.75rem] font-extrabold tracking-tight">
                Turn website visitors into <span class="bg-gradient-to-r from-brand-300 to-accent-300 bg-clip-text text-transparent">customers, 24/7.</span>
            </h1>
            <p class="mt-6 text-base sm:text-lg text-slate-300 max-w-xl">{{ config('branding.description') }}</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <a href="{{ route('register') }}" class="px-6 py-4 rounded-xl bg-brand-500 hover:bg-brand-400 font-semibold text-center shadow-lg shadow-brand-500/30">Start your free trial</a>
                <a href="#how" class="px-6 py-4 rounded-xl border border-white/20 hover:bg-white/5 font-semibold text-center">See how it works</a>
            </div>
            <ul class="mt-7 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-400">
                <li>✓ {{ config('branding.trial_days') }}-day free trial</li><li>✓ No card required</li><li>✓ Live in 10 minutes</li>
            </ul>
        </div>

        {{-- Live demo assistant --}}
        <div id="demo" class="min-w-0 pfx-rise" style="animation-delay:.1s" x-data="pfxChat()">
            <div class="mx-auto w-full max-w-md rounded-3xl bg-white text-slate-800 shadow-2xl shadow-black/40 overflow-hidden flex flex-col h-[460px] sm:h-[520px]">
                <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-brand-500 to-brand-600 text-white">
                    <span class="grid place-items-center w-10 h-10 rounded-full bg-white text-brand-600 font-extrabold">P</span>
                    <div class="min-w-0">
                        <p class="font-bold leading-tight">{{ $name }} Assistant</p>
                        <p class="text-xs text-white/80 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span> Online · replies instantly</p>
                    </div>
                </div>
                <div x-ref="log" class="flex-1 overflow-y-auto px-4 py-5 space-y-3 bg-slate-50" aria-live="polite">
                    <template x-for="(m, i) in messages" :key="i">
                        <div :class="m.role === 'user' ? 'flex justify-end' : 'flex gap-2'">
                            <span x-show="m.role !== 'user'" class="w-7 h-7 shrink-0 rounded-full bg-brand-500 text-white grid place-items-center text-xs font-bold">P</span>
                            <p :class="m.role === 'user' ? 'bg-brand-500 text-white rounded-2xl rounded-br-md' : 'bg-white border border-slate-200 rounded-2xl rounded-tl-md'"
                               class="px-3.5 py-2.5 text-sm leading-relaxed max-w-[82%] break-words whitespace-pre-line" x-text="m.content"></p>
                        </div>
                    </template>
                    <div x-show="busy" x-cloak class="flex gap-2">
                        <span class="w-7 h-7 shrink-0 rounded-full bg-brand-500 text-white grid place-items-center text-xs font-bold">P</span>
                        <p class="bg-white border border-slate-200 rounded-2xl rounded-tl-md px-4 py-3 flex gap-1">
                            <span class="pfx-dot w-1.5 h-1.5 rounded-full bg-slate-400"></span><span class="pfx-dot w-1.5 h-1.5 rounded-full bg-slate-400"></span><span class="pfx-dot w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        </p>
                    </div>
                </div>
                <div x-show="messages.length < 3" class="px-4 pt-3 flex gap-2 overflow-x-auto bg-white">
                    <template x-for="s in starters" :key="s">
                        <button type="button" @click="send(s)" class="shrink-0 text-xs font-medium px-3 py-1.5 rounded-full border border-brand-200 text-brand-600 hover:bg-brand-50" x-text="s"></button>
                    </template>
                </div>
                <form @submit.prevent="send()" class="p-3 flex gap-2 bg-white">
                    <label for="pfx-demo-input" class="sr-only">Message</label>
                    <input id="pfx-demo-input" x-ref="input" x-model="draft" maxlength="500" autocomplete="off" placeholder="Ask about pricing, setup, AI models…"
                           class="flex-1 min-w-0 rounded-xl border border-slate-200 px-4 py-3 text-base sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                    <button type="submit" :disabled="busy || !draft.trim()" aria-label="Send" class="shrink-0 w-12 rounded-xl bg-brand-500 text-white grid place-items-center disabled:opacity-40">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3.4 20.4l17.45-7.48a1 1 0 0 0 0-1.84L3.4 3.6a.99.99 0 0 0-1.39.91L2 9.12c0 .5.37.93.87.99L17 12 2.87 13.88c-.5.07-.87.5-.87 1l.01 4.61c0 .71.73 1.2 1.39.91z"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>

{{-- ===== Results strip ===== --}}
<section class="bg-white border-b border-slate-200">
    <div class="max-w-6xl mx-auto px-5 py-10 grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
        @foreach([['24/7', 'Answers without extra hires'], ['5', 'AI providers, one switch'], ['10', 'Channels, one inbox'], ['1 line', 'Of code to go live']] as [$big, $small])
        <div><p class="text-2xl sm:text-3xl font-extrabold tracking-tight text-brand-600">{{ $big }}</p><p class="text-sm text-slate-500 mt-1">{{ $small }}</p></div>
        @endforeach
    </div>
</section>

{{-- ===== Features bento ===== --}}
<section id="features" class="max-w-6xl mx-auto px-5 py-20 md:py-28">
    <p class="text-sm font-bold uppercase tracking-widest text-brand-600">Everything in one assistant</p>
    <h2 class="mt-3 text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight max-w-3xl">Answers, leads and support, handled automatically.</h2>
    <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach([
            ['Trained on your business', 'Learns from your website, PDFs and Q&A. Strict mode keeps every answer on-topic and accurate.', 'M12 6.25v13m0-13C10.83 5.48 9.25 5 7.5 5S4.17 5.48 3 6.25v13C4.17 18.48 5.75 18 7.5 18s3.33.48 4.5 1.25m0-13C13.17 5.48 14.75 5 16.5 5s3.33.48 4.5 1.25v13C19.83 18.48 18.25 18 16.5 18s-3.33.48-4.5 1.25', 'lg:col-span-2 bg-[#0b1324] text-white'],
            ['Leads scored for you', 'Contact details captured mid-conversation and scored cold, warm or hot.', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', ''],
            ['Human handoff', 'Agents take over in one shared inbox with AI summaries and suggested replies.', 'M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.28.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z', ''],
            ['Any AI model, with backup', 'OpenAI, Claude, Gemini, Groq or OpenRouter, with automatic failover if one goes down.', 'M13 10V3L4 14h7v7l9-11h-7z', ''],
            ['Automations & webhooks', 'Send hot leads to your CRM, alert your team on keywords and trigger follow-ups with signed webhooks.', 'M4 4v5h.58m15.36 2A8 8 0 0 0 4.58 9m0 0H9m11 11v-5h-.58m0 0a8 8 0 0 1-15.36-2m15.36 2H15', ''],
            ['Analytics that matter', 'Conversations, conversion, CSAT, sentiment and the questions the AI could not answer.', 'M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z', ''],
            ['Looks like your brand', 'Colours, avatar, launcher, tone and welcome message, previewed live. A/B test variants to find what converts.', 'M7 21a4 4 0 0 1-4-4V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v12a4 4 0 0 1-4 4zm0 0h12a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.34M11 7.34l1.66-1.66a2 2 0 0 1 2.83 0l2.83 2.83a2 2 0 0 1 0 2.83L9.83 19.83', 'lg:col-span-2'],
        ] as [$title, $body, $icon, $extra])
        <article class="rounded-3xl p-7 border border-slate-200 {{ $extra ?: 'bg-white' }} {{ str_contains($extra, 'text-white') ? 'border-transparent' : '' }}">
            <span class="grid place-items-center w-11 h-11 rounded-xl {{ str_contains($extra, 'text-white') ? 'bg-white/10 text-accent-300' : 'bg-brand-50 text-brand-600' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
            </span>
            <h3 class="mt-5 text-xl font-bold">{{ $title }}</h3>
            <p class="mt-2 leading-relaxed {{ str_contains($extra, 'text-white') ? 'text-slate-300' : 'text-slate-600' }}">{{ $body }}</p>
        </article>
        @endforeach
    </div>
</section>

{{-- ===== How it works ===== --}}
<section id="how" class="bg-white border-y border-slate-200">
    <div class="max-w-6xl mx-auto px-5 py-20 md:py-28">
        <p class="text-sm font-bold uppercase tracking-widest text-brand-600">How it works</p>
        <h2 class="mt-3 text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight">Live this afternoon.</h2>
        <ol class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['Teach it', 'Add your URLs, documents or Q&A. Training runs in the background.'],
                ['Style it', 'Choose colours, tone, AI model and greeting. Preview every change.'],
                ['Install it', 'Paste one script tag, or connect WhatsApp, Telegram and more.'],
                ['Grow it', 'Review conversations and leads, fill knowledge gaps, convert more.'],
            ] as $i => [$t, $d])
            <li class="relative rounded-3xl bg-slate-50 border border-slate-200 p-6">
                <span class="grid place-items-center w-10 h-10 rounded-full bg-brand-500 text-white font-bold">{{ $i + 1 }}</span>
                <h3 class="mt-5 font-bold text-lg">{{ $t }}</h3>
                <p class="mt-1.5 text-slate-600">{{ $d }}</p>
            </li>
            @endforeach
        </ol>
        <div class="mt-6 rounded-2xl bg-[#0b1324] p-5 sm:p-6 min-w-0">
            <p class="text-xs font-semibold text-slate-400 mb-3">Your entire installation:</p>
            <code class="block text-[13px] sm:text-sm text-accent-300 break-all leading-6">&lt;script src="{{ url('widget.js') }}" data-widget="YOUR-ID" async&gt;&lt;/script&gt;</code>
        </div>
    </div>
</section>

{{-- ===== Channels ===== --}}
<section class="max-w-6xl mx-auto px-5 py-20 md:py-24 text-center">
    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">One assistant. Every channel.</h2>
    <p class="mt-3 text-slate-600 max-w-xl mx-auto">Train once and answer customers wherever they message you, with every conversation in one inbox.</p>
    <div class="mt-10 flex flex-wrap justify-center gap-2.5">
        @foreach(['Website', 'WhatsApp', 'Telegram', 'Messenger', 'Instagram', 'X', 'Slack', 'Discord', 'SMS', 'Email'] as $ch)
        <span class="px-4 py-2 rounded-full bg-white border border-slate-200 text-sm font-semibold shadow-sm">{{ $ch }}</span>
        @endforeach
    </div>
</section>

{{-- ===== Pricing ===== --}}
@if($plans->count())
<section id="pricing" class="bg-white border-y border-slate-200">
    <div class="max-w-6xl mx-auto px-5 py-20 md:py-28">
        <div class="text-center">
            <p class="text-sm font-bold uppercase tracking-widest text-brand-600">Pricing</p>
            <h2 class="mt-3 text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight">Plans that grow with you.</h2>
            <div class="mt-8 inline-flex p-1 rounded-xl bg-slate-100 text-sm font-semibold">
                <button @click="yearly = false" :class="!yearly ? 'bg-white shadow text-slate-900' : 'text-slate-500'" class="px-5 py-2 rounded-lg">Monthly</button>
                <button @click="yearly = true" :class="yearly ? 'bg-white shadow text-slate-900' : 'text-slate-500'" class="px-5 py-2 rounded-lg">Yearly</button>
            </div>
        </div>
        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-{{ min($plans->count(), 4) }}">
            @foreach($plans as $plan)
            <div class="rounded-3xl p-7 flex flex-col {{ $plan->is_popular ? 'bg-[#0b1324] text-white ring-2 ring-brand-500 shadow-2xl shadow-brand-500/20' : 'bg-slate-50 border border-slate-200' }}">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-bold text-lg">{{ $plan->name }}</h3>
                    @if($plan->is_popular)<span class="text-[11px] font-bold uppercase tracking-wider bg-brand-500 rounded-full px-2.5 py-1">Popular</span>@endif
                </div>
                <p class="mt-2 text-sm {{ $plan->is_popular ? 'text-slate-400' : 'text-slate-500' }}">{{ $plan->description }}</p>
                <p class="mt-6 text-4xl font-extrabold tracking-tight">
                    <span x-show="!yearly">${{ number_format($plan->price_monthly, 0) }}</span>
                    <span x-show="yearly" x-cloak>${{ number_format($plan->price_yearly / 12, 0) }}</span>
                    <span class="text-base font-medium opacity-60">/mo</span>
                </p>
                <ul class="mt-6 space-y-2.5 text-sm flex-1">
                    @foreach(array_filter([
                        $fmt($plan->widgets_limit) . ' widgets',
                        $fmt($plan->messages_per_month) . ' messages / month',
                        $fmt($plan->leads_per_month) . ' leads / month',
                        $fmt($plan->team_members_limit) . ' team seats',
                        $plan->has_live_chat ? 'Live agent takeover' : null,
                        $plan->has_webhooks ? 'Webhooks & automations' : null,
                        $plan->has_ai_insights ? 'AI insights' : null,
                        $plan->has_custom_branding ? 'Your own branding' : null,
                        $plan->has_priority_support ? 'Priority support' : null,
                    ]) as $line)
                    <li class="flex gap-2"><span class="text-brand-500 font-bold">✓</span>{{ $line }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('register', ['plan' => $plan->slug]) }}"
                   class="mt-8 text-center py-3.5 rounded-xl font-semibold {{ $plan->is_popular ? 'bg-brand-500 hover:bg-brand-400 text-white' : 'bg-slate-900 text-white hover:bg-slate-700' }}">
                    {{ $plan->price_monthly > 0 ? 'Start free trial' : 'Get started free' }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== FAQ ===== --}}
<section id="faq" class="max-w-3xl mx-auto px-5 py-20 md:py-28">
    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-center">Questions, answered.</h2>
    <p class="mt-3 text-center text-slate-600">Or ask the <a href="#demo" class="text-brand-600 font-semibold underline underline-offset-4">live assistant</a> above.</p>
    <div class="mt-10 space-y-3">
        @foreach($faq as [, $q, $a])
        <details class="group rounded-2xl bg-white border border-slate-200 px-5 open:shadow-sm">
            <summary class="flex justify-between items-center gap-4 cursor-pointer py-5 font-semibold list-none">{{ $q }}
                <span class="shrink-0 grid place-items-center w-7 h-7 rounded-full bg-brand-50 text-brand-600 transition group-open:rotate-45">+</span></summary>
            <p class="pb-5 -mt-1 text-slate-600 leading-relaxed">{{ $a }}</p>
        </details>
        @endforeach
    </div>
</section>

{{-- ===== CTA ===== --}}
<section class="px-5 pb-20">
    <div class="pfx-mesh max-w-6xl mx-auto rounded-[2rem] text-white px-7 py-14 md:px-14 md:py-16 text-center">
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight">Your best salesperson,<br class="hidden sm:block"> on every page.</h2>
        <p class="mt-4 text-slate-300">Start free. Keep it if it earns its place.</p>
        <a href="{{ route('register') }}" class="mt-8 inline-block px-8 py-4 rounded-xl bg-white text-slate-900 font-bold hover:bg-slate-200">Create my assistant</a>
    </div>
</section>

<footer class="border-t border-slate-200 bg-white">
    <div class="max-w-6xl mx-auto px-5 py-10 flex flex-col md:flex-row gap-6 justify-between text-sm text-slate-500">
        <div class="flex items-center gap-2.5 font-bold text-slate-800">@include('layouts.partials.logo', ['size' => 26]) {{ $name }}</div>
        <div class="flex flex-wrap gap-x-6 gap-y-2">
            <a href="{{ route('privacy') }}" class="hover:text-slate-900">Privacy</a>
            <a href="{{ route('terms') }}" class="hover:text-slate-900">Terms</a>
            <a href="{{ route('contact') }}" class="hover:text-slate-900">Contact</a>
            <a href="mailto:{{ config('branding.support_email') }}" class="hover:text-slate-900">{{ config('branding.support_email') }}</a>
        </div>
        <p>{{ config('branding.copyright') ?: '© ' . date('Y') . ' ' . $name }}</p>
    </div>
</footer>

{{-- Floating launcher: jumps to the live assistant --}}
<a href="#demo" @click="setTimeout(() => document.getElementById('pfx-demo-input').focus({ preventScroll: true }), 500)" aria-label="Chat with us"
   class="fixed z-40 right-4 bottom-[max(1rem,env(safe-area-inset-bottom))] w-14 h-14 rounded-[18px] bg-brand-500 text-white grid place-items-center shadow-xl shadow-brand-500/40 hover:bg-brand-400">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M5 3h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-8l-5 4v-4H5a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z" fill="white"/><path d="M12 6.5l1.2 2.8 2.8 1.2-2.8 1.2L12 14.5l-1.2-2.8L8 10.5l2.8-1.2z" fill="#2f5ee8"/></svg>
</a>

<script>
function pfxChat() {
    return {
        messages: [], draft: '', busy: false,
        starters: ['How much does it cost?', 'How do I install it?', 'Which AI models?', 'Can my team take over?'],
        init() { this.messages.push({ role: 'assistant', content: "Hi! I'm the {{ $name }} assistant. Ask me anything about pricing, setup or features." }); },
        async send(text) {
            const message = (text ?? this.draft).trim();
            if (!message || this.busy) return;
            const history = this.messages.slice(1).slice(-10);
            this.messages.push({ role: 'user', content: message });
            this.draft = ''; this.busy = true; this.scroll();
            try {
                const res = await fetch(@js(route('demo.chat')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ message, history }),
                });
                const data = await res.json();
                this.messages.push({ role: 'assistant', content: res.ok ? data.reply : (res.status === 429 ? 'You are sending messages quickly. Please wait a moment.' : 'Sorry, something went wrong. Please try again.') });
            } catch (e) {
                this.messages.push({ role: 'assistant', content: 'Connection lost. Please try again.' });
            }
            this.busy = false; this.scroll();
        },
        scroll() { this.$nextTick(() => { this.$refs.log.scrollTop = this.$refs.log.scrollHeight; }); },
    };
}
</script>
@include('layouts.partials.cookie-consent')
</body>
</html>
