@extends('layouts.app')
@section('title', 'Pay by bank transfer')
@section('page-title', 'Pay by bank transfer')
@section('content')
<div class="max-w-xl rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-5">
    <div>
        <p class="text-sm text-gray-400">@if($plan){{ $plan->name }} · {{ $cycle === 'yearly' ? 'yearly' : 'monthly' }}@else{{ number_format($credits) }} AI reply credits (never expire)@endif</p>
        <p class="text-3xl font-extrabold text-white">${{ number_format($amount, 2) }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Send the payment to</p>
        <div class="mt-2 rounded-xl bg-white/5 border border-white/10 p-4 text-sm text-gray-200 whitespace-pre-line break-words">{{ $details }}</div>
    </div>
    <form method="POST" action="{{ route('billing.bank.submit') }}" class="space-y-3">
        @csrf
        @if($plan)<input type="hidden" name="plan_id" value="{{ $plan->id }}">
        <input type="hidden" name="billing_cycle" value="{{ $cycle }}">@else<input type="hidden" name="pack" value="{{ $pack }}">@endif
        <label class="block text-sm text-gray-300">Payment reference you used
            <input name="reference" required maxlength="120" value="{{ old('reference', $suggested) }}" class="mt-1 w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white font-mono">
        </label>
        <p class="text-xs text-gray-500">Use this reference on the transfer so we can match it. Your plan starts as soon as we confirm the payment.</p>
        @error('reference')<p class="text-sm text-rose-400">{{ $message }}</p>@enderror
        <div class="flex flex-wrap gap-3">
            <button class="px-6 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold">I have sent the payment</button>
            <a href="{{ route('settings.billing') }}" class="px-4 py-3 rounded-xl bg-white/5 text-gray-300 text-sm font-semibold">Back</a>
        </div>
    </form>
</div>
@endsection
