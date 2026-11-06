<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>What's new · {{ config('branding.name') }}</title>
    <meta name="description" content="New features, improvements and fixes in {{ config('branding.name') }}.">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[#f6f7fb] text-slate-800 antialiased" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif">
<main class="max-w-2xl mx-auto px-5 py-12">
    <a href="{{ url('/') }}" class="inline-block">@include('layouts.partials.logo', ['size' => 34])</a>
    <h1 class="mt-8 text-3xl font-extrabold tracking-tight">What's new</h1>
    <ol class="mt-8 space-y-6 border-l-2 border-slate-200 pl-6">
        @forelse($entries as $e)
        <li class="relative">
            <span class="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-[#ff6a2b] ring-4 ring-[#f6f7fb]"></span>
            <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($e->published_at)->format('j F Y') }} · <span class="uppercase font-bold tracking-wider">{{ $e->tag }}</span></p>
            <h2 class="mt-1 text-lg font-bold">{{ $e->title }}</h2>
            <p class="mt-1 text-slate-600 whitespace-pre-line">{{ $e->body }}</p>
        </li>
        @empty
        <li class="text-slate-500">Nothing here yet.</li>
        @endforelse
    </ol>
</main>
</body>
</html>
