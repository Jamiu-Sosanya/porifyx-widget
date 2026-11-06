<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>{{ $ticket->subject }} · {{ $ticket->number }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[#f4f6fb] text-slate-800 antialiased" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif">
@php $brand = $ticket->widget?->primary_color ?: '#ff6a2b'; @endphp
<main class="max-w-2xl mx-auto px-4 py-10">
    <p class="text-sm font-semibold" style="color: {{ $brand }}">{{ $ticket->widget?->name ?: config('app.name') }} support</p>
    <h1 class="mt-1 text-2xl font-extrabold tracking-tight break-words">{{ $ticket->subject }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $ticket->number }} · {{ \App\Models\Ticket::STATUSES[$ticket->status] ?? $ticket->status }}</p>
    @if(session('success'))<p class="mt-4 rounded-xl bg-emerald-50 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</p>@endif
    <ol class="mt-6 space-y-3">
        @foreach($ticket->messages as $m)
        <li class="rounded-2xl p-4 {{ $m->type === 'reply' ? 'bg-white border border-slate-200 sm:mr-10' : 'sm:ml-10 text-white' }}" @if($m->type !== 'reply') style="background: {{ $brand }}" @endif>
            <p class="text-[11px] font-semibold opacity-70">{{ $m->type === 'reply' ? 'Support team' : 'You' }} · {{ $m->created_at->format('j M, H:i') }}</p>
            <div class="mt-1.5 text-sm whitespace-pre-line break-words">{{ $m->body }}</div>
        </li>
        @endforeach
    </ol>
    @if($ticket->status !== 'closed')
    <form method="POST" action="{{ route('tickets.public.reply', [$ticket->id, $token]) }}" class="mt-6 space-y-3">
        @csrf
        <textarea name="body" required rows="5" maxlength="10000" placeholder="Add a reply" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm"></textarea>
        @error('body')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        <button class="w-full sm:w-auto px-6 py-3 rounded-xl text-white text-sm font-semibold" style="background: {{ $brand }}">Send reply</button>
    </form>
    @else
    <p class="mt-6 text-sm text-slate-500">This request is closed. Contact us again if you need more help.</p>
    @endif
</main>
</body>
</html>
