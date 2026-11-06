@extends('layouts.settings')
@section('title', 'Billing')
@section('page-title', 'Settings')
@section('settings')
<div class="w-full">

    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400">{{ session('error') }}</div>
    @endif
    @php
        $pfxStripe = \App\Http\Controllers\BillingController::stripeReady();
        $pfxBank = \App\Http\Controllers\BillingController::bankReady();
        $pfxPending = \App\Models\ManualPayment::where('user_id', auth()->id())->where('status', 'pending')->with('plan')->first();
    @endphp
    @if(request('paid'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400">Payment received. Your plan updates within a minute; refresh if it has not changed yet.</div>
    @endif
    @if($pfxPending)
    <div class="mb-5 p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl text-sm text-amber-300">Bank transfer for <strong>{{ $pfxPending->label() }}</strong> (reference {{ $pfxPending->reference }}) is waiting for confirmation. We activate it as soon as the money arrives.</div>
    @endif
    @if($pfxStripe && auth()->user()->subscription?->stripe_customer_id)
    <div class="mb-5 flex justify-end"><a href="{{ route('billing.portal') }}" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-sm font-semibold text-white">Manage card &amp; invoices</a></div>
    @endif

    {{-- Current Plan Card --}}
    @if($subscription && $plan)
    @php
        $isTrialing  = $subscription->isTrialing();
        $isCancelled = $subscription->status === 'canceled';
        $periodEnd   = $subscription->current_period_end;
    @endphp
    <div class="bg-linear-to-br from-brand-500/10 via-accent-500/5 to-transparent border border-brand-500/15 rounded-2xl p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="text-lg font-bold text-white">{{ $plan->name }}</h3>
                    @if($isTrialing)
                    <span class="px-2.5 py-0.5 text-[10px] font-bold bg-amber-500/15 text-amber-400 rounded-full uppercase tracking-wider">Trial</span>
                    @elseif($isCancelled)
                    <span class="px-2.5 py-0.5 text-[10px] font-bold bg-rose-500/15 text-rose-400 rounded-full uppercase tracking-wider">Cancelled</span>
                    @else
                    <span class="px-2.5 py-0.5 text-[10px] font-bold bg-emerald-500/15 text-emerald-400 rounded-full uppercase tracking-wider">Active</span>
                    @endif
                </div>
                @if($isTrialing)
                <p class="text-sm text-amber-300/80">Trial ends {{ $subscription->trial_ends_at->format('M d, Y') }}</p>
                @elseif($isCancelled)
                <p class="text-sm text-rose-300/80">Access until {{ $periodEnd?->format('M d, Y') }}</p>
                @else
                <p class="text-sm text-gray-400">{{ ucfirst($subscription->billing_cycle) }} billing · renews {{ $periodEnd?->format('M d, Y') }}</p>
                @endif
            </div>
            <div class="text-left sm:text-right">
                <p class="text-3xl font-bold text-white">{{ $plan->getFormattedPrice($subscription->billing_cycle ?? 'monthly') }}</p>
                <p class="text-sm text-gray-400 mt-0.5">per {{ $subscription->billing_cycle === 'yearly' ? 'year' : 'month' }}</p>
                @if(!$isCancelled)
                <form method="POST" action="{{ route('settings.billing.cancel') }}" class="mt-3"
                      onsubmit="return confirm('Cancel your subscription? You\'ll keep access until the end of the billing period.')">
                    @csrf
                    <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 underline transition">Cancel subscription</button>
                </form>
                @endif
            </div>
        </div>

        {{-- Usage bars --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-5 border-t border-white/[0.07]">
            @foreach($usage as $key => $u)
            @php
                $isUnlimited = $u['limit'] === -1;
                $pct = $isUnlimited ? 8 : ($u['limit'] > 0 ? min(100, round($u['used'] / $u['limit'] * 100)) : 0);
                $barColor = $pct >= 90 ? 'bg-rose-500' : ($pct >= 70 ? 'bg-amber-500' : 'bg-brand-500');
            @endphp
            <div>
                <div class="flex justify-between text-xs mb-1.5">
                    <span class="text-gray-400 capitalize font-medium">{{ $key }}</span>
                    <span class="text-white font-semibold">
                        {{ number_format($u['used']) }} / {{ $isUnlimited ? '∞' : number_format($u['limit']) }}
                    </span>
                </div>
                <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full {{ $barColor }} rounded-full transition-all" style="width: {{ $isUnlimited ? 8 : $pct }}%"></div>
                </div>
                @if(!$isUnlimited && $pct >= 90)
                <p class="text-[10px] text-rose-400 mt-1">Limit nearly reached</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- AI credits --}}
    @php $pfxCredits = (int) auth()->user()->ai_credits; @endphp
    <div class="mb-8 rounded-2xl border border-white/[0.07] bg-gray-900/50 p-5 flex flex-col lg:flex-row lg:items-center gap-5">
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">AI reply credits <span class="ml-2 px-2 py-0.5 rounded-full bg-brand-500/15 text-brand-300 text-xs">{{ number_format($pfxCredits) }} left</span></p>
            <p class="mt-1 text-xs text-gray-400">Your plan's AI replies are included every month. Credits are only used after that, never expire, and keep your assistant answering at busy times. No surprise upgrades.</p>
        </div>
        @php $pfxPP = \App\Services\PayPalService::ready(); @endphp
        @if($pfxStripe || $pfxPP || $pfxBank)
        <div x-data="{ pack: 'm' }" class="space-y-2">
            <div class="flex flex-wrap gap-2">
                @foreach(config('plans.credit_packs') as $k => [$n, $price])
                <button type="button" @click="pack = '{{ $k }}'" :class="pack === '{{ $k }}' ? 'border-brand-500 bg-brand-500/15' : 'border-white/10 bg-white/5'" class="px-4 py-2 rounded-xl border text-sm text-white"><strong>{{ number_format($n) }}</strong> replies · ${{ $price }}</button>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                @if($pfxStripe)<form method="POST" action="{{ route('billing.credits') }}">@csrf<input type="hidden" name="pack" :value="pack"><button class="px-3 py-2 rounded-lg bg-brand-500 text-white">Pay by card</button></form>@endif
                @if($pfxPP)<form method="POST" action="{{ route('billing.paypal.credits') }}">@csrf<input type="hidden" name="pack" :value="pack"><button class="px-3 py-2 rounded-lg bg-[#ffc439] text-gray-900">PayPal</button></form>@endif
                @if($pfxBank)<a :href="'{{ route('billing.bank') }}?pack=' + pack" class="px-3 py-2 rounded-lg bg-white/10 text-white">Bank transfer</a>@endif
            </div>
        </div>
        @else
        <p class="text-xs text-gray-500">Online payments are not set up yet. Contact us to add credits.</p>
        @endif
    </div>

    {{-- Plan selector --}}
    <div class="mb-8" x-data="{ cycle: '{{ $subscription?->billing_cycle ?? 'monthly' }}' }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-white">{{ $plan ? 'Change Plan' : 'Choose a Plan' }}</h3>
            <div class="inline-flex bg-gray-900/60 border border-white/5 rounded-xl p-1">
                <button @click="cycle = 'monthly'"
                        :class="cycle === 'monthly' ? 'bg-white/10 text-white shadow' : 'text-gray-400 hover:text-white'"
                        class="px-4 py-1.5 text-xs font-medium rounded-lg transition">Monthly</button>
                <button @click="cycle = 'yearly'"
                        :class="cycle === 'yearly' ? 'bg-white/10 text-white shadow' : 'text-gray-400 hover:text-white'"
                        class="px-4 py-1.5 text-xs font-medium rounded-lg transition">
                    Yearly <span class="text-emerald-400 font-semibold">–20%</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-4">
            @foreach($plans as $p)
            @php $isCurrent = $plan && $plan->id === $p->id; @endphp
            <div class="relative bg-gray-900/50 border {{ $p->is_popular ? 'border-brand-500/30 ring-1 ring-brand-500/15' : 'border-white/5' }} rounded-2xl p-5 flex flex-col">
                @if($p->is_popular)
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-brand-600 text-white text-[10px] font-bold rounded-full uppercase tracking-wider shadow-lg">Most Popular</span>
                @endif
                <div class="mb-4">
                    <h4 class="text-base font-bold text-white">{{ $p->name }}</h4>
                    <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $p->description }}</p>
                </div>
                <div class="mb-5">
                    <div x-show="cycle === 'monthly'" class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-white">${{ number_format($p->price_monthly) }}</span>
                        <span class="text-sm text-gray-400">/mo</span>
                    </div>
                    <div x-show="cycle === 'yearly'" class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-white">${{ number_format($p->price_yearly) }}</span>
                        <span class="text-sm text-gray-400">/yr</span>
                    </div>
                </div>
                <ul class="space-y-2 text-xs text-gray-400 mb-6 flex-1">
                    @foreach(array_map(fn ($l) => [$l, true], $p->featureList()) as [$feature, $included])
                    @if($included)
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        {{ $feature }}
                    </li>
                    @endif
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('settings.billing.changePlan') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $p->id }}">
                    <input type="hidden" name="billing_cycle" :value="cycle">
                    <button type="submit"
                            class="w-full py-2.5 text-sm font-semibold rounded-xl transition
                                {{ $isCurrent ? 'bg-white/5 text-gray-500 cursor-default' : ($p->is_popular ? 'bg-brand-600 hover:bg-brand-500 text-white shadow-lg shadow-brand-500/20' : 'bg-white/5 hover:bg-white/10 text-white') }}"
                            {{ $isCurrent ? 'disabled' : '' }}>
                        {{ $isCurrent ? 'Current Plan' : ($plan ? 'Switch to ' . $p->name : 'Get Started') }}
                    </button>
                </form>
                @if(! $isCurrent && \App\Services\PayPalService::ready() && ($p->paypal_monthly_id || $p->paypal_yearly_id))
                <form method="POST" action="{{ route('billing.paypal') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $p->id }}">
                    <input type="hidden" name="billing_cycle" :value="cycle">
                    <button class="w-full py-2 rounded-xl bg-[#ffc439] hover:bg-[#f2b92f] text-[#003087] text-sm font-bold">Pay with PayPal</button>
                </form>
                @endif
                <form method="POST" action="{{ route('settings.billing.changePlan') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $p->id }}">
                    <input type="hidden" name="billing_cycle" :value="cycle">
                    @if(! $isCurrent && $p->price_monthly > 0 && $pfxBank && $pfxStripe)
                    <button type="submit" name="method" value="bank" class="mt-2 w-full text-xs text-gray-400 hover:text-white">or pay by bank transfer</button>
                    @endif
                </form>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Invoice History --}}
    @if(isset($invoices) && $invoices->isNotEmpty())
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-white">Invoice History</h3>
            <span class="text-xs text-gray-500">{{ $invoices->count() }} invoice{{ $invoices->count() !== 1 ? 's' : '' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Invoice</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Description</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/4">
                    @foreach($invoices as $invoice)
                    <tr class="hover:bg-white/1.5 transition">
                        <td class="px-6 py-3.5 font-mono text-xs text-gray-500">
                            {{ $invoice->stripe_invoice_id ? substr($invoice->stripe_invoice_id, 0, 16) . '…' : 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-4 py-3.5 text-gray-300">{{ $invoice->description ?? 'Subscription' }}</td>
                        <td class="px-4 py-3.5 text-center font-semibold text-white">
                            ${{ number_format($invoice->amount / 100, 2) }}
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full
                                {{ $invoice->status === 'paid' ? 'bg-emerald-500/10 text-emerald-400' : ($invoice->status === 'open' ? 'bg-amber-500/10 text-amber-400' : 'bg-rose-500/10 text-rose-400') }}">
                                {{ ucfirst($invoice->status ?? 'paid') }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($invoice->created_at)->format('M d, Y') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @elseif(!$plan)
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl py-12 text-center">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
        </svg>
        <p class="text-gray-500 text-sm">No invoices yet.</p>
        <p class="text-gray-600 text-xs mt-1">Invoices appear here after your first payment.</p>
    </div>
    @endif
</div>
@endsection
