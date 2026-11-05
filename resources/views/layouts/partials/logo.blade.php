{{-- PoriFyx mark: a "P" cut from a speech bubble. Pass $size (px) to scale. --}}
@php $size = $size ?? 36; @endphp
@if(config('branding.logo_url'))
    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.name') }}" style="height: {{ $size }}px" class="shrink-0">
@else
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 40 40" class="shrink-0" aria-hidden="true">
        <path d="M8 4h24a6 6 0 0 1 6 6v16a6 6 0 0 1-6 6H17l-8 6v-6H8a6 6 0 0 1-6-6V10a6 6 0 0 1 6-6z" fill="#2f5ee8"/>
        <path d="M15 11h7.5a5.5 5.5 0 0 1 0 11H19v5h-4V11zm4 3.6v3.8h3.3a1.9 1.9 0 0 0 0-3.8H19z" fill="#fff"/>
        <circle cx="31" cy="11" r="3" fill="#22d3ee"/>
    </svg>
@endif
