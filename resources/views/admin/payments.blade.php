@extends('layouts.admin')
@section('title', 'Payments')
@section('page-title', 'Bank transfer payments')
@section('content')
<div class="space-y-6">
    <section class="rounded-2xl border border-white/5 bg-gray-900/50">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-white">Waiting for approval</h3>
            <span class="text-xs text-gray-500">Approve only after the money is in your account.</span>
        </div>
        @forelse($pending as $p)
        <div class="px-5 py-4 border-b border-white/5 last:border-0 flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-white">{{ $p->user->name }} <span class="text-gray-500">· {{ $p->user->email }}</span></p>
                <p class="text-xs text-gray-400">{{ $p->plan->name }} ({{ $p->billing_cycle }}) · <strong class="text-white">${{ number_format($p->amount, 2) }}</strong> · reference <span class="font-mono text-brand-400">{{ $p->reference }}</span> · {{ $p->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.payments.approve', $p) }}" onsubmit="return confirm('Money received? This activates the plan.')">@csrf<button class="px-4 py-2 rounded-lg bg-emerald-500 text-white text-xs font-semibold">Approve</button></form>
                <form method="POST" action="{{ route('admin.payments.reject', $p) }}" class="flex gap-2">@csrf
                    <input name="note" maxlength="500" placeholder="Reason (optional)" class="bg-white/5 border border-white/10 rounded-lg px-2.5 py-1.5 text-xs text-white">
                    <button class="px-3 py-2 rounded-lg bg-rose-500/10 text-rose-400 text-xs font-semibold">Reject</button>
                </form>
            </div>
        </div>
        @empty
        <p class="px-5 py-10 text-center text-sm text-gray-500">No bank transfers waiting.</p>
        @endforelse
    </section>

    @if($recent->isNotEmpty())
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left"><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Plan</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Reviewed</th></tr></thead>
            <tbody>
                @foreach($recent as $p)
                <tr class="border-t border-white/5 text-gray-300">
                    <td class="px-5 py-3">{{ $p->user->email }}</td><td class="px-5 py-3">{{ $p->plan->name }}</td><td class="px-5 py-3">${{ number_format($p->amount, 2) }}</td>
                    <td class="px-5 py-3 font-mono text-xs">{{ $p->reference }}</td>
                    <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $p->status === 'approved' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-rose-500/15 text-rose-400' }}">{{ ucfirst($p->status) }}</span></td>
                    <td class="px-5 py-3 text-xs text-gray-500">{{ $p->reviewed_at?->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </section>
    @endif
</div>
@endsection
