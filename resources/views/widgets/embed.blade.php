@extends('layouts.app')
@section('title', 'Embed Widget: ' . $widget->name)
@section('page-title', 'Embed Code')
@section('content')
<div class="max-w-3xl">
    <a href="{{ route('widgets.edit', $widget) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white mb-6 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        Back to widget
    </a>

    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
        <h3 class="text-lg font-semibold text-white mb-1">Embed {{ $widget->name }}</h3>
        <p class="text-sm text-gray-400 mb-6">Copy and paste this code into your website's HTML, just before the closing <code class="text-brand-400">&lt;/body&gt;</code> tag.</p>

        <div class="relative" x-data="{ copied: false }">
            <pre class="bg-black/50 border border-white/10 rounded-xl p-5 text-sm text-emerald-400 font-mono overflow-x-auto"><code>&lt;!-- {{ config('branding.name') }} --&gt;
&lt;script&gt;
  (function(w,d,s,o,f,js,fjs){
    w['porifyxwidget']=o;w[o]=w[o]||function(){
    (w[o].q=w[o].q||[]).push(arguments)};
    js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
    js.id=o;js.src=f;js.async=1;fjs.parentNode.insertBefore(js,fjs);
  }(window,document,'script','pfx','{{ url('/widget.js') }}'));
  pfx('init', { widgetId: '{{ $widget->uuid }}', apiBase: @json(rtrim(url('/api/widget'), '/')) });
&lt;/script&gt;</code></pre>
            <button @click="navigator.clipboard.writeText($refs.embedCode?.innerText || $el.previousElementSibling.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                    :class="copied ? 'bg-emerald-500/20 text-emerald-400' : 'bg-white/5 text-gray-400 hover:text-white hover:bg-white/10'"
                    class="absolute top-3 right-3 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition">
                <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                <svg x-show="copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
            </button>
        </div>
    </div>

    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-3">Widget Token</h3>
        <div class="flex items-center gap-3">
            <code class="flex-1 bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-gray-400 font-mono truncate">{{ $widget->token }}</code>
            <form method="POST" action="{{ route('widgets.regenerateToken', $widget) }}" onsubmit="return confirm('Regenerating the token will invalidate the old one. Continue?')">
                @csrf
                <button type="submit" class="px-4 py-3 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white text-xs font-medium rounded-xl transition">Regenerate</button>
            </form>
        </div>
        <p class="mt-2 text-xs text-gray-500">Used by the WordPress plugin to sync content. Keep it private.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-6 mt-6">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
            <h3 class="text-base font-semibold text-white">WordPress &amp; WooCommerce</h3>
            <p class="mt-1 text-sm text-gray-400">Adds the widget to every page and keeps the assistant's knowledge in sync with posts, pages and products.</p>
            <ol class="mt-3 space-y-1 text-sm text-gray-300 list-decimal list-inside">
                <li>Download and upload the plugin in Plugins → Add New.</li>
                <li>Settings → PoriFyx Widget: paste the URL, Widget ID and token below.</li>
                <li>Click “Sync now”. Edits sync automatically after that.</li>
            </ol>
            <dl class="mt-3 space-y-1 text-xs text-gray-400">
                <div class="flex gap-2"><dt class="w-20 shrink-0">URL</dt><dd class="font-mono text-gray-200 break-all">{{ url('/') }}</dd></div>
                <div class="flex gap-2"><dt class="w-20 shrink-0">Widget ID</dt><dd class="font-mono text-gray-200 break-all">{{ $widget->uuid }}</dd></div>
            </dl>
            <a href="{{ route('widgets.wordpress', $widget) }}" class="mt-4 inline-block px-4 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Download plugin (.zip)</a>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
            <h3 class="text-base font-semibold text-white">Shopify, Wix, Squarespace &amp; others</h3>
            <ul class="mt-3 space-y-2 text-sm text-gray-300">
                <li><strong class="text-white">Shopify:</strong> Online Store → Themes → ⋯ → Edit code → <code>theme.liquid</code>; paste the code above just before <code>&lt;/body&gt;</code> and save.</li>
                <li><strong class="text-white">Wix:</strong> Settings → Custom code → Add code → Body - end, all pages.</li>
                <li><strong class="text-white">Squarespace:</strong> Settings → Advanced → Code injection → Footer.</li>
                <li><strong class="text-white">Webflow:</strong> Site settings → Custom code → Footer code, then publish.</li>
                <li><strong class="text-white">React / Next.js:</strong> load <code>{{ url('/widget.js') }}</code> with <code>data-widget-id</code> once in your root layout.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
