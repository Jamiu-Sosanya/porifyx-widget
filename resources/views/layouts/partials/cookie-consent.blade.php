{{-- Cookie consent banner. Content and layout are set by admins in Admin › Cookie consent.
     Choice is stored as {v, analytics, marketing, at} in localStorage + the pfx_consent cookie,
     and announced with a "pfx:consent" window event. Reopen with window.dispatchEvent(new Event('pfx-consent-open')). --}}
@php
    $c = \App\Support\CookieConsent::get();
    $preview = request()->boolean('cookie_preview');
    $wrap = [
        'bar'       => 'inset-x-0 bottom-0',
        'box-left'  => 'inset-x-3 bottom-3 sm:inset-x-auto sm:left-5 sm:bottom-5 sm:w-[400px]',
        'box-right' => 'inset-x-3 bottom-3 sm:inset-x-auto sm:right-5 sm:bottom-5 sm:w-[400px]',
        'modal'     => '',
    ][$c['layout']] ?? 'inset-x-0 bottom-0';
    $isBar = $c['layout'] === 'bar';
    $isModal = $c['layout'] === 'modal';
    $overlay = $isModal || $c['block_page'];
    $outer = $overlay ? 'inset-0 bg-slate-950/50 backdrop-blur-[2px]' . ($isModal ? ' grid place-items-center p-4' : '') : $wrap;
    $inner = $overlay && ! $isModal ? 'absolute ' . $wrap : '';
    $btn = 'px-4 py-2.5 rounded-xl text-sm font-semibold transition whitespace-nowrap';
    $categories = array_filter([
        'analytics' => $c['analytics'] ? ['Analytics', 'Help us understand which pages are useful and where people get stuck. Data is aggregated and never sold.'] : null,
        'marketing' => $c['marketing'] ? ['Marketing', 'Let us measure campaigns and show relevant messages on other sites.'] : null,
    ]);
@endphp
@if($c['enabled'] || $preview)
<div x-data="{
        show: false, prefs: false, v: {{ (int) $c['version'] }},
        choice: { analytics: false, marketing: false },
        init() {
            let saved = null;
            try { saved = JSON.parse(localStorage.getItem('pfx_consent')); } catch (e) {}
            if (saved && saved.v === this.v) { this.choice = { analytics: !!saved.analytics, marketing: !!saved.marketing }; }
            this.show = {{ $preview ? 'true' : 'false' }} || !saved || saved.v !== this.v;
            window.addEventListener('pfx-consent-open', () => { this.show = true; this.prefs = {{ $categories ? 'true' : 'false' }}; });
        },
        store(analytics, marketing) {
            const value = { v: this.v, analytics, marketing, at: new Date().toISOString() };
            localStorage.setItem('pfx_consent', JSON.stringify(value));
            document.cookie = 'pfx_consent=' + encodeURIComponent(JSON.stringify(value)) + ';path=/;max-age=31536000;SameSite=Lax';
            window.dispatchEvent(new CustomEvent('pfx:consent', { detail: value }));
            this.choice = { analytics, marketing }; this.show = false; this.prefs = false;
        },
     }"
     x-show="show" x-cloak style="display:none"
     class="fixed z-[60] {{ $outer }}"
     role="dialog" aria-modal="{{ $overlay ? 'true' : 'false' }}" aria-labelledby="pfx-consent-title"
     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="{{ $inner }}
                {{ $isBar ? 'border-t' : 'border rounded-2xl' }} {{ $isModal ? 'w-full max-w-lg' : '' }}
                bg-white text-slate-900 border-slate-200 shadow-2xl shadow-slate-900/15 dark:bg-[#0d1526] dark:text-white dark:border-white/10 dark:shadow-black/50">
        <div class="{{ $isBar ? 'max-w-6xl mx-auto px-5 py-4 md:flex md:items-center md:gap-8' : 'p-5 sm:p-6' }}">
            <div class="flex gap-3 min-w-0 {{ $isBar ? 'md:flex-1' : '' }}">
                <span class="hidden sm:grid place-items-center w-10 h-10 shrink-0 rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-300" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 9 9 4 4 0 0 1-4-4 4 4 0 0 1-4-4 1 1 0 0 0-1-1z"/><circle cx="8.5" cy="11" r="1" fill="currentColor"/><circle cx="12" cy="16" r="1" fill="currentColor"/><circle cx="15.5" cy="13" r="1" fill="currentColor"/></svg>
                </span>
                <div class="min-w-0">
                    <p id="pfx-consent-title" class="font-bold">{{ $c['title'] }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $c['message'] }}
                        <a href="{{ route('privacy') }}#cookies" class="font-semibold text-brand-600 dark:text-brand-300 underline underline-offset-2">Cookie policy</a></p>
                </div>
            </div>

            @if($categories)
            <div x-show="prefs" x-collapse.duration.200ms class="mt-4 space-y-2.5">
                <div class="flex items-start justify-between gap-4 rounded-xl p-3 bg-slate-50 dark:bg-white/[0.04]">
                    <div><p class="text-sm font-semibold">Essential</p><p class="text-xs text-slate-500 dark:text-slate-400">Sign-in, security and remembering this choice. Always on.</p></div>
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 shrink-0 pt-0.5">Always on</span>
                </div>
                @foreach($categories as $key => [$label, $about])
                <label class="flex items-start justify-between gap-4 rounded-xl p-3 bg-slate-50 dark:bg-white/[0.04] cursor-pointer">
                    <div><p class="text-sm font-semibold">{{ $label }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ $about }}</p></div>
                    <input type="checkbox" x-model="choice.{{ $key }}" class="peer sr-only">
                    <span class="relative shrink-0 w-10 h-6 rounded-full bg-slate-300 dark:bg-white/15 peer-checked:bg-brand-500 transition after:absolute after:top-1 after:left-1 after:w-4 after:h-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-500/30"></span>
                </label>
                @endforeach
            </div>
            @endif

            <div class="mt-4 {{ $isBar ? 'md:mt-0 md:shrink-0' : '' }} flex flex-wrap gap-2 {{ $isBar ? '' : 'sm:justify-end' }}">
                @if($categories && $c['show_customize'])
                    <button type="button" x-show="!prefs" @click="prefs = true" class="{{ $btn }} text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ $c['customize_label'] }}</button>
                    <button type="button" x-show="prefs" @click="store(choice.analytics, choice.marketing)" class="{{ $btn }} border border-slate-300 hover:border-slate-500 dark:border-white/20 dark:hover:border-white/40">Save choices</button>
                @endif
                @if($c['show_reject'])
                    <button type="button" @click="store(false, false)" class="{{ $btn }} border border-slate-300 hover:border-slate-500 dark:border-white/20 dark:hover:border-white/40">{{ $c['reject_label'] }}</button>
                @endif
                <button type="button" @click="store({{ $c['analytics'] ? 'true' : 'false' }}, {{ $c['marketing'] ? 'true' : 'false' }})" class="{{ $btn }} bg-brand-500 text-white hover:bg-brand-600 grow sm:grow-0">{{ $c['accept_label'] }}</button>
            </div>
        </div>
    </div>
</div>
@endif
