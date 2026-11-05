<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('branding.name') }} — {{ config('branding.tagline') }}</title>
    <meta name="description" content="{{ config('branding.description') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: dark; }
        body { background: #020617; }

        /* Background grid */
        .grid-pattern {
            background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 72px 72px;
        }

        /* Glows */
        .glow-brand { box-shadow: 0 0 120px -20px rgba(47, 94, 232,0.35); }
        .glow-accent { box-shadow: 0 0 120px -20px rgba(8, 145, 178,0.25); }
        .text-glow { text-shadow: 0 0 80px rgba(47, 94, 232,0.5); }

        /* Chat widget mockup animations */
        @keyframes msgIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .msg-animate { animation: msgIn 0.4s ease forwards; }

        @keyframes blink { 0%,100% { opacity:1 } 50% { opacity:0.3 } }
        .typing-dot { animation: blink 1.2s infinite; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }

        /* Floating animation */
        @keyframes float {
            0%,100% { transform: translateY(0px); }
            50%      { transform: translateY(-10px); }
        }
        .float-slow { animation: float 6s ease-in-out infinite; }
        .float-slower { animation: float 8s ease-in-out infinite; animation-delay: -3s; }

        /* Gradient text */
        .grad-text { background: linear-gradient(135deg, #6b8ff2 0%, #22d3ee 50%, #22d3ee 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .grad-text-emerald { background: linear-gradient(135deg, #34d399, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        /* Shimmer on logo bar */
        @keyframes shimmer { from { background-position: -200% center; } to { background-position: 200% center; } }
        .logo-shimmer { background: linear-gradient(90deg, #374151 0%, #6b7280 50%, #374151 100%); background-size: 200% auto; animation: shimmer 3s linear infinite; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        /* Scroll reveal */
        @keyframes revealUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
        .reveal { opacity: 0; }
        .reveal.visible { animation: revealUp 0.6s ease forwards; }

        /* Feature card hover */
        .feature-card { transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease; }
        .feature-card:hover { transform: translateY(-3px); box-shadow: 0 12px 40px -8px rgba(47, 94, 232,0.15); }

        /* Navbar scroll effect handled by Alpine */
        .nav-blur { background: rgba(2,6,23,0.85); backdrop-filter: blur(20px); }

        /* Stats counter pulse */
        @keyframes countUp { from { opacity:0; transform:scale(0.85); } to { opacity:1; transform:scale(1); } }
        .stat-num { animation: countUp 0.5s ease forwards; }

        /* Testimonial card */
        .testi-card { background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%); }

        /* Step connector */
        .step-line::after {
            content: '';
            position: absolute;
            top: 24px;
            left: calc(50% + 28px);
            width: calc(100% - 56px);
            height: 1px;
            background: linear-gradient(90deg, rgba(47, 94, 232,0.4), rgba(8, 145, 178,0.2));
        }
        @media (max-width: 768px) { .step-line::after { display: none; } }
    </style>
</head>
<body class="text-white antialiased" x-data="{
    mobileOpen: false,
    navScrolled: false,
    pricingCycle: 'monthly',
    faqOpen: null,
    activeFeat: 0,
    chatStep: 0,
    init() {
        window.addEventListener('scroll', () => { this.navScrolled = window.scrollY > 20; });
        this.startChat();
        this.startReveal();
    },
    startChat() {
        const steps = 6;
        setInterval(() => { this.chatStep = (this.chatStep + 1) % steps; }, 2200);
    },
    startReveal() {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach((e, i) => { if(e.isIntersecting) { setTimeout(() => e.target.classList.add('visible'), i * 80); } });
        }, { threshold: 0.15 });
        document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
    }
}">

{{-- ============ NAVBAR ============ --}}
<nav class="fixed top-0 w-full z-50 transition-all duration-300"
     :class="navScrolled ? 'nav-blur border-b border-white/[0.06] shadow-2xl shadow-black/20' : 'bg-transparent'">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 h-16 flex items-center justify-between">
        {{-- Logo --}}
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="w-9 h-9 bg-gradient-to-br from-brand-500 to-accent-600 rounded-xl flex items-center justify-center shadow-lg shadow-brand-500/30 group-hover:shadow-brand-500/50 transition-shadow">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
            </div>
            <span class="text-base font-bold tracking-tight">{{ config('branding.name') }}</span>
        </a>

        {{-- Desktop nav --}}
        <div class="hidden md:flex items-center gap-1 text-sm">
            <a href="#features" class="px-3 py-2 text-gray-400 hover:text-white rounded-lg hover:bg-white/5 transition">Features</a>
            <a href="#how-it-works" class="px-3 py-2 text-gray-400 hover:text-white rounded-lg hover:bg-white/5 transition">How It Works</a>
            <a href="#pricing" class="px-3 py-2 text-gray-400 hover:text-white rounded-lg hover:bg-white/5 transition">Pricing</a>
            <a href="#faq" class="px-3 py-2 text-gray-400 hover:text-white rounded-lg hover:bg-white/5 transition">FAQ</a>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('login') }}" class="hidden sm:block text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-white/5 transition">Sign in</a>
            <a href="{{ route('register') }}" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl transition-all shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40">
                Get Started Free
            </a>
            {{-- Mobile burger --}}
            <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-gray-400 hover:text-white rounded-lg hover:bg-white/5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    <path x-show="mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    {{-- Mobile menu --}}
    <div x-show="mobileOpen" x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="md:hidden bg-gray-950/98 border-t border-white/5 px-5 py-4 space-y-1">
        <a href="#features" @click="mobileOpen=false" class="block px-3 py-2.5 text-sm text-gray-300 hover:text-white rounded-lg hover:bg-white/5 transition">Features</a>
        <a href="#how-it-works" @click="mobileOpen=false" class="block px-3 py-2.5 text-sm text-gray-300 hover:text-white rounded-lg hover:bg-white/5 transition">How It Works</a>
        <a href="#pricing" @click="mobileOpen=false" class="block px-3 py-2.5 text-sm text-gray-300 hover:text-white rounded-lg hover:bg-white/5 transition">Pricing</a>
        <a href="#faq" @click="mobileOpen=false" class="block px-3 py-2.5 text-sm text-gray-300 hover:text-white rounded-lg hover:bg-white/5 transition">FAQ</a>
        <div class="pt-2 border-t border-white/5 flex gap-2">
            <a href="{{ route('login') }}" class="flex-1 text-center py-2.5 text-sm text-gray-300 bg-white/5 rounded-xl hover:bg-white/10 transition">Sign in</a>
            <a href="{{ route('register') }}" class="flex-1 text-center py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-xl hover:bg-brand-500 transition">Start Free</a>
        </div>
    </div>
</nav>

{{-- ============ HERO ============ --}}
<section class="relative min-h-screen flex items-center pt-16 overflow-hidden grid-pattern">
    {{-- Background blobs --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-[600px] h-[600px] bg-brand-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/3 right-1/4 w-[400px] h-[400px] bg-accent-600/10 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-0 left-1/2 w-[300px] h-[200px] bg-accent-600/8 rounded-full blur-[80px]"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-5 lg:px-8 py-20 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            {{-- Left: Copy --}}
            <div>
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-brand-500/10 border border-brand-500/20 rounded-full text-xs font-medium text-brand-300 mb-7">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-brand-400"></span>
                    </span>
                    AI-Powered · GPT-4o · RAG Pipeline
                </div>

                <h1 class="text-5xl sm:text-6xl xl:text-7xl font-black leading-[1.03] tracking-tight mb-6">
                    <span class="block text-white">Turn Visitors</span>
                    <span class="block grad-text text-glow">Into Customers</span>
                    <span class="block text-white">While You Sleep</span>
                </h1>

                <p class="text-lg text-gray-400 leading-relaxed mb-8 max-w-xl">
                    Deploy an AI chat agent trained on your business data in minutes. It captures leads, answers questions, qualifies buyers, and hands off to your team — <span class="text-white font-medium">all on autopilot</span>.
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row items-start gap-3 mb-8">
                    <a href="{{ route('register') }}"
                       class="group inline-flex items-center gap-2.5 px-8 py-4 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold rounded-2xl transition-all duration-200 shadow-2xl shadow-brand-500/30 hover:shadow-brand-500/50 text-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                        Start Free — No Card Needed
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                    <a href="#how-it-works" class="inline-flex items-center gap-2 px-6 py-4 text-gray-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 font-medium rounded-2xl transition text-sm">
                        <svg class="w-4 h-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                        Watch 2-Min Demo
                    </a>
                </div>

                {{-- Trust signals --}}
                <div class="flex flex-wrap items-center gap-5 text-xs text-gray-500">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        14-day free trial
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Setup in 2 minutes
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Cancel anytime
                    </div>
                </div>
            </div>

            {{-- Right: Chat widget mockup --}}
            <div class="relative flex justify-center lg:justify-end">
                {{-- Glow behind widget --}}
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-80 h-80 bg-brand-600/20 rounded-full blur-[80px]"></div>
                </div>

                {{-- Widget mockup --}}
                <div class="relative float-slow">
                    {{-- Shadow/reflection --}}
                    <div class="absolute -inset-4 bg-gradient-to-b from-brand-500/10 to-transparent rounded-3xl blur-xl"></div>

                    <div class="relative w-80 bg-gray-900 border border-white/10 rounded-3xl overflow-hidden shadow-2xl shadow-brand-500/20" style="min-height:480px">
                        {{-- Chat header --}}
                        <div class="bg-gradient-to-r from-brand-600 to-accent-600 px-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                                    </div>
                                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 border-2 border-brand-600 rounded-full"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">Aria AI</p>
                                    <p class="text-[10px] text-white/70">● Online · Typically replies instantly</p>
                                </div>
                                <div class="ml-auto flex gap-1.5">
                                    <div class="w-2 h-2 bg-white/40 rounded-full"></div>
                                    <div class="w-2 h-2 bg-white/40 rounded-full"></div>
                                    <div class="w-2 h-2 bg-white/40 rounded-full"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Messages --}}
                        <div class="p-4 space-y-3 min-h-[340px] flex flex-col justify-end">
                            {{-- Step 0: AI welcome --}}
                            <div x-show="chatStep >= 0" class="msg-animate flex gap-2">
                                <div class="w-6 h-6 bg-brand-600/30 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-brand-400" fill="currentColor" viewBox="0 0 24 24"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                                </div>
                                <div class="bg-gray-800 rounded-2xl rounded-tl-sm px-3 py-2 max-w-[80%]">
                                    <p class="text-xs text-gray-200">Hi! I'm Aria 👋 How can I help you today?</p>
                                </div>
                            </div>
                            {{-- Step 1: User message --}}
                            <div x-show="chatStep >= 1" class="msg-animate flex justify-end">
                                <div class="bg-brand-600 rounded-2xl rounded-tr-sm px-3 py-2 max-w-[80%]">
                                    <p class="text-xs text-white">What's the pricing for the Pro plan?</p>
                                </div>
                            </div>
                            {{-- Step 2: AI typing --}}
                            <div x-show="chatStep === 2" class="msg-animate flex gap-2">
                                <div class="w-6 h-6 bg-brand-600/30 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-brand-400" fill="currentColor" viewBox="0 0 24 24"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                                </div>
                                <div class="bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full typing-dot"></span>
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full typing-dot"></span>
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full typing-dot"></span>
                                </div>
                            </div>
                            {{-- Step 3: AI answer --}}
                            <div x-show="chatStep >= 3" class="msg-animate flex gap-2">
                                <div class="w-6 h-6 bg-brand-600/30 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-brand-400" fill="currentColor" viewBox="0 0 24 24"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                                </div>
                                <div class="bg-gray-800 rounded-2xl rounded-tl-sm px-3 py-2 max-w-[85%]">
                                    <p class="text-xs text-gray-200">The Pro plan is <strong class="text-white">$49/mo</strong> and includes 5 widgets, 10k messages, leads, and live chat. Would you like to start a free trial? 🚀</p>
                                </div>
                            </div>
                            {{-- Step 4: User replies --}}
                            <div x-show="chatStep >= 4" class="msg-animate flex justify-end">
                                <div class="bg-brand-600 rounded-2xl rounded-tr-sm px-3 py-2 max-w-[80%]">
                                    <p class="text-xs text-white">Yes! Sign me up please</p>
                                </div>
                            </div>
                            {{-- Step 5: Lead captured --}}
                            <div x-show="chatStep >= 5" class="msg-animate flex gap-2">
                                <div class="w-6 h-6 bg-brand-600/30 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-3 h-3 text-brand-400" fill="currentColor" viewBox="0 0 24 24"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                                </div>
                                <div class="space-y-1.5 max-w-[85%]">
                                    <div class="bg-gray-800 rounded-2xl rounded-tl-sm px-3 py-2">
                                        <p class="text-xs text-gray-200">Great! What's your name and email so I can get you set up?</p>
                                    </div>
                                    <div class="flex items-center gap-1.5 px-1">
                                        <span class="text-[9px] px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded-full font-medium">🎯 Lead Captured — Score: 92</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Input bar --}}
                        <div class="p-3 border-t border-white/5">
                            <div class="flex items-center gap-2 bg-gray-800 rounded-2xl px-3 py-2.5">
                                <input type="text" placeholder="Ask anything..." class="flex-1 bg-transparent text-xs text-gray-400 placeholder-gray-600 outline-none" disabled>
                                <button class="w-7 h-7 bg-brand-600 rounded-xl flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Floating lead badge --}}
                    <div class="absolute -left-16 top-24 float-slower bg-gray-900 border border-white/10 rounded-2xl px-3 py-2.5 shadow-xl shadow-black/30 flex items-center gap-2.5 w-44">
                        <div class="w-8 h-8 bg-rose-500/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-white">Hot Lead!</p>
                            <p class="text-[10px] text-gray-500">Score 92 — Budget: $5k</p>
                        </div>
                    </div>

                    {{-- Floating analytics badge --}}
                    <div class="absolute -right-12 bottom-28 float-slow bg-gray-900 border border-white/10 rounded-2xl px-3 py-2.5 shadow-xl shadow-black/30 flex items-center gap-2 w-40" style="animation-delay:-2s">
                        <div class="w-8 h-8 bg-emerald-500/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" /></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-white">+34% Conv.</p>
                            <p class="text-[10px] text-gray-500">vs last month</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ STATS BAR ============ --}}
<section class="py-12 border-y border-white/[0.05] bg-gray-900/20">
    <div class="max-w-5xl mx-auto px-5 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            @foreach([
                ['12,000+', 'Businesses Onboarded', 'brand'],
                ['4.2M+',   'Leads Captured',        'accent'],
                ['99.9%',   'Uptime SLA',             'emerald'],
                ['< 2 min', 'Avg. Setup Time',        'amber'],
            ] as [$num, $label, $color])
            <div class="reveal">
                <p class="text-3xl sm:text-4xl font-black text-white mb-1 stat-num">{{ $num }}</p>
                <p class="text-sm text-gray-500">{{ $label }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ LOGO BAR ============ --}}
<section class="py-14">
    <div class="max-w-5xl mx-auto px-5 lg:px-8 text-center">
        <p class="text-xs uppercase tracking-widest text-gray-600 font-semibold mb-8">Trusted by teams at</p>
        <div class="flex flex-wrap justify-center items-center gap-x-10 gap-y-5">
            @foreach(['Acme Corp', 'Nexus AI', 'Streamline', 'Orbit Labs', 'Velo Tech', 'PureFlow'] as $brand)
            <span class="text-base font-bold logo-shimmer tracking-tight">{{ $brand }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ HOW IT WORKS ============ --}}
<section id="how-it-works" class="py-24 bg-gray-900/20">
    <div class="max-w-6xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-16 reveal">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-accent-500/10 border border-accent-500/20 rounded-full text-xs text-accent-400 font-medium mb-4">
                Simple Setup
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold mb-4">Up and Running in <span class="grad-text">3 Steps</span></h2>
            <p class="text-gray-400 max-w-xl mx-auto">No developers needed. No complex configuration. Just connect, train, and deploy.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
            @foreach([
                ['01', 'Create Your Widget', 'Choose your AI\'s personality, brand colors, and language. Configure lead capture fields and conversation starters.', 'brand', '<path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />'],
                ['02', 'Train on Your Data', 'Upload PDFs, paste URLs, add FAQs, or write your knowledge base directly. The AI learns your business in minutes.', 'accent', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />'],
                ['03', 'Embed & Go Live', 'Copy one script tag. Paste it into your website. The widget appears instantly — no rebuild, no deploy.', 'emerald', '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />'],
            ] as $i => [$num, $title, $desc, $color, $icon])
            <div class="reveal relative text-center" style="animation-delay: {{ $i * 120 }}ms">
                {{-- Step number circle --}}
                <div class="relative inline-flex mb-6">
                    <div class="w-16 h-16 bg-{{ $color }}-500/10 border border-{{ $color }}-500/20 rounded-2xl flex items-center justify-center mx-auto">
                        <svg class="w-7 h-7 text-{{ $color }}-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">{!! $icon !!}</svg>
                    </div>
                    <span class="absolute -top-2 -right-2 w-6 h-6 bg-{{ $color }}-600 rounded-lg text-[10px] font-black text-white flex items-center justify-center">{{ $num }}</span>
                </div>
                {{-- Connector line --}}
                @if($i < 2)
                <div class="hidden md:block absolute top-8 left-[calc(50%+40px)] right-0 h-px" style="background: linear-gradient(90deg, rgba(47, 94, 232,0.3), transparent)"></div>
                @endif
                <h3 class="text-base font-bold text-white mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-400 leading-relaxed max-w-xs mx-auto">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ FEATURES ============ --}}
<section id="features" class="py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-16 reveal">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-brand-500/10 border border-brand-500/20 rounded-full text-xs text-brand-400 font-medium mb-4">
                Everything Included
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold mb-4">Built for Businesses That Want to<br><span class="grad-text">Close More Deals</span></h2>
            <p class="text-gray-400 max-w-2xl mx-auto">One platform. Every tool you need to capture, qualify, and convert website visitors automatically.</p>
        </div>

        {{-- Feature tabs --}}
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            @foreach(['AI Engine', 'Lead Capture', 'Analytics', 'Automations', 'Customization', 'Security'] as $i => $tab)
            <button @click="activeFeat = {{ $i }}"
                    :class="activeFeat === {{ $i }} ? 'bg-brand-600 text-white border-brand-500' : 'bg-white/5 text-gray-400 border-white/10 hover:text-white hover:bg-white/10'"
                    class="px-4 py-2 text-xs font-medium border rounded-xl transition">
                {{ $tab }}
            </button>
            @endforeach
        </div>

        {{-- Feature panels --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            {{-- Detail panel --}}
            <div class="lg:col-span-2 space-y-4">
                @foreach([
                    [
                        'title' => 'RAG-Powered AI Engine',
                        'desc'  => 'Your AI retrieves the most relevant knowledge from your docs, URLs, and FAQs using vector search — then generates accurate, grounded responses. No hallucinations.',
                        'points' => ['GPT-4o & Claude support', 'Vector similarity search', 'Confidence-based fallback', 'Auto language detection'],
                        'color' => 'brand',
                    ],
                    [
                        'title' => 'Intelligent Lead Capture',
                        'desc'  => 'The AI naturally extracts name, email, phone, company, budget range, and timeline from conversation context. Every visitor gets a lead score.',
                        'points' => ['0-100 lead scoring', 'Hot / Warm / Cold labels', 'Custom qualification flows', 'CRM export via CSV'],
                        'color' => 'accent',
                    ],
                    [
                        'title' => 'Deep Analytics',
                        'desc'  => 'Track conversion funnels, AI accuracy rates, sentiment trends, CSAT scores, and geographic breakdowns. AI-generated weekly insights included.',
                        'points' => ['Conversion funnel tracking', 'Sentiment analysis', 'CSAT ratings', 'AI-generated summaries'],
                        'color' => 'emerald',
                    ],
                    [
                        'title' => 'Workflow Automation',
                        'desc'  => 'Trigger instant actions when events happen — new lead, hot lead, human takeover requested, offline form submitted.',
                        'points' => ['Email notifications', 'Slack & webhook triggers', 'Tag & segment leads', 'Custom delay schedules'],
                        'color' => 'amber',
                    ],
                    [
                        'title' => 'Full Customization',
                        'desc'  => 'Brand every pixel. Colors, fonts, avatars, positions, tones, welcome messages, suggested replies — make the widget feel native to your site.',
                        'points' => ['Brand color picker', 'Custom AI avatar', '4 personality tones', 'Responsive on all devices'],
                        'color' => 'rose',
                    ],
                    [
                        'title' => 'Enterprise Security',
                        'desc'  => 'Domain allowlist, rate limiting, encrypted API keys, GDPR-compliant data retention, and soft-delete for all records.',
                        'points' => ['Domain restriction', 'Rate limiting per IP', 'GDPR data controls', 'Encrypted credentials'],
                        'color' => 'sky',
                    ],
                ] as $i => $feat)
                <div x-show="activeFeat === {{ $i }}" class="bg-gray-900/60 border border-white/[0.06] rounded-2xl p-6 reveal">
                    <h3 class="text-base font-bold text-white mb-2">{{ $feat['title'] }}</h3>
                    <p class="text-sm text-gray-400 leading-relaxed mb-4">{{ $feat['desc'] }}</p>
                    <ul class="space-y-2">
                        @foreach($feat['points'] as $pt)
                        <li class="flex items-center gap-2 text-sm text-gray-300">
                            <svg class="w-4 h-4 text-{{ $feat['color'] }}-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ $pt }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endforeach
            </div>

            {{-- Feature cards grid --}}
            <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    ['🤖', 'RAG AI Engine',         'Accurate answers from your data, not hallucinations.',            'brand', 0],
                    ['🎯', 'Smart Lead Scoring',     'AI scores every lead 0-100 with hot/warm/cold classification.',   'accent', 1],
                    ['📊', 'Advanced Analytics',     'Funnels, sentiment, CSAT, accuracy, and AI weekly insights.',     'emerald', 2],
                    ['⚡', 'Workflow Automations',   'Email, Slack, and webhook triggers on any conversation event.',   'amber', 3],
                    ['🎨', 'Full Customization',     'Colors, avatars, tones — your brand, your widget.',               'rose', 4],
                    ['🔒', 'Enterprise Security',    'Domain lock, rate limits, encrypted keys, GDPR ready.',           'sky', 5],
                    ['👥', 'Team Collaboration',     'Role-based access for agents, admins, and viewers.',              'accent', 0],
                    ['💬', 'Live Chat Handoff',      'Seamlessly transfer from AI to human when it matters most.',      'teal', 0],
                ] as [$emoji, $title, $desc, $color, $tab])
                <div @click="activeFeat = {{ $tab }}" class="feature-card bg-gray-900/40 border border-white/[0.06] hover:border-{{ $color }}-500/25 rounded-2xl p-5 cursor-pointer group reveal">
                    <div class="text-2xl mb-3">{{ $emoji }}</div>
                    <h4 class="text-sm font-semibold text-white mb-1.5 group-hover:text-{{ $color }}-300 transition">{{ $title }}</h4>
                    <p class="text-xs text-gray-500 leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============ TESTIMONIALS ============ --}}
<section class="py-24 bg-gray-900/20">
    <div class="max-w-6xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <h2 class="text-3xl sm:text-4xl font-bold mb-4">Loved by <span class="grad-text">Sales Teams</span></h2>
            <p class="text-gray-400">Real results from real businesses using {{ config('branding.name') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach([
                [
                    'quote'   => '"We went from manually qualifying 50 leads/week to the AI handling 500+ automatically. Our close rate jumped 40% in the first month."',
                    'name'    => 'Sarah Chen',
                    'title'   => 'Head of Sales, NexusTech',
                    'avatar'  => 'SC',
                    'color'   => 'brand',
                    'stars'   => 5,
                    'metric'  => '+40% Close Rate',
                ],
                [
                    'quote'   => '"The setup took 8 minutes. Seriously — I pasted the script, uploaded our FAQ, and the bot was answering questions accurately on day one."',
                    'name'    => 'Marcus Rivera',
                    'title'   => 'Founder, Orbit Labs',
                    'avatar'  => 'MR',
                    'color'   => 'accent',
                    'stars'   => 5,
                    'metric'  => '8-Minute Setup',
                ],
                [
                    'quote'   => '"The analytics alone are worth it. Seeing exactly where visitors drop off in the conversation helped us completely rewrite our sales approach."',
                    'name'    => 'Priya Sharma',
                    'title'   => 'Growth Manager, StreamFlow',
                    'avatar'  => 'PS',
                    'color'   => 'emerald',
                    'stars'   => 5,
                    'metric'  => '3× Lead Volume',
                ],
            ] as $i => $t)
            <div class="testi-card border border-white/[0.06] rounded-2xl p-6 reveal" style="animation-delay: {{ $i * 100 }}ms">
                {{-- Stars --}}
                <div class="flex items-center gap-0.5 mb-4">
                    @for($s = 0; $s < $t['stars']; $s++)
                    <svg class="w-4 h-4 text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z" /></svg>
                    @endfor
                </div>
                <p class="text-sm text-gray-300 leading-relaxed mb-5 italic">{{ $t['quote'] }}</p>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-{{ $t['color'] }}-600/30 rounded-xl flex items-center justify-center text-{{ $t['color'] }}-300 text-xs font-bold">{{ $t['avatar'] }}</div>
                        <div>
                            <p class="text-sm font-semibold text-white">{{ $t['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $t['title'] }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-{{ $t['color'] }}-400 bg-{{ $t['color'] }}-500/10 px-2.5 py-1 rounded-lg">{{ $t['metric'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ INTEGRATIONS ============ --}}
<section class="py-20">
    <div class="max-w-5xl mx-auto px-5 lg:px-8 text-center">
        <div class="reveal mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold mb-3">Integrates with Your Stack</h2>
            <p class="text-gray-400 text-sm">Works with any website or platform. Connects to your favourite tools.</p>
        </div>
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
            @foreach([
                ['WordPress',  '🔷'], ['Shopify',    '🟢'], ['Webflow',    '🔵'],
                ['Slack',      '🟣'], ['HubSpot',    '🟠'], ['Zapier',     '🟡'],
                ['React',      '⚛️'],  ['Vue',        '💚'], ['Framer',     '⬛'],
                ['Wix',        '🔴'], ['Squarespace','◼️'],  ['Webhooks',   '🔗'],
            ] as [$name, $icon])
            <div class="reveal feature-card bg-gray-900/40 border border-white/[0.06] hover:border-brand-500/20 rounded-2xl p-4 flex flex-col items-center gap-2">
                <span class="text-2xl">{{ $icon }}</span>
                <span class="text-xs text-gray-400 font-medium">{{ $name }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ OMNICHANNEL ============ --}}
<section class="py-24 bg-gray-900/20">
    <div class="max-w-6xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-full text-xs text-emerald-400 font-medium mb-4">
                Omnichannel Ready
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold mb-4">Meet Customers <span class="grad-text">Wherever They Are</span></h2>
            <p class="text-gray-400 max-w-2xl mx-auto">One AI brain, nine channels. Your widget conversations sync across every platform — and your team manages them all in a single inbox.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-12">
            @foreach([
                ['💬', 'WhatsApp',   'emerald', '2B+ users worldwide'],
                ['✈️',  'Telegram',   'sky',     'Bot API + groups'],
                ['📘', 'Facebook',   'blue',    'Messenger & pages'],
                ['📸', 'Instagram',  'pink',    'DMs & story replies'],
                ['💼', 'Slack',      'accent',  'Team workspace bots'],
                ['🎮', 'Discord',    'brand',  'Server & DM channels'],
                ['📱', 'SMS',        'rose',    'Twilio-powered texts'],
                ['🐦', 'Twitter/X',  'gray',    'DMs & mentions'],
                ['📧', 'Email',      'accent',  'Inbox & auto-replies'],
                ['🌐', 'Web Widget', 'amber',   'Embedded chat widget'],
            ] as [$emoji, $name, $color, $desc])
            <div class="reveal feature-card bg-gray-900/40 border border-white/6 hover:border-{{ $color }}-500/25 rounded-2xl p-4 text-center group">
                <div class="w-12 h-12 bg-{{ $color }}-500/10 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 group-hover:bg-{{ $color }}-500/20 transition">{{ $emoji }}</div>
                <p class="text-sm font-semibold text-white mb-1">{{ $name }}</p>
                <p class="text-[10px] text-gray-500 leading-tight">{{ $desc }}</p>
            </div>
            @endforeach
        </div>

        {{-- Feature highlights row --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach([
                ['🔄', 'Unified Inbox', 'Every message from every channel lands in one place. No tab-switching, no missed conversations.', 'brand'],
                ['🤖', 'One AI for All Channels', 'Train once, deploy everywhere. Your AI uses the same knowledge base across WhatsApp, email, and web.', 'emerald'],
                ['📊', 'Per-Channel Analytics', 'See which channels drive the most leads, best response times, and highest satisfaction scores.', 'amber'],
            ] as [$emoji, $title, $desc, $color])
            <div class="reveal bg-gray-900/40 border border-white/6 rounded-2xl p-6 flex gap-4">
                <div class="w-11 h-11 bg-{{ $color }}-500/10 rounded-xl flex items-center justify-center text-xl shrink-0">{{ $emoji }}</div>
                <div>
                    <h4 class="text-sm font-bold text-white mb-1.5">{{ $title }}</h4>
                    <p class="text-xs text-gray-400 leading-relaxed">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ PRICING ============ --}}
<section id="pricing" class="py-24 bg-gray-900/20">
    <div class="max-w-6xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-12 reveal">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-full text-xs text-emerald-400 font-medium mb-4">
                Transparent Pricing
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold mb-3">Start Free. <span class="grad-text">Scale Fast.</span></h2>
            <p class="text-gray-400">All plans include a 14-day free trial. Upgrade or cancel anytime.</p>
        </div>

        {{-- Billing toggle --}}
        <div class="flex justify-center mb-10" x-data>
            <div class="inline-flex bg-gray-900/80 border border-white/10 rounded-xl p-1">
                <button @click="pricingCycle = 'monthly'" :class="pricingCycle === 'monthly' ? 'bg-white/10 text-white shadow-sm' : 'text-gray-400'" class="px-6 py-2 text-sm font-medium rounded-lg transition">Monthly</button>
                <button @click="pricingCycle = 'yearly'"  :class="pricingCycle === 'yearly'  ? 'bg-white/10 text-white shadow-sm' : 'text-gray-400'" class="px-6 py-2 text-sm font-medium rounded-lg transition">
                    Yearly
                    <span class="ml-1.5 px-1.5 py-0.5 bg-emerald-500/20 text-emerald-400 text-[10px] font-bold rounded">-20%</span>
                </button>
            </div>
        </div>

        @if(!empty($plans) && count($plans) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($plans as $plan)
            @php
                $popular = $plan->is_popular ?? false;
                $checkIcon = '<svg class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
                $features = [
                    ($plan->widgets_limit == -1 ? 'Unlimited' : $plan->widgets_limit) . ' widget' . ($plan->widgets_limit != 1 ? 's' : ''),
                    ($plan->messages_per_month == -1 ? 'Unlimited' : number_format($plan->messages_per_month)) . ' messages/mo',
                    ($plan->leads_per_month == -1 ? 'Unlimited' : number_format($plan->leads_per_month)) . ' leads/mo',
                    ($plan->team_members_limit == -1 ? 'Unlimited' : ($plan->team_members_limit ?? 1)) . ' team member' . (($plan->team_members_limit ?? 1) != 1 ? 's' : ''),
                ];
                if ($plan->has_live_chat ?? false)         $features[] = 'Live chat handoff';
                if ($plan->has_ai_insights ?? false)       $features[] = 'AI insights & reports';
                if ($plan->has_custom_branding ?? false)   $features[] = 'Remove branding';
                if ($plan->has_webhooks ?? false)          $features[] = 'Webhooks & API';
                if ($plan->has_priority_support ?? false)  $features[] = 'Priority support';
            @endphp
            <div class="reveal relative flex flex-col {{ $popular ? 'ring-2 ring-brand-500/50 shadow-2xl shadow-brand-500/20' : '' }} bg-gray-900/60 border {{ $popular ? 'border-brand-500/30' : 'border-white/[0.06]' }} rounded-2xl p-6">
                @if($popular)
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-brand-600 to-accent-600 text-white text-[10px] font-black rounded-full uppercase tracking-wider shadow-lg shadow-brand-500/30">Most Popular</span>
                @endif
                <div class="mb-5">
                    <h3 class="text-base font-bold text-white">{{ $plan->name }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $plan->description ?? 'Perfect for ' . strtolower($plan->name) . ' teams' }}</p>
                </div>
                <div class="mb-6">
                    <div x-show="pricingCycle === 'monthly'" class="flex items-end gap-1.5">
                        <span class="text-4xl font-black text-white">${{ number_format($plan->price_monthly, 0) }}</span>
                        <span class="text-sm text-gray-500 mb-1.5">/mo</span>
                    </div>
                    <div x-show="pricingCycle === 'yearly'" class="flex items-end gap-1.5">
                        <span class="text-4xl font-black text-white">${{ number_format($plan->price_yearly / 12, 0) }}</span>
                        <span class="text-sm text-gray-500 mb-1.5">/mo</span>
                    </div>
                    <p x-show="pricingCycle === 'yearly'" class="text-xs text-emerald-400 mt-1">Billed ${{ number_format($plan->price_yearly, 0) }}/yr · Save ${{ number_format(($plan->price_monthly * 12) - $plan->price_yearly, 0) }}</p>
                    <p x-show="pricingCycle === 'monthly'" class="text-xs text-gray-600 mt-1">Switch to yearly and save 20%</p>
                </div>
                <ul class="space-y-2.5 mb-7 flex-1">
                    @foreach($features as $feature)
                    <li class="flex items-start gap-2 text-xs text-gray-400">{!! $checkIcon !!}{{ $feature }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center py-3 text-sm font-semibold rounded-xl transition {{ $popular ? 'bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white shadow-lg shadow-brand-500/25' : 'bg-white/5 hover:bg-white/10 text-white border border-white/10 hover:border-white/20' }}">
                    Start Free Trial
                </a>
            </div>
            @endforeach
        </div>
        @else
        {{-- Fallback when no plans seeded --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 max-w-3xl mx-auto">
            @foreach([
                ['Starter', '$0', 'Free forever', ['1 widget', '500 messages/mo', '50 leads/mo', 'Basic analytics'], false],
                ['Pro',     '$49', '/month', ['5 widgets', '10,000 messages/mo', 'Unlimited leads', 'Live chat handoff', 'AI insights', 'Remove branding'], true],
                ['Agency',  '$149', '/month', ['Unlimited widgets', 'Unlimited messages', 'Unlimited leads', 'Custom AI models', 'White-label', 'Priority support'], false],
            ] as [$name, $price, $period, $feats, $pop])
            <div class="reveal relative flex flex-col {{ $pop ? 'ring-2 ring-brand-500/40 shadow-2xl shadow-brand-500/15' : '' }} bg-gray-900/60 border {{ $pop ? 'border-brand-500/25' : 'border-white/[0.06]' }} rounded-2xl p-6">
                @if($pop)<span class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-brand-600 to-accent-600 text-white text-[10px] font-black rounded-full uppercase">Popular</span>@endif
                <h3 class="text-base font-bold text-white mb-1">{{ $name }}</h3>
                <div class="flex items-end gap-1 mb-5 mt-3">
                    <span class="text-4xl font-black text-white">{{ $price }}</span>
                    <span class="text-sm text-gray-500 mb-1.5">{{ $period }}</span>
                </div>
                <ul class="space-y-2.5 mb-6 flex-1">
                    @foreach($feats as $f)
                    <li class="flex items-center gap-2 text-xs text-gray-400">
                        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="block text-center py-3 text-sm font-semibold rounded-xl transition {{ $pop ? 'bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white shadow-lg shadow-brand-500/25' : 'bg-white/5 hover:bg-white/10 text-white border border-white/10' }}">
                    Get Started
                </a>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Money back guarantee --}}
        <div class="text-center mt-10 reveal">
            <div class="inline-flex items-center gap-3 px-5 py-3 bg-gray-900/60 border border-white/[0.06] rounded-2xl">
                <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                <span class="text-sm text-gray-300">30-day money-back guarantee · No questions asked</span>
            </div>
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section id="faq" class="py-24">
    <div class="max-w-3xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold mb-3">Frequently Asked Questions</h2>
            <p class="text-gray-400">Can't find what you're looking for? <a href="mailto:{{ config('branding.support_email', 'support@example.com') }}" class="text-brand-400 hover:text-brand-300 transition">Contact our team →</a></p>
        </div>
        <div class="space-y-3">
            @foreach([
                ['How does AI training work?', 'You provide your data — URLs, PDFs, text, or FAQs — and our system breaks it into chunks, generates vector embeddings, and stores them in a knowledge base. When a visitor asks a question, the AI retrieves the most relevant context and generates a grounded, accurate response using GPT-4o or your chosen model. No hallucinations.'],
                ['Can I use it with my existing website?', 'Yes — it works with any website. Paste a single <script> tag into your HTML and the widget appears instantly. Compatible with WordPress, Shopify, Webflow, Framer, React, Vue, plain HTML, and more. No rebuild or redeployment needed.'],
                ['What happens when the AI can\'t answer?', 'The AI uses confidence-based fallback. If confidence is below your threshold, it can gracefully transfer to a live human agent (if you have live chat enabled), collect the visitor\'s contact info for follow-up, or display a custom fallback message.'],
                ['Is there a free trial?', 'Yes — every paid plan includes a 14-day free trial. No credit card required to start. You can explore all features, capture real leads, and see the results before committing to anything.'],
                ['How is lead scoring calculated?', 'The AI analyzes the conversation and extracts signals: budget mentioned, timeline urgency, problem clarity, engagement depth, and purchase intent language. Each signal is weighted to produce a 0-100 score with automatic hot/warm/cold classification.'],
                ['Can I white-label the widget?', 'Yes — Pro and Agency plans support custom branding. Remove the "Powered by" badge, use your own logo and domain, set custom AI names and avatars, and match your brand colors exactly.'],
                ['What AI models are supported?', 'We support OpenAI (GPT-4o, GPT-4 Turbo, GPT-3.5), Anthropic (Claude 3 Opus, Sonnet, Haiku), and bring-your-own-key. You can switch models per widget without any data loss.'],
                ['Is my data secure?', 'All data is encrypted in transit (TLS 1.3) and at rest (AES-256). We support GDPR data deletion requests, soft deletes on all records, rate limiting, domain allowlisting, and never use your data to train public AI models.'],
            ] as $i => [$q, $a])
            <div x-data class="reveal bg-gray-900/40 border border-white/[0.06] rounded-2xl overflow-hidden hover:border-white/10 transition">
                <button @click="faqOpen = faqOpen === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between p-5 text-left gap-4">
                    <span class="text-sm font-semibold text-white">{{ $q }}</span>
                    <svg class="w-5 h-5 text-gray-500 shrink-0 transition-transform duration-200"
                         :class="faqOpen === {{ $i }} ? 'rotate-180 text-brand-400' : ''"
                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="faqOpen === {{ $i }}" x-collapse class="px-5 pb-5 text-sm text-gray-400 leading-relaxed border-t border-white/[0.04] pt-4">{{ $a }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ FINAL CTA ============ --}}
<section class="py-24">
    <div class="max-w-5xl mx-auto px-5 lg:px-8">
        <div class="relative overflow-hidden bg-gradient-to-br from-brand-600/20 via-accent-600/15 to-accent-600/10 border border-brand-500/20 rounded-3xl p-12 text-center glow-brand">
            {{-- Background pattern --}}
            <div class="absolute inset-0 grid-pattern opacity-50 pointer-events-none"></div>
            {{-- Blobs --}}
            <div class="absolute -top-20 -right-20 w-64 h-64 bg-brand-600/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-accent-600/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white/5 border border-white/10 rounded-full text-xs text-gray-300 font-medium mb-6">
                    🚀 Join 12,000+ businesses already converting with AI
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black mb-5 leading-tight">
                    Start Capturing Leads<br><span class="grad-text">While You Sleep Tonight</span>
                </h2>
                <p class="text-gray-300 text-lg mb-8 max-w-lg mx-auto leading-relaxed">
                    14-day free trial. No credit card. Setup in 2 minutes. Your first lead capture could happen tonight.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('register') }}"
                       class="group inline-flex items-center gap-2.5 px-10 py-4 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold rounded-2xl transition-all duration-200 shadow-2xl shadow-brand-500/40 text-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                        Deploy Your AI Agent Free
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                    <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-white transition px-4 py-4">
                        Already have an account? Sign in →
                    </a>
                </div>
                <p class="text-xs text-gray-500 mt-5">No credit card required · SOC 2 compliant · GDPR ready</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ FOOTER ============ --}}
<footer class="border-t border-white/[0.05] bg-gray-950/60">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 py-14">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-10 mb-12">
            {{-- Brand --}}
            <div class="col-span-2">
                <a href="/" class="flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 bg-gradient-to-br from-brand-500 to-accent-600 rounded-xl flex items-center justify-center shadow-lg shadow-brand-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                    </div>
                    <span class="text-base font-bold">{{ config('branding.name') }}</span>
                </a>
                <p class="text-sm text-gray-500 leading-relaxed max-w-xs mb-5">AI-powered chat widget that captures leads, answers questions, and converts visitors 24/7.</p>
                <div class="flex items-center gap-3">
                    <a href="#" class="w-8 h-8 bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg flex items-center justify-center text-gray-500 hover:text-white transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.84L1.254 2.25H8.08l4.261 5.636 5.903-5.636Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="w-8 h-8 bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg flex items-center justify-center text-gray-500 hover:text-white transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a href="#" class="w-8 h-8 bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg flex items-center justify-center text-gray-500 hover:text-white transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0 1 12 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
                    </a>
                </div>
            </div>

            {{-- Product --}}
            <div>
                <p class="text-xs font-semibold text-white uppercase tracking-wider mb-4">Product</p>
                <ul class="space-y-2.5 text-sm text-gray-500">
                    <li><a href="#features" class="hover:text-white transition">Features</a></li>
                    <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
                    <li><a href="{{ route('changelog') }}" class="hover:text-white transition">Changelog</a></li>
                    <li><a href="{{ route('roadmap') }}" class="hover:text-white transition">Roadmap</a></li>
                    <li><a href="{{ route('status') }}" class="hover:text-white transition">Status</a></li>
                </ul>
            </div>

            {{-- Resources --}}
            <div>
                <p class="text-xs font-semibold text-white uppercase tracking-wider mb-4">Resources</p>
                <ul class="space-y-2.5 text-sm text-gray-500">
                    <li><a href="{{ route('documentation') }}" class="hover:text-white transition">Documentation</a></li>
                    <li><a href="{{ route('api.reference') }}" class="hover:text-white transition">API Reference</a></li>
                    <li><a href="{{ route('blog') }}" class="hover:text-white transition">Blog</a></li>
                    <li><a href="{{ route('case-studies') }}" class="hover:text-white transition">Case Studies</a></li>
                    <li><a href="#faq" class="hover:text-white transition">FAQ</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <p class="text-xs font-semibold text-white uppercase tracking-wider mb-4">Company</p>
                <ul class="space-y-2.5 text-sm text-gray-500">
                    <li><a href="{{ route('about') }}" class="hover:text-white transition">About</a></li>
                    <li><a href="{{ route('careers') }}" class="hover:text-white transition">Careers</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:text-white transition">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-white transition">Terms of Service</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white transition">Contact</a></li>
                </ul>
            </div>
        </div>

        <div class="pt-8 border-t border-white/[0.05] flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-600">
            <p>© {{ date('Y') }} {{ config('branding.name') }}. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                    All systems operational
                </span>
                <span>GDPR Compliant</span>
                <span>SOC 2 Type II</span>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
