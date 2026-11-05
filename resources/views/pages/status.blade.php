@extends('layouts.site')
@section('title', 'System status')
@section('description', "Live status of {$name} services.")
@section('eyebrow', 'Status')
@php
    $allOk = collect($components)->every(fn ($c) => $c[1]['ok']);
@endphp
@section('heading', $allOk ? 'All systems are working normally' : 'Some services need attention')
@section('lead', 'This page checks our core services each time it loads, so what you see here is current. If something is not working for you but everything here looks fine, please let us know.')

@section('content')
<div class="max-w-4xl mx-auto px-5 py-14 md:py-20 space-y-12">

    <div class="rounded-2xl p-6 flex items-center gap-4 {{ $allOk ? 'bg-emerald-500/10 border border-emerald-500/30' : 'bg-amber-500/10 border border-amber-500/30' }}">
        <span class="relative flex w-3.5 h-3.5 shrink-0">
            <span class="absolute inline-flex w-full h-full rounded-full opacity-60 animate-ping {{ $allOk ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
            <span class="relative inline-flex w-3.5 h-3.5 rounded-full {{ $allOk ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
        </span>
        <div class="min-w-0">
            <p class="font-bold">{{ $allOk ? 'Operational' : 'Partial service' }}</p>
            <p class="text-sm {{ $muted }}">Checked {{ $checkedAt->format('j F Y, H:i') }} UTC · <a href="{{ route('status') }}" class="underline underline-offset-2">Refresh</a></p>
        </div>
    </div>

    <div class="rounded-2xl overflow-hidden divide-y divide-slate-200 dark:divide-white/10 {{ $card }}">
        @foreach($components as $label => [$about, $result])
        <div class="p-5 flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="font-semibold">{{ $label }}</p>
                <p class="text-sm {{ $muted }}">{{ $about }}</p>
            </div>
            <div class="text-right shrink-0">
                <p class="inline-flex items-center gap-2 text-sm font-semibold {{ $result['ok'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    <span class="w-2 h-2 rounded-full {{ $result['ok'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ $result['ok'] ? 'Operational' : ($label === 'AI responses' ? 'Limited' : 'Degraded') }}
                </p>
                @if($result['ms'])<p class="text-xs {{ $muted }}">{{ $result['ms'] }} ms</p>@endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="pfx-prose">
            <h2>Incident history</h2>
            <p>No incidents have been posted. When something goes wrong, we publish what happened, who was affected and what we changed to stop it happening again.</p>
        </div>
        <div class="pfx-prose">
            <h2>If something is not working</h2>
            <ul>
                <li>Check the <a href="{{ route('documentation') }}#troubleshooting">troubleshooting guide</a> for common fixes.</li>
                <li>If replies are slow, your AI provider may be busy. A backup provider takes over automatically if you have set one up.</li>
                <li>Still stuck? <a href="{{ route('contact', ['topic' => 'support']) }}">Contact support</a> and we will look into it straight away.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
