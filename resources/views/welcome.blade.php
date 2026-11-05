<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('branding.name') }} — {{ config('branding.tagline') }}</title>
    <meta name="description" content="{{ config('branding.description') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&family=jetbrains-mono:500&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: light; }
        body { background: #f7f8fb; color: #0b1324; }
        .mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .pfx-dots { background-image: radial-gradient(#2f5ee81f 1px, transparent 1px); background-size: 22px 22px; }
        .pfx-rise { animation: pfxRise .7s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes pfxRise { from { opacity: 0; transform: translateY(14px); } }
        @media (prefers-reduced-motion: reduce) { .pfx-rise { animation: none; } }
    </style>
</head>
@php
    $name = config('branding.name');
    $fmt  = fn ($v) => ($v === null || $v < 0) ? 'Unlimited' : number_format($v);
@endphp
<body class="antialiased font-sans" x-data="{ yearly: false, menu: false }">

{{-- Navigation --}}
<header class="sticky top-0 z-40 bg-[#f7f8fb]/85 backdrop-blur border-b border-slate-200/70">
    <nav class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 font-extrabold tracking-tight text-lg">
            <span class="grid place-items-center w-8 h-8 rounded-lg bg-brand-500 text-white text-sm">P</span>{{ $name }}
        </a>
        <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
            <a href="#jobs" class="hover:text-slate-900">Product</a>
            <a href="#how" class="hover:text-slate-900">How it works</a>
            <a href="#pricing" class="hover:text-slate-900">Pricing</a>
            <a href="#faq" class="hover:text-slate-900">FAQ</a>
        </div>
        <div class="hidden md:flex items-center gap-3 text-sm font-semibold">
            @auth
                <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-700">Open dashboard</a>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2 text-slate-700 hover:text-slate-900">Sign in</a>
                <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg bg-brand-500 text-white hover:bg-brand-600">Start free</a>
            @endauth
        </div>
        <button class="md:hidden p-2" @click="menu = !menu" aria-label="Menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </nav>
    <div x-show="menu" x-cloak class="md:hidden px-5 pb-4 flex flex-col gap-3 text-sm font-medium">
        <a href="#jobs">Product</a><a href="#how">How it works</a><a href="#pricing">Pricing</a>
        <a href="{{ route('login') }}">Sign in</a>
        <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg bg-brand-500 text-white text-center">Start free</a>
    </div>
</header>

{{-- Hero --}}
<section class="relative overflow-hidden pfx-dots">
    <div class="max-w-6xl mx-auto px-5 pt-20 pb-24 grid lg:grid-cols-12 gap-12 items-center">
        <div class="lg:col-span-7 pfx-rise">
            <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[.18em] text-brand-600">
                <span class="w-6 h-px bg-brand-500"></span> Conversational sales &amp; support
            </p>
            <h1 class="mt-5 text-5xl md:text-6xl font-extrabold leading-[1.02] tracking-tight">
                Every visitor gets an answer.<br><span class="text-brand-500">Every answer works for you.</span>
            </h1>
            <p class="mt-6 text-lg text-slate-600 max-w-xl">{{ config('branding.description') }}</p>
            <div class="mt-9 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="px-6 py-3.5 rounded-xl bg-brand-500 text-white font-semibold shadow-lg shadow-brand-500/25 hover:bg-brand-600">Build your assistant free</a>
                <a href="#how" class="px-6 py-3.5 rounded-xl border border-slate-300 font-semibold hover:border-slate-900">See how it works</a>
            </div>
            <p class="mt-5 text-sm text-slate-500">{{ config('branding.trial_days') }}-day free trial · No card required · Live in under 10 minutes</p>
        </div>

        {{-- Install card --}}
        <div class="lg:col-span-5 pfx-rise" style="animation-delay:.12s">
            <div class="rounded-2xl bg-[#0b1324] text-slate-200 shadow-2xl shadow-slate-900/20 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-white/10 text-xs text-slate-400">
                    <span class="mono">index.html</span><span>1 line. That's the install.</span>
                </div>
                <pre class="mono text-[12.5px] leading-6 px-5 py-5 overflow-x-auto"><span class="text-slate-500">&lt;!-- before &lt;/body&gt; --&gt;</span>
<span class="text-accent-400">&lt;script</span> src=<span class="text-amber-300">"{{ url('widget.js') }}"</span>
  data-widget=<span class="text-amber-300">"your-widget-id"</span> async<span class="text-accent-400">&gt;&lt;/script&gt;</span></pre>
                <div class="bg-white text-slate-800 m-4 mt-0 rounded-xl p-4 space-y-3 text-sm">
                    <div class="flex gap-2"><span class="w-7 h-7 shrink-0 rounded-full bg-brand-500 grid place-items-center text-white text-xs font-bold">P</span>
                        <p class="bg-slate-100 rounded-2xl rounded-tl-sm px-3 py-2">Hi! Looking for pricing or a quick demo?</p></div>
                    <p class="ml-auto w-fit bg-brand-500 text-white rounded-2xl rounded-tr-sm px-3 py-2">Pricing for a 12-person team</p>
                    <div class="flex gap-2"><span class="w-7 h-7 shrink-0 rounded-full bg-brand-500 grid place-items-center text-white text-xs font-bold">P</span>
                        <p class="bg-slate-100 rounded-2xl rounded-tl-sm px-3 py-2">The Team plan fits. Shall I email you a quote?</p></div>
                    <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Lead scored: hot · sent to CRM</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Metrics strip --}}
<section class="border-y border-slate-200 bg-white">
    <div class="max-w-6xl mx-auto px-5 py-10 grid grid-cols-2 md:grid-cols-4 gap-8">
        @foreach([['24/7', 'coverage without extra hires'], ['5', 'AI providers, one switch'], ['9', 'messaging channels'], ['< 1 s', 'typical first token']] as [$big, $small])
        <div><p class="text-3xl font-extrabold tracking-tight">{{ $big }}</p><p class="text-sm text-slate-500 mt-1">{{ $small }}</p></div>
        @endforeach
    </div>
</section>

{{-- Three jobs --}}
<section id="jobs" class="max-w-6xl mx-auto px-5 py-24">
    <h2 class="text-4xl font-extrabold tracking-tight max-w-2xl">One assistant. Three jobs your team no longer does by hand.</h2>
    <div class="mt-12 grid md:grid-cols-3 gap-5">
        @foreach([
            ['01', 'Answer', 'Trained on your pages, PDFs and Q&A. Replies stream in real time, in the visitor\'s language, and stay inside what you taught it.', ['Knowledge base with versions', 'Strict mode for on-topic replies', 'Voice input, files and emoji']],
            ['02', 'Qualify', 'Every conversation is scored cold, warm or hot. Contact details are captured as the visitor talks, then routed where you want them.', ['Lead scoring and segments', 'Automations and webhooks', 'Excel and PDF export']],
            ['03', 'Hand off', 'When a human should step in, an agent takes over in the shared inbox with an AI summary and suggested replies ready.', ['Live takeover and canned replies', 'Sentiment and CSAT', 'Team roles and alerts']],
        ] as [$n, $title, $body, $points])
        <article class="rounded-2xl bg-white border border-slate-200 p-7 hover:border-brand-300 hover:shadow-xl hover:shadow-brand-500/5 transition">
            <p class="mono text-sm text-brand-500">{{ $n }}</p>
            <h3 class="mt-3 text-2xl font-bold">{{ $title }}</h3>
            <p class="mt-3 text-slate-600 leading-relaxed">{{ $body }}</p>
            <ul class="mt-6 space-y-2 text-sm">
                @foreach($points as $pt)
                <li class="flex gap-2"><svg class="w-4 h-4 mt-0.5 text-brand-500 shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>{{ $pt }}</li>
                @endforeach
            </ul>
        </article>
        @endforeach
    </div>

    <div class="mt-5 rounded-2xl bg-[#0b1324] text-white p-8 md:p-10 grid md:grid-cols-2 gap-8 items-center">
        <div>
            <h3 class="text-2xl font-bold">Meet customers where they already talk.</h3>
            <p class="mt-3 text-slate-400">The same trained assistant answers on your site and in every connected channel, with one inbox for your team.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach(['Website', 'WhatsApp', 'Telegram', 'Messenger', 'Instagram', 'X', 'Slack', 'Discord', 'SMS', 'Email'] as $ch)
            <span class="px-3.5 py-1.5 rounded-full border border-white/15 text-sm">{{ $ch }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- How it works --}}
<section id="how" class="bg-white border-y border-slate-200">
    <div class="max-w-6xl mx-auto px-5 py-24 grid lg:grid-cols-12 gap-12">
        <div class="lg:col-span-4">
            <h2 class="text-4xl font-extrabold tracking-tight">Live this afternoon.</h2>
            <p class="mt-4 text-slate-600">No developers, no migration project. Four steps from sign-up to first qualified lead.</p>
        </div>
        <ol class="lg:col-span-8 divide-y divide-slate-200">
            @foreach([
                ['Teach it', 'Add your website URL, upload documents or paste answers. Training runs in the background.'],
                ['Shape it', 'Pick colours, tone, model and welcome message. Preview every change live.'],
                ['Place it', 'Paste one script tag, or connect WhatsApp, Telegram and more.'],
                ['Grow with it', 'Watch conversations, leads and answer quality in analytics, then refine.'],
            ] as $i => [$t, $d])
            <li class="py-6 flex gap-6">
                <span class="mono text-brand-500 text-sm pt-1">0{{ $i + 1 }}</span>
                <div><h3 class="font-bold text-lg">{{ $t }}</h3><p class="text-slate-600 mt-1">{{ $d }}</p></div>
            </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- Pricing --}}
@if($plans->count())
<section id="pricing" class="max-w-6xl mx-auto px-5 py-24">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <h2 class="text-4xl font-extrabold tracking-tight">Simple plans that scale with you.</h2>
        <div class="inline-flex p-1 rounded-xl bg-slate-200/70 text-sm font-semibold">
            <button @click="yearly = false" :class="!yearly ? 'bg-white shadow' : 'text-slate-500'" class="px-4 py-2 rounded-lg">Monthly</button>
            <button @click="yearly = true" :class="yearly ? 'bg-white shadow' : 'text-slate-500'" class="px-4 py-2 rounded-lg">Yearly</button>
        </div>
    </div>
    <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-{{ min($plans->count(), 4) }}">
        @foreach($plans as $plan)
        <div class="rounded-2xl p-7 flex flex-col {{ $plan->is_popular ? 'bg-brand-500 text-white shadow-2xl shadow-brand-500/30' : 'bg-white border border-slate-200' }}">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-lg">{{ $plan->name }}</h3>
                @if($plan->is_popular)<span class="text-[11px] font-bold uppercase tracking-wider bg-white/20 rounded-full px-2.5 py-1">Most chosen</span>@endif
            </div>
            <p class="mt-2 text-sm {{ $plan->is_popular ? 'text-white/80' : 'text-slate-500' }}">{{ $plan->description }}</p>
            <p class="mt-6 text-4xl font-extrabold tracking-tight">
                <span x-show="!yearly">${{ number_format($plan->price_monthly, 0) }}</span>
                <span x-show="yearly" x-cloak>${{ number_format($plan->price_yearly / 12, 0) }}</span>
                <span class="text-base font-medium opacity-70">/mo</span>
            </p>
            <ul class="mt-6 space-y-2.5 text-sm flex-1">
                <li>{{ $fmt($plan->widgets_limit) }} widgets</li>
                <li>{{ $fmt($plan->messages_per_month) }} messages / month</li>
                <li>{{ $fmt($plan->leads_per_month) }} leads / month</li>
                <li>{{ $fmt($plan->team_members_limit) }} team seats</li>
                @if($plan->has_live_chat)<li>Live agent takeover</li>@endif
                @if($plan->has_webhooks)<li>Webhooks &amp; automations</li>@endif
                @if($plan->has_ai_insights)<li>AI insights</li>@endif
                @if($plan->has_custom_branding)<li>Your own branding</li>@endif
                @if($plan->has_priority_support)<li>Priority support</li>@endif
            </ul>
            <a href="{{ route('register', ['plan' => $plan->slug ?? $plan->id]) }}"
               class="mt-8 text-center py-3 rounded-xl font-semibold {{ $plan->is_popular ? 'bg-white text-brand-600 hover:bg-brand-50' : 'bg-slate-900 text-white hover:bg-slate-700' }}">
                {{ $plan->price_monthly > 0 ? 'Start free trial' : 'Get started' }}
            </a>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- FAQ --}}
<section id="faq" class="bg-white border-t border-slate-200">
    <div class="max-w-3xl mx-auto px-5 py-24">
        <h2 class="text-4xl font-extrabold tracking-tight">Questions, answered.</h2>
        <div class="mt-10 divide-y divide-slate-200 border-y border-slate-200">
            @foreach([
                ['Which AI models can I use?', 'OpenAI, Anthropic Claude, Google Gemini, Groq and OpenRouter. Each assistant can use a different model, and a backup provider takes over automatically if one fails.'],
                ['Will it make things up?', 'Strict mode keeps answers inside your knowledge base. When it does not know, it says so and offers a human.'],
                ['Does it slow down my site?', 'No. The script loads asynchronously and runs isolated from your page styles.'],
                ['Can I use my own branding?', 'Yes. Colours, avatar, name and launcher are yours, and higher plans remove the footer credit.'],
                ['Is visitor data safe?', 'Data is stored on your own server. Webhooks are HMAC-signed, logins support two-factor, and admin access can be IP-restricted.'],
            ] as [$q, $a])
            <details class="group py-5">
                <summary class="flex justify-between items-center cursor-pointer font-semibold list-none">{{ $q }}
                    <span class="text-brand-500 text-xl transition group-open:rotate-45">+</span></summary>
                <p class="mt-3 text-slate-600">{{ $a }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="max-w-6xl mx-auto px-5 py-20">
    <div class="rounded-3xl bg-brand-500 text-white px-8 py-14 md:px-14 flex flex-col md:flex-row gap-8 md:items-center justify-between pfx-dots" style="background-color:#2f5ee8">
        <div>
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight">Put your best salesperson on every page.</h2>
            <p class="mt-3 text-white/80">Start free. Keep it if it earns its place.</p>
        </div>
        <a href="{{ route('register') }}" class="shrink-0 px-7 py-4 rounded-xl bg-white text-brand-600 font-bold hover:bg-brand-50">Create my assistant</a>
    </div>
</section>

<footer class="border-t border-slate-200">
    <div class="max-w-6xl mx-auto px-5 py-10 flex flex-col md:flex-row gap-4 justify-between text-sm text-slate-500">
        <p>{{ config('branding.copyright') ?: '© ' . date('Y') . ' ' . $name . '. All rights reserved.' }}</p>
        <div class="flex flex-wrap gap-5">
            <a href="{{ route('privacy') }}" class="hover:text-slate-900">Privacy</a>
            <a href="{{ route('terms') }}" class="hover:text-slate-900">Terms</a>
            <a href="{{ route('contact') }}" class="hover:text-slate-900">Contact</a>
            <a href="mailto:{{ config('branding.support_email') }}" class="hover:text-slate-900">{{ config('branding.support_email') }}</a>
        </div>
    </div>
</footer>

@include('layouts.partials.cookie-consent')
</body>
</html>
