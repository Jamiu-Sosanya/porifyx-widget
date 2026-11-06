{{--
    PoriFyx wordmark: "PoriFyx." with the orange dot on the i and the orange full stop.
    $size  = rough height in px (scales the whole mark).
    $tone  = 'auto' (navy in light mode, white in dark mode) or 'white' (always on dark surfaces).
    $tag   = false hides the small "Widget" product label.
--}}
@php
    $size = $size ?? 36;
    $tone = $tone ?? 'auto';
    $tag = $tag ?? true;
@endphp
@if(config('branding.logo_url'))
    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.name') }}" style="height: {{ $size }}px" class="shrink-0">
@else
    <span class="pfx-logo inline-flex items-baseline gap-[.4em] shrink-0 whitespace-nowrap {{ $tone === 'white' ? 'text-white' : 'text-[#0d1a33] dark:text-white' }}"
          style="font-size: {{ round($size * 0.66) }}px" role="img" aria-label="{{ config('branding.name') }}">
        <span class="font-extrabold tracking-[-.015em] leading-none" aria-hidden="true">Por<span class="relative mx-[.03em]">ı<span class="absolute left-1/2 top-[.06em] -translate-x-1/2 w-[.23em] h-[.23em] rounded-full bg-brand-500"></span></span>Fyx<span class="text-brand-500">.</span></span>
        @if($tag)
            <span class="self-center rounded-full px-[.55em] py-[.2em] text-[.42em] font-bold uppercase tracking-[.12em] leading-none bg-brand-500/15 text-brand-600 dark:text-brand-400 {{ $tone === 'white' ? '!text-brand-400' : '' }}" aria-hidden="true">Widget</span>
        @endif
    </span>
@endif
