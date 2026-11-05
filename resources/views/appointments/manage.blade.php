<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Your booking with {{ $s['business_name'] }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[#f6f7fb] text-slate-900 antialiased grid place-items-center p-5">
@php
    $start = $a->localStart();
    $canCancel = in_array($a->status, \App\Models\Appointment::ACTIVE, true) && $a->starts_at->isFuture();
    $state = [
        'pending'   => ['Waiting for confirmation', 'bg-amber-100 text-amber-800'],
        'confirmed' => ['Confirmed', 'bg-emerald-100 text-emerald-800'],
        'completed' => ['Completed', 'bg-slate-100 text-slate-700'],
        'cancelled' => ['Cancelled', 'bg-rose-100 text-rose-800'],
        'no_show'   => ['Missed', 'bg-slate-100 text-slate-700'],
    ][$a->status];
@endphp
<main class="w-full max-w-md bg-white rounded-3xl shadow-xl shadow-slate-900/5 border border-slate-200 overflow-hidden">
    <div class="p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $s['business_name'] }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight">Your booking</h1>
        <span class="mt-3 inline-block px-2.5 py-1 rounded-full text-xs font-bold {{ $state[1] }}">{{ $state[0] }}</span>

        @if(session('cancelled'))
            <p class="mt-4 p-3 rounded-xl bg-slate-50 text-sm text-slate-600">Your booking has been cancelled and {{ $s['business_name'] }} has been told. We have sent you a confirmation email.</p>
        @endif

        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Service</dt><dd class="font-semibold text-right">{{ $a->service_name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Date</dt><dd class="font-semibold text-right">{{ $start->format('l j F Y') }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Time</dt><dd class="font-semibold text-right">{{ $start->format('g:i A') }} – {{ $a->ends_at->copy()->setTimezone($s['timezone'])->format('g:i A') }}<br><span class="font-normal text-slate-500">{{ $s['timezone'] }}</span></dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="font-semibold text-right">{{ $a->name }}</dd></div>
        </dl>

        @if($canCancel)
        <form method="POST" action="{{ route('appointments.cancel', $a->token) }}" class="mt-8" onsubmit="return confirm('Cancel this booking?')">
            @csrf
            <button class="w-full py-3 rounded-xl border border-rose-200 text-rose-700 font-semibold hover:bg-rose-50 transition">Cancel booking</button>
        </form>
        <p class="mt-3 text-xs text-center text-slate-500">Need a different time? Cancel here and book again from the chat on our website.</p>
        @endif
    </div>
</main>
</body>
</html>
