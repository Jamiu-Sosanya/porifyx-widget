<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('branding.name') }} — {{ config('branding.tagline') }}</title>
    <meta name="description" content="{{ config('branding.description') }}">
    <meta property="og:title" content="{{ config('branding.name') }}">
    <meta property="og:description" content="{{ config('branding.description') }}">
    {{-- Theme before first paint: saved choice, else the device setting. Shares the app's "theme" key. --}}
    <script>
        (function () {
            var q = new URLSearchParams(location.search).get('theme');
            var t = (q === 'light' || q === 'dark') ? q : localStorage.getItem('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', t === 'dark');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        html, body { overflow-x: hidden; }
        .pfx-hero { background: radial-gradient(55rem 35rem at 90% -10%, #2f5ee826, transparent 60%), radial-gradient(40rem 28rem at -10% 100%, #06b6d41f, transparent 60%); }
        .dark .pfx-hero { background: radial-gradient(55rem 35rem at 90% -10%, #2f5ee855, transparent 60%), radial-gradient(40rem 28rem at -10% 100%, #06b6d433, transparent 60%); }
        .pfx-grid { background-image: linear-gradient(#0b13240a 1px, transparent 1px), linear-gradient(90deg, #0b13240a 1px, transparent 1px); background-size: 48px 48px; mask-image: linear-gradient(to bottom, #000 30%, transparent 85%); }
        .dark .pfx-grid { background-image: linear-gradient(#ffffff0a 1px, transparent 1px), linear-gradient(90deg, #ffffff0a 1px, transparent 1px); }
        .pfx-rise { animation: pfxRise .7s cubic-bezier(.2,.8,.2,1) both; }
        @keyframes pfxRise { from { opacity: 0; transform: translateY(16px); } }
        .pfx-dot { animation: pfxDot 1.2s infinite; }
        .pfx-dot:nth-child(2) { animation-delay: .15s; } .pfx-dot:nth-child(3) { animation-delay: .3s; }
        @keyframes pfxDot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
        .pfx-marquee { mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
        .pfx-marquee-track { animation: pfxMarquee 45s linear infinite; }
        @keyframes pfxMarquee { to { transform: translateX(calc(-50% - .5rem)); } }
        @media (prefers-reduced-motion: reduce) { .pfx-rise, .pfx-dot, .pfx-marquee-track { animation: none; } .pfx-marquee-track { flex-wrap: wrap; width: auto; justify-content: center; } }
    </style>
</head>
@php
    $name = config('branding.name');
    $fmt  = fn ($v) => ($v === null || $v < 0) ? 'Unlimited' : number_format($v);
    // Reused class strings keep light/dark pairs consistent.
    $card    = 'bg-white border border-slate-200 dark:bg-white/[0.03] dark:border-white/10';
    $muted   = 'text-slate-600 dark:text-slate-400';
    $eyebrow = 'text-sm font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400';
    $h2      = 'mt-3 text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight';
@endphp
<body class="antialiased font-sans" x-data="{
        yearly: false, menu: false,
        dark: document.documentElement.classList.contains('dark'),
        toggleTheme() { this.dark = !this.dark; document.documentElement.classList.toggle('dark', this.dark); localStorage.setItem('theme', this.dark ? 'dark' : 'light'); }
    }">
<div class="min-h-screen bg-[#f6f7fb] text-[#0b1324] dark:bg-[#070d1a] dark:text-slate-100 transition-colors">

{{-- ================= NAV ================= --}}
@include('layouts.partials.site-nav')

{{-- ================= HERO ================= --}}
<section class="pfx-hero relative">
    <div class="absolute inset-0 pfx-grid pointer-events-none"></div>
    <div class="relative max-w-6xl mx-auto px-5 pt-12 pb-20 md:pt-20 md:pb-28 grid lg:grid-cols-2 gap-12 lg:gap-14 items-center">
        <div class="min-w-0 pfx-rise">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white/70 px-3 py-1.5 text-xs font-semibold dark:border-white/15 dark:bg-white/5">
                <span class="relative flex w-2 h-2"><span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-60"></span><span class="relative w-2 h-2 rounded-full bg-emerald-500"></span></span>
                Live demo — try the assistant on this page
            </span>
            <h1 class="mt-6 text-[2.5rem] leading-[1.05] sm:text-5xl lg:text-[3.6rem] font-extrabold tracking-tight">
                Turn website visitors into <span class="bg-gradient-to-r from-brand-500 to-accent-500 dark:from-brand-300 dark:to-accent-300 bg-clip-text text-transparent">customers, 24/7.</span>
            </h1>
            <p class="mt-6 text-base sm:text-lg max-w-xl {{ $muted }}">{{ config('branding.description') }}</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <a href="{{ route('register') }}" class="px-6 py-4 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-semibold text-center shadow-lg shadow-brand-500/30">Start your free trial</a>
                <a href="#how" class="px-6 py-4 rounded-xl border border-slate-300 hover:border-slate-900 dark:border-white/20 dark:hover:bg-white/5 font-semibold text-center">See how it works</a>
            </div>
            <ul class="mt-7 flex flex-wrap gap-x-5 gap-y-2 text-sm {{ $muted }}">
                <li>✓ {{ config('branding.trial_days') }}-day free trial</li><li>✓ No card required</li><li>✓ Live in 10 minutes</li>
            </ul>
        </div>

        {{-- Live demo assistant. Fixed-height column; only the message log scrolls (min-h-0 stops it growing). --}}
        <div id="demo" class="min-w-0 pfx-rise scroll-mt-24" style="animation-delay:.1s" x-data="pfxChat()">
            <div class="mx-auto w-full max-w-md h-[min(520px,75svh)] min-h-[380px] flex flex-col rounded-3xl overflow-hidden bg-white text-slate-800 shadow-2xl shadow-slate-900/15 ring-1 ring-slate-200 dark:bg-[#0f1a30] dark:text-slate-100 dark:ring-white/10 dark:shadow-black/50">
                <div class="shrink-0 flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-brand-500 to-brand-600 text-white">
                    <span class="grid place-items-center w-10 h-10 rounded-full bg-white text-brand-600 font-extrabold">P</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold leading-tight truncate">{{ $name }} Assistant</p>
                        <p class="text-xs text-white/80 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span> Online · replies instantly</p>
                    </div>
                    <button type="button" @click="reset()" title="New conversation" aria-label="New conversation" class="grid place-items-center w-8 h-8 rounded-lg bg-white/15 hover:bg-white/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v6h6M20 20v-6h-6M5.5 15A7 7 0 0 0 18 17.5M18.5 9A7 7 0 0 0 6 6.5"/></svg>
                    </button>
                </div>
                <div x-ref="log" class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 py-5 space-y-3 bg-slate-50 dark:bg-transparent" aria-live="polite">
                    <template x-for="(m, i) in messages" :key="i">
                        <div :class="m.role === 'user' ? 'flex justify-end' : 'flex gap-2'">
                            <span x-show="m.role !== 'user'" class="w-7 h-7 shrink-0 rounded-full bg-brand-500 text-white grid place-items-center text-xs font-bold">P</span>
                            <p :class="m.role === 'user' ? 'bg-brand-500 text-white rounded-2xl rounded-br-md' : 'bg-white border border-slate-200 dark:bg-white/5 dark:border-white/10 rounded-2xl rounded-tl-md'"
                               class="px-3.5 py-2.5 text-sm leading-relaxed max-w-[82%] break-words whitespace-pre-line" x-text="m.content"></p>
                        </div>
                    </template>
                    <div x-show="busy" x-cloak class="flex gap-2">
                        <span class="w-7 h-7 shrink-0 rounded-full bg-brand-500 text-white grid place-items-center text-xs font-bold">P</span>
                        <p class="bg-white border border-slate-200 dark:bg-white/5 dark:border-white/10 rounded-2xl rounded-tl-md px-4 py-3 flex gap-1">
                            <span class="pfx-dot w-1.5 h-1.5 rounded-full bg-slate-400"></span><span class="pfx-dot w-1.5 h-1.5 rounded-full bg-slate-400"></span><span class="pfx-dot w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                        </p>
                    </div>
                </div>
                <div class="shrink-0 border-t border-slate-200 dark:border-white/10">
                    <div x-show="messages.length < 3" class="px-3 pt-3 flex gap-2 overflow-x-auto">
                        <template x-for="s in starters" :key="s">
                            <button type="button" @click="send(s)" class="shrink-0 text-xs font-medium px-3 py-1.5 rounded-full border border-brand-200 text-brand-600 hover:bg-brand-50 dark:border-brand-400/30 dark:text-brand-300 dark:hover:bg-brand-500/10" x-text="s"></button>
                        </template>
                    </div>
                    <form @submit.prevent="send()" class="p-3 flex gap-2">
                        <label for="pfx-demo-input" class="sr-only">Message</label>
                        <input id="pfx-demo-input" x-model="draft" maxlength="500" autocomplete="off" placeholder="Ask about pricing, setup, AI models…"
                               class="flex-1 min-w-0 rounded-xl border border-slate-200 bg-white px-4 py-3 text-base sm:text-sm placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:bg-white/5 dark:border-white/10">
                        <button type="submit" :disabled="busy || !draft.trim()" aria-label="Send" class="shrink-0 w-12 rounded-xl bg-brand-500 text-white grid place-items-center disabled:opacity-40">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3.4 20.4l17.45-7.48a1 1 0 0 0 0-1.84L3.4 3.6a.99.99 0 0 0-1.39.91L2 9.12c0 .5.37.93.87.99L17 12 2.87 13.88c-.5.07-.87.5-.87 1l.01 4.61c0 .71.73 1.2 1.39.91z"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================= WORKS WITH + STATS ================= --}}
<section class="border-y border-slate-200 bg-white dark:border-white/10 dark:bg-white/[0.02]">
    @php
        $platforms = ['wordpress' => 'WordPress', 'woocommerce' => 'WooCommerce', 'shopify' => 'Shopify', 'webflow' => 'Webflow', 'wix' => 'Wix',
            'squarespace' => 'Squarespace', 'framer' => 'Framer', 'react' => 'React', 'nextdotjs' => 'Next.js', 'vuedotjs' => 'Vue', 'nuxt' => 'Nuxt',
            'angular' => 'Angular', 'svelte' => 'Svelte', 'laravel' => 'Laravel', 'bigcommerce' => 'BigCommerce', 'prestashop' => 'PrestaShop',
            'drupal' => 'Drupal', 'joomla' => 'Joomla', 'ghost' => 'Ghost', 'hubspot' => 'HubSpot', 'gatsby' => 'Gatsby', 'html5' => 'HTML'];
        $monoLogos = ['nextdotjs', 'ghost', 'squarespace', 'bigcommerce'];
    @endphp
    <div class="max-w-6xl mx-auto px-5 pt-8 pb-2">
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 text-center">Works on any site — {{ count($platforms) }}+ platforms and frameworks</p>
    </div>
    {{-- Infinite logo marquee: list rendered twice, track slides -50%. Pauses on hover; static for reduced motion. --}}
    <div class="pfx-marquee relative overflow-hidden py-6" role="list" aria-label="Supported platforms">
        <div class="pfx-marquee-track flex w-max gap-4">
            @foreach([false, true] as $copy)
                @foreach($platforms as $slug => $label)
                <div role="listitem" @if($copy) aria-hidden="true" @endif
                     class="group flex items-center gap-2.5 shrink-0 rounded-2xl px-5 py-3 bg-slate-50 border border-slate-200 dark:bg-white/[0.04] dark:border-white/10">
                    <img src="{{ asset('images/platforms/' . $slug . '.svg') }}" alt="" height="24" loading="lazy"
                         class="h-6 w-auto max-w-[48px] transition group-hover:scale-110 {{ in_array($slug, $monoLogos) ? 'dark:invert' : '' }}">
                    <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-white whitespace-nowrap">{{ $label }}</span>
                </div>
                @endforeach
            @endforeach
        </div>
    </div>
    <div class="max-w-6xl mx-auto px-5 pt-4 pb-10 grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([['24/7', 'Answers without extra hires'], ['5', 'AI providers, one switch'], ['10', 'Channels, one inbox'], ['1 line', 'Of code to go live']] as [$big, $small])
        <div class="rounded-2xl bg-slate-50 dark:bg-white/[0.03] p-5"><p class="text-2xl sm:text-3xl font-extrabold tracking-tight text-brand-600 dark:text-brand-400">{{ $big }}</p><p class="text-sm mt-1 {{ $muted }}">{{ $small }}</p></div>
        @endforeach
    </div>
</section>

{{-- ================= BEFORE / AFTER ================= --}}
<section class="max-w-6xl mx-auto px-5 py-20 md:py-28">
    <p class="{{ $eyebrow }}">Why teams switch</p>
    <h2 class="{{ $h2 }} max-w-3xl">Stop losing the visitors who had one question.</h2>
    <div class="mt-12 grid md:grid-cols-2 gap-4">
        <div class="rounded-3xl p-7 {{ $card }}">
            <p class="font-bold text-rose-500">Without {{ $name }}</p>
            <ul class="mt-5 space-y-3 {{ $muted }}">
                @foreach(['Visitors leave when nobody answers after hours', 'Contact forms collect names, not buying intent', 'Your team answers the same 20 questions every day', 'Hot leads wait hours for a reply'] as $x)
                <li class="flex gap-3"><span class="text-rose-500 font-bold">✕</span>{{ $x }}</li>
                @endforeach
            </ul>
        </div>
        <div class="rounded-3xl p-7 bg-brand-500 text-white shadow-xl shadow-brand-500/20">
            <p class="font-bold">With {{ $name }}</p>
            <ul class="mt-5 space-y-3 text-white/90">
                @foreach(['Every visitor gets an accurate answer in seconds, day or night', 'Leads are captured mid-conversation and scored automatically', 'Repetitive questions are handled, so your team focuses on deals', 'Hot leads alert your team and reach your CRM instantly'] as $x)
                <li class="flex gap-3"><span class="font-bold">✓</span>{{ $x }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- ================= FEATURES ================= --}}
<section id="features" class="bg-white border-y border-slate-200 dark:bg-white/[0.02] dark:border-white/10 scroll-mt-16">
    <div class="max-w-6xl mx-auto px-5 py-20 md:py-28">
        <p class="{{ $eyebrow }}">Everything in one assistant</p>
        <h2 class="{{ $h2 }} max-w-3xl">Answers, leads and support, handled automatically.</h2>
        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach([
                ['Trained on your business', 'Learns from your website, PDFs, documents and Q&A pairs. Strict mode keeps every answer on-topic, and version history lets you roll back.', 'M12 6.25v13m0-13C10.83 5.48 9.25 5 7.5 5S4.17 5.48 3 6.25v13C4.17 18.48 5.75 18 7.5 18s3.33.48 4.5 1.25m0-13C13.17 5.48 14.75 5 16.5 5s3.33.48 4.5 1.25v13C19.83 18.48 18.25 18 16.5 18s-3.33.48-4.5 1.25', true],
                ['Leads scored for you', 'Contact details captured mid-chat and scored cold, warm or hot, with segments and notes.', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', false],
                ['Human handoff', 'Agents take over in a shared inbox with AI summaries, sentiment and suggested replies.', 'M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.28.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z', false],
                ['Any AI model, with backup', 'OpenAI, Claude, Gemini, Groq or OpenRouter. If one provider goes down, a backup takes over.', 'M13 10V3L4 14h7v7l9-11h-7z', false],
                ['Automations & webhooks', 'Route hot leads to your CRM, alert your team on keywords and trigger follow-ups with signed webhooks.', 'M4 4v5h.58m15.36 2A8 8 0 0 0 4.58 9m0 0H9m11 11v-5h-.58m0 0a8 8 0 0 1-15.36-2m15.36 2H15', false],
                ['Proactive messages', 'Greet visitors on key pages, after a delay or on exit intent, in their own language.', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.42-4.03 8-9 8a9.86 9.86 0 0 1-4.26-.95L3 20l1.4-3.72A7.56 7.56 0 0 1 3 12c0-4.42 4.03-8 9-8s9 3.58 9 8z', false],
                ['Analytics that matter', 'Conversations, conversion, CSAT, sentiment and the questions your AI could not answer yet.', 'M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z', false],
                ['Looks like your brand', 'Colours, avatar, launcher, tone and greeting, previewed live. A/B test variants to find what converts best.', 'M7 21a4 4 0 0 1-4-4V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v12a4 4 0 0 1-4 4zm0 0h12a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.34M11 7.34l1.66-1.66a2 2 0 0 1 2.83 0l2.83 2.83a2 2 0 0 1 0 2.83L9.83 19.83', true],
            ] as [$title, $body, $icon, $wide])
            <article class="rounded-3xl p-7 {{ $wide ? 'lg:col-span-2 bg-[#0b1324] text-white dark:bg-brand-500/10 dark:ring-1 dark:ring-brand-400/20' : 'bg-slate-50 border border-slate-200 dark:bg-white/[0.03] dark:border-white/10' }}">
                <span class="grid place-items-center w-11 h-11 rounded-xl {{ $wide ? 'bg-white/10 text-accent-300' : 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg>
                </span>
                <h3 class="mt-5 text-xl font-bold">{{ $title }}</h3>
                <p class="mt-2 leading-relaxed {{ $wide ? 'text-slate-300' : $muted }}">{{ $body }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= DASHBOARD PREVIEW ================= --}}
<section class="max-w-6xl mx-auto px-5 py-20 md:py-28 grid lg:grid-cols-2 gap-12 items-center">
    <div class="min-w-0">
        <p class="{{ $eyebrow }}">Your command centre</p>
        <h2 class="{{ $h2 }}">See every conversation. Act on every lead.</h2>
        <p class="mt-5 text-lg {{ $muted }}">One dashboard for conversations, leads, training and analytics. Jump into any chat, see why a lead is hot, and find the questions your assistant should learn next.</p>
        <ul class="mt-8 grid sm:grid-cols-2 gap-3 text-sm font-medium">
            @foreach(['Real-time inbox with takeover', 'Lead CRM with export', 'Knowledge gaps report', 'Team roles and permissions', 'Browser push notifications', 'Installable mobile app (PWA)'] as $x)
            <li class="flex gap-2"><span class="text-brand-500">✓</span>{{ $x }}</li>
            @endforeach
        </ul>
    </div>
    <div class="min-w-0 rounded-3xl p-3 sm:p-4 {{ $card }} shadow-xl shadow-slate-900/5" aria-hidden="true">
        <div class="rounded-2xl bg-slate-50 dark:bg-[#0b1324] p-4 space-y-3">
            <div class="flex items-center justify-between text-xs font-semibold {{ $muted }}"><span>Example dashboard</span><span class="text-emerald-500">● 12 live chats</span></div>
            <div class="grid grid-cols-3 gap-2">
                @foreach([['Chats', '184'], ['Leads', '37'], ['CSAT', '4.8']] as [$k, $v])
                <div class="rounded-xl bg-white dark:bg-white/5 p-3"><p class="text-[11px] {{ $muted }}">{{ $k }}</p><p class="text-lg font-extrabold">{{ $v }}</p></div>
                @endforeach
            </div>
            @foreach([['Sarah K.', 'Can you integrate with HubSpot?', 'Hot', 'bg-rose-500'], ['Daniel O.', 'What does the Team plan include?', 'Warm', 'bg-amber-500'], ['Mei L.', 'Do you offer an annual discount?', 'Warm', 'bg-amber-500'], ['Visitor 2841', 'How do I reset my password?', 'Support', 'bg-slate-400']] as [$who, $msg, $tag, $dot])
            <div class="flex items-center gap-3 rounded-xl bg-white dark:bg-white/5 p-3">
                <span class="grid place-items-center w-9 h-9 shrink-0 rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200 text-xs font-bold">{{ mb_substr($who, 0, 1) }}</span>
                <div class="min-w-0 flex-1"><p class="text-sm font-semibold truncate">{{ $who }}</p><p class="text-xs truncate {{ $muted }}">{{ $msg }}</p></div>
                <span class="shrink-0 inline-flex items-center gap-1.5 text-[11px] font-semibold"><span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>{{ $tag }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= USE CASES ================= --}}
<section id="use-cases" class="bg-white border-y border-slate-200 dark:bg-white/[0.02] dark:border-white/10 scroll-mt-16">
    <div class="max-w-6xl mx-auto px-5 py-20 md:py-28">
        <p class="{{ $eyebrow }}">Use cases</p>
        <h2 class="{{ $h2 }} max-w-3xl">Built for teams that live on their website.</h2>
        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach([
                ['🛍️', 'E-commerce', 'Answer sizing, shipping and returns questions instantly and recover hesitant shoppers.'],
                ['💻', 'SaaS', 'Qualify trial users, explain plans and route enterprise leads to sales.'],
                ['🏢', 'Agencies', 'Run a branded assistant for every client from one account.'],
                ['🏠', 'Real estate', 'Capture buyer budgets and viewing requests around the clock.'],
                ['🩺', 'Clinics & services', 'Handle opening hours, services and booking requests without phone queues.'],
                ['🎓', 'Education', 'Guide applicants through courses, fees and deadlines in their language.'],
            ] as [$emoji, $t, $d])
            <div class="rounded-3xl p-6 bg-slate-50 border border-slate-200 dark:bg-white/[0.03] dark:border-white/10 hover:border-brand-300 dark:hover:border-brand-400/40 transition">
                <span class="text-2xl" aria-hidden="true">{{ $emoji }}</span>
                <h3 class="mt-3 font-bold text-lg">{{ $t }}</h3>
                <p class="mt-1.5 {{ $muted }}">{{ $d }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= HOW IT WORKS ================= --}}
<section id="how" class="max-w-6xl mx-auto px-5 py-20 md:py-28 scroll-mt-16">
    <p class="{{ $eyebrow }}">How it works</p>
    <h2 class="{{ $h2 }}">Live this afternoon.</h2>
    <ol class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['Teach it', 'Add your URLs, documents or Q&A. Training runs in the background.'],
            ['Style it', 'Choose colours, tone, AI model and greeting. Preview every change.'],
            ['Install it', 'Paste one script tag, or connect WhatsApp, Telegram and more.'],
            ['Grow it', 'Review chats and leads, fill knowledge gaps, convert more.'],
        ] as $i => [$t, $d])
        <li class="rounded-3xl p-6 {{ $card }}">
            <span class="grid place-items-center w-10 h-10 rounded-full bg-brand-500 text-white font-bold">{{ $i + 1 }}</span>
            <h3 class="mt-5 font-bold text-lg">{{ $t }}</h3>
            <p class="mt-1.5 {{ $muted }}">{{ $d }}</p>
        </li>
        @endforeach
    </ol>
    <div class="mt-4 rounded-2xl bg-[#0b1324] ring-1 ring-white/10 p-5 sm:p-6 min-w-0">
        <p class="text-xs font-semibold text-slate-400 mb-3">Your entire installation:</p>
        <code class="block text-[13px] sm:text-sm text-accent-300 break-all leading-6">&lt;script src="{{ url('widget.js') }}" data-widget-id="YOUR-WIDGET-ID" async&gt;&lt;/script&gt;</code>
    </div>
</section>

{{-- ================= CHANNELS + SECURITY ================= --}}
<section class="bg-white border-y border-slate-200 dark:bg-white/[0.02] dark:border-white/10">
    <div class="max-w-6xl mx-auto px-5 py-20 md:py-28 grid lg:grid-cols-2 gap-12">
        <div class="min-w-0">
            <p class="{{ $eyebrow }}">Omnichannel</p>
            <h2 class="{{ $h2 }}">One assistant. Every channel.</h2>
            <p class="mt-4 {{ $muted }}">Train once and answer wherever customers message you, with every conversation in one inbox.</p>
            <div class="mt-8 flex flex-wrap gap-2.5">
                @foreach(['Website', 'WhatsApp', 'Telegram', 'Messenger', 'Instagram', 'X', 'Slack', 'Discord', 'SMS', 'Email'] as $ch)
                <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $card }}">{{ $ch }}</span>
                @endforeach
            </div>
        </div>
        <div class="min-w-0">
            <p class="{{ $eyebrow }}">Security</p>
            <h2 class="{{ $h2 }}">Your data stays yours.</h2>
            <div class="mt-8 grid sm:grid-cols-2 gap-3">
                @foreach([['Self-hosted data', 'Conversations and leads live on your own server.'], ['Signed webhooks', 'Every payload carries an HMAC-SHA256 signature.'], ['Two-factor login', 'TOTP 2FA for every account, IP-restricted admin.'], ['Abuse protection', 'Rate limits and domain locks on every widget.']] as [$t, $d])
                <div class="rounded-2xl p-5 bg-slate-50 dark:bg-white/[0.03]"><p class="font-bold">{{ $t }}</p><p class="mt-1 text-sm {{ $muted }}">{{ $d }}</p></div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ================= PRICING ================= --}}
@if($plans->count())
<section id="pricing" class="max-w-6xl mx-auto px-5 py-20 md:py-28 scroll-mt-16">
    <div class="text-center">
        <p class="{{ $eyebrow }}">Pricing</p>
        <h2 class="{{ $h2 }}">Plans that grow with you.</h2>
        <div class="mt-8 inline-flex p-1 rounded-xl bg-slate-200/70 dark:bg-white/5 text-sm font-semibold">
            <button @click="yearly = false" :class="!yearly ? 'bg-white shadow text-slate-900' : 'text-slate-500 dark:text-slate-400'" class="px-5 py-2 rounded-lg">Monthly</button>
            <button @click="yearly = true" :class="yearly ? 'bg-white shadow text-slate-900' : 'text-slate-500 dark:text-slate-400'" class="px-5 py-2 rounded-lg">Yearly</button>
        </div>
    </div>
    <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-{{ min($plans->count(), 4) }}">
        @foreach($plans as $plan)
        <div class="rounded-3xl p-7 flex flex-col {{ $plan->is_popular ? 'bg-[#0b1324] text-white ring-2 ring-brand-500 shadow-2xl shadow-brand-500/20' : $card }}">
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-bold text-lg">{{ $plan->name }}</h3>
                @if($plan->is_popular)<span class="text-[11px] font-bold uppercase tracking-wider bg-brand-500 rounded-full px-2.5 py-1">Popular</span>@endif
            </div>
            <p class="mt-2 text-sm {{ $plan->is_popular ? 'text-slate-400' : $muted }}">{{ $plan->description }}</p>
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
               class="mt-8 text-center py-3.5 rounded-xl font-semibold {{ $plan->is_popular ? 'bg-brand-500 hover:bg-brand-400 text-white' : 'bg-slate-900 text-white hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200' }}">
                {{ $plan->price_monthly > 0 ? 'Start free trial' : 'Get started free' }}
            </a>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- ================= FAQ ================= --}}
<section id="faq" class="bg-white border-y border-slate-200 dark:bg-white/[0.02] dark:border-white/10 scroll-mt-16">
    <div class="max-w-3xl mx-auto px-5 py-20 md:py-28">
        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-center">Questions, answered.</h2>
        <p class="mt-3 text-center {{ $muted }}">Or ask the <a href="#demo" class="text-brand-600 dark:text-brand-400 font-semibold underline underline-offset-4">live assistant</a>.</p>
        <div class="mt-10 space-y-3">
            @foreach($faq as [, $q, $a])
            <details class="group rounded-2xl px-5 bg-slate-50 border border-slate-200 dark:bg-white/[0.03] dark:border-white/10">
                <summary class="flex justify-between items-center gap-4 cursor-pointer py-5 font-semibold list-none">{{ $q }}
                    <span class="shrink-0 grid place-items-center w-7 h-7 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300 transition group-open:rotate-45">+</span></summary>
                <p class="pb-5 -mt-1 leading-relaxed {{ $muted }}">{{ $a }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= CTA ================= --}}
<section class="px-5 py-20">
    <div class="relative overflow-hidden max-w-6xl mx-auto rounded-[2rem] bg-[#0b1324] text-white px-7 py-14 md:px-14 md:py-16 text-center">
        <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-brand-500/40 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-accent-500/25 blur-3xl"></div>
        <div class="relative">
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight">Your best salesperson,<br class="hidden sm:block"> on every page.</h2>
            <p class="mt-4 text-slate-300">Start free. Keep it if it earns its place.</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}" class="px-8 py-4 rounded-xl bg-white text-slate-900 font-bold hover:bg-slate-200">Create my assistant</a>
                <a href="{{ route('contact', ['topic' => 'sales']) }}#form" class="px-8 py-4 rounded-xl border border-white/25 font-semibold hover:bg-white/5">Talk to sales</a>
            </div>
        </div>
    </div>
</section>

{{-- ================= FOOTER ================= --}}
@include('layouts.partials.site-footer')

{{-- Floating launcher: jumps to the live assistant --}}
<a href="#demo" @click="setTimeout(() => document.getElementById('pfx-demo-input').focus({ preventScroll: true }), 500)" aria-label="Chat with us"
   class="fixed z-30 right-4 bottom-[max(1rem,env(safe-area-inset-bottom))] w-14 h-14 rounded-[18px] bg-brand-500 text-white grid place-items-center shadow-xl shadow-brand-500/40 hover:bg-brand-600">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M5 3h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-8l-5 4v-4H5a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z" fill="white"/><path d="M12 6.5l1.2 2.8 2.8 1.2-2.8 1.2L12 14.5l-1.2-2.8L8 10.5l2.8-1.2z" fill="#2f5ee8"/></svg>
</a>
</div>

<script>
function pfxChat() {
    const greeting = { role: 'assistant', content: @js("Hi! I'm the {$name} assistant. Ask me anything about pricing, setup or features.") };
    return {
        messages: [greeting], draft: '', busy: false,
        starters: ['How much does it cost?', 'How do I install it?', 'Which AI models?', 'Can my team take over?'],
        reset() { this.messages = [greeting]; this.draft = ''; },
        async send(text) {
            const message = (text ?? this.draft).trim();
            if (!message || this.busy) return;
            const history = this.messages.slice(1).slice(-10);
            this.messages.push({ role: 'user', content: message });
            // Keep the DOM bounded on long sessions.
            if (this.messages.length > 40) this.messages.splice(1, this.messages.length - 40);
            this.draft = ''; this.busy = true; this.scroll();
            try {
                const res = await fetch(@js(route('demo.chat')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ message, history }),
                });
                const data = await res.json().catch(() => ({}));
                this.messages.push({ role: 'assistant', content: res.ok ? data.reply
                    : res.status === 429 ? 'You are sending messages quickly. Please wait a moment.'
                    : res.status === 419 ? 'This page expired. Please refresh and try again.'
                    : 'Sorry, something went wrong. Please try again.' });
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
