<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Your booking with {{ $s['business_name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f7fb] text-slate-900 antialiased grid place-items-center p-5">
@php
    $start = $a->localStart();
    $state = [
        'pending'   => ['Waiting for confirmation', 'bg-amber-100 text-amber-800'],
        'confirmed' => ['Confirmed', 'bg-emerald-100 text-emerald-800'],
        'completed' => ['Completed', 'bg-slate-100 text-slate-700'],
        'cancelled' => ['Cancelled', 'bg-rose-100 text-rose-800'],
        'no_show'   => ['Missed', 'bg-slate-100 text-slate-700'],
    ][$a->status];
    if ($a->payment_status === 'unpaid' && $a->status !== 'cancelled') {
        $state = ['Waiting for payment', 'bg-amber-100 text-amber-800'];
    }
@endphp
<main class="w-full max-w-md bg-white rounded-3xl shadow-xl shadow-slate-900/5 border border-slate-200 overflow-hidden"
      x-data="{
          moving: false, date: '', dates: [], slots: [], picked: '', loading: false,
          async open() {
              this.moving = true;
              const r = await fetch(@js(url('/api/widget/' . $a->widget->uuid . '/booking')), { headers: { Accept: 'application/json' } });
              this.dates = r.ok ? (await r.json()).dates : [];
              if (this.dates.length) this.pick(this.dates[0], 14);
          },
          async pick(d, skip = 0) {
              this.date = d; this.picked = ''; this.loading = true;
              const q = new URLSearchParams({ service: @js($a->service_id), date: d, ignore: @js($a->token) });
              @if($a->staff_id) q.set('staff', @js($a->staff_id)); @endif
              const r = await fetch(@js(url('/api/widget/' . $a->widget->uuid . '/booking/slots')) + '?' + q, { headers: { Accept: 'application/json' } });
              this.slots = r.ok ? (await r.json()).slots : [];
              this.loading = false;
              const next = this.dates[this.dates.indexOf(d) + 1];
              if (skip && !this.slots.length && next) this.pick(next, skip - 1);
          },
          label(d) { return new Date(d + 'T12:00:00').toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' }); },
      }">
    <div class="p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $s['business_name'] }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight">Your booking</h1>
        <p class="mt-1 text-sm text-slate-500">Booking number <span class="font-mono font-bold text-slate-900 tracking-wider">{{ $a->reference }}</span></p>
        <span class="mt-3 inline-block px-2.5 py-1 rounded-full text-xs font-bold {{ $state[1] }}">{{ $state[0] }}</span>

        @if(session('notice'))
            <p class="mt-4 p-3 rounded-xl bg-slate-50 text-sm text-slate-600">{{ session('notice') }}</p>
        @endif
        @if($errors->any())
            <p class="mt-4 p-3 rounded-xl bg-rose-50 text-sm text-rose-700">{{ $errors->first() }}</p>
        @endif

        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Service</dt><dd class="font-semibold text-right">{{ $a->service_name }}</dd></div>
            @if($a->staff_name)<div class="flex justify-between gap-4"><dt class="text-slate-500">With</dt><dd class="font-semibold text-right">{{ $a->staff_name }}</dd></div>@endif
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Date</dt><dd class="font-semibold text-right">{{ $start->format('l j F Y') }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Time</dt><dd class="font-semibold text-right">{{ $start->format('g:i A') }} – {{ $a->ends_at->copy()->setTimezone($s['timezone'])->format('g:i A') }}<br><span class="font-normal text-slate-500">{{ $s['timezone'] }}</span></dd></div>
            @if($a->amount)<div class="flex justify-between gap-4"><dt class="text-slate-500">Payment</dt><dd class="font-semibold text-right">{{ $a->priceLabel() }} · {{ ucfirst($a->payment_status) }}</dd></div>@endif
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="font-semibold text-right">{{ $a->name }}</dd></div>
        </dl>

        @if($payAgain)
            <a href="{{ route('appointments.pay', $a->token) }}" class="mt-8 block w-full py-3 rounded-xl bg-slate-900 text-white text-center font-semibold hover:bg-slate-800">Pay {{ $a->priceLabel() }} to confirm</a>
            <p class="mt-2 text-xs text-center text-slate-500">We hold this time for {{ \App\Models\Appointment::PAYMENT_HOLD }} minutes from booking.</p>
        @endif

        @if($canChange)
            <div class="mt-8 space-y-3">
                @if($canMove)
                <button type="button" x-show="!moving" @click="open()" class="w-full py-3 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 transition">Change time</button>
                <form x-show="moving" x-cloak method="POST" action="{{ route('appointments.reschedule.visitor', $a->token) }}" class="space-y-3">
                    @csrf
                    <div class="flex gap-1.5 overflow-x-auto pb-1">
                        <template x-for="d in dates" :key="d">
                            <button type="button" @click="pick(d)" class="shrink-0 px-3 py-2 rounded-xl text-xs font-semibold border" :class="d === date ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-700'" x-text="label(d)"></button>
                        </template>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5">
                        <template x-for="sl in slots" :key="sl.start">
                            <button type="button" @click="picked = sl.start" class="py-2 rounded-xl text-sm font-semibold border" :class="picked === sl.start ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-700'" x-text="sl.label"></button>
                        </template>
                        <p x-show="!loading && !slots.length" class="col-span-3 text-sm text-slate-500">No free times that day.</p>
                    </div>
                    <input type="hidden" name="start" :value="picked">
                    <button :disabled="!picked" class="w-full py-3 rounded-xl bg-slate-900 text-white font-semibold disabled:opacity-40">Move my booking</button>
                </form>
                @endif
                <form method="POST" action="{{ route('appointments.cancel', $a->token) }}" onsubmit="return confirm('Cancel this booking?')">
                    @csrf
                    <button class="w-full py-3 rounded-xl border border-rose-200 text-rose-700 font-semibold hover:bg-rose-50 transition">Cancel booking</button>
                </form>
                @if($a->payment_status === 'paid' && $s['payments']['refund_on_cancel'])
                    <p class="text-xs text-center text-slate-500">Cancelling refunds your payment to the card you used.</p>
                @endif
            </div>
        @elseif(in_array($a->status, \App\Models\Appointment::ACTIVE, true) && $a->payment_status !== 'unpaid')
            <p class="mt-8 text-xs text-center text-slate-500">This booking can no longer be changed online. Please contact {{ $s['business_name'] }} directly.</p>
        @endif
    </div>
</main>
</body>
</html>
