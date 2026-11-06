@extends('layouts.app')
@section('title', 'Playground: ' . $widget->name)
@section('page-title', 'Playground')
@section('content')
@php
    // The widget runs inside a same-origin frame that looks like a blank website.
    $frame = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<style>body{margin:0;min-height:100vh;font-family:system-ui,sans-serif;background:linear-gradient(180deg,#f4f6fb,#e8edf6);color:#64748b}'
        . '.s{max-width:520px;margin:48px auto;padding:0 24px}.b{height:14px;border-radius:7px;background:#dfe5ef;margin:12px 0}</style></head><body>'
        . '<div class="s"><div class="b" style="width:40%;height:22px;background:#cfd8e6"></div><div class="b"></div><div class="b" style="width:85%"></div><div class="b" style="width:65%"></div>'
        . '<p style="font-size:13px;margin-top:28px">Your website. The assistant opens in the corner, exactly as visitors will see it.</p></div>'
        . '<script src="' . asset('widget.js') . '?v=' . filemtime(public_path('widget.js')) . '" data-widget-id="' . $widget->uuid . '" data-api-base="' . url('/api/widget') . '" data-playground="' . $widget->playgroundToken() . '"></script>'
        . '<script>var pgT=setInterval(function(){if(document.getElementById("pfx-launcher")){clearInterval(pgT);window.pfx("open")}},200)</script></body></html>';
    $checks = [
        ['AI replies', $plan['chat'], 'Monthly AI messages left on your plan', 'Visitors see a "leave a message" form instead of AI replies'],
        ['Lead capture', $plan['leads'], 'New leads are saved', 'New leads are not saved until next month or an upgrade'],
        ['Bookings', $plan['bookings'], 'Visitors can book in the chat', 'The booking button is hidden from visitors'],
        ['Human handoff', $plan['handoff'], '"Talk to a human" is available', 'Not in your plan: the button is hidden'],
    ];
@endphp
<div class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-6" x-data="{ k: 0, reset() { localStorage.setItem('pfx_fp', 'pg-' + Math.random().toString(36).slice(2)); this.k++ } }">
    <div class="min-w-0 rounded-2xl border border-white/5 bg-gray-900/50 overflow-hidden flex flex-col">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-white/5">
            <span class="flex gap-1.5" aria-hidden="true"><i class="w-2.5 h-2.5 rounded-full bg-rose-400/70"></i><i class="w-2.5 h-2.5 rounded-full bg-amber-400/70"></i><i class="w-2.5 h-2.5 rounded-full bg-emerald-400/70"></i></span>
            <span class="flex-1 min-w-0 truncate rounded-lg bg-white/5 px-3 py-1 text-xs text-gray-400">{{ $widget->domain ?: 'your-website.com' }}</span>
            <button type="button" @click="reset()" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/5 hover:bg-white/10 text-gray-200">New test chat</button>
        </div>
        <template x-for="i in [k]" :key="i">
            <iframe title="Widget playground" class="w-full h-[640px] max-h-[75dvh] bg-white" srcdoc="{{ $frame }}"></iframe>
        </template>
    </div>

    <aside class="space-y-4">
        <div class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <h3 class="text-sm font-semibold text-white">Test before you launch</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-gray-500">Chat with {{ $widget->ai_name ?: 'your assistant' }} exactly as a visitor would. Playground chats work even while the widget is off and do not use your monthly messages.</p>
            <p class="mt-3 inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $widget->is_active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-amber-500/15 text-amber-400' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $widget->is_active ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                {{ $widget->is_active ? 'Live on your website' : 'Not live yet: visitors cannot see it' }}
            </p>
        </div>

        <div class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <h3 class="text-sm font-semibold text-white">What visitors get on your plan</h3>
            <ul class="mt-3 space-y-3">
                @foreach($checks as [$name, $ok, $yes, $no])
                <li class="flex gap-2.5">
                    <span class="mt-0.5 grid place-items-center w-5 h-5 shrink-0 rounded-full text-[11px] font-bold {{ $ok ? 'bg-emerald-500/15 text-emerald-400' : 'bg-rose-500/15 text-rose-400' }}">{{ $ok ? '✓' : '!' }}</span>
                    <span class="min-w-0"><span class="block text-sm font-medium text-gray-200">{{ $name }}</span><span class="block text-xs text-gray-500">{{ $ok ? $yes : $no }}</span></span>
                </li>
                @endforeach
            </ul>
            @unless($plan['chat'] && $plan['leads'] && $plan['bookings'])
            <a href="{{ route('settings.billing') }}" class="mt-4 inline-flex w-full justify-center px-4 py-2 rounded-xl bg-brand-500 text-white text-sm font-semibold">Upgrade or renew</a>
            @endunless
        </div>

        <div class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <h3 class="text-sm font-semibold text-white">Try asking</h3>
            <ul class="mt-2 space-y-1.5 text-xs text-gray-400">
                <li>• A question your website answers (prices, hours, delivery)</li>
                <li>• Something off-topic, to check it stays on your business</li>
                <li>• "I'd like a quote", then give an email, to test lead capture</li>
                <li>• "Can I book an appointment?" if bookings are on</li>
            </ul>
            <a href="{{ route('widgets.knowledge.index', $widget) }}" class="mt-4 inline-block text-xs font-semibold text-brand-400 hover:text-brand-300">Wrong answer? Improve the knowledge base →</a>
        </div>
    </aside>
</div>
@endsection
