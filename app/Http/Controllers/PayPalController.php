<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** PayPal subscription checkout, return page and webhook. */
class PayPalController extends Controller
{
    public function checkout(Request $request, PayPalService $pp)
    {
        $d = $request->validate(['plan_id' => 'required|exists:plans,id', 'billing_cycle' => 'required|in:monthly,yearly']);
        $plan = Plan::active()->findOrFail($d['plan_id']);
        $ppPlan = $d['billing_cycle'] === 'yearly' ? $plan->paypal_yearly_id : $plan->paypal_monthly_id;
        abort_unless(PayPalService::ready() && $ppPlan, 404);

        try {
            [, $approve] = $pp->createSubscription($ppPlan, $request->user()->id, $request->user()->email,
                route('billing.paypal.return', ['plan' => $plan->id, 'cycle' => $d['billing_cycle']]), route('settings.billing'));

            return redirect()->away($approve);
        } catch (\Throwable $e) {
            Log::error('PayPal checkout failed: ' . $e->getMessage());

            return back()->with('error', 'PayPal could not start. Please try another payment method.');
        }
    }

    public function credits(Request $request, PayPalService $pp)
    {
        $pack = config('plans.credit_packs.' . $request->input('pack'));
        abort_unless(PayPalService::ready() && $pack, 404);
        try {
            [, $approve] = $pp->createOrder(number_format($pack[1], 2, '.', ''), number_format($pack[0]) . ' AI reply credits', $request->user()->id,
                route('billing.paypal.credits.return', ['pack' => $request->input('pack')]), route('settings.billing'));

            return redirect()->away($approve);
        } catch (\Throwable $e) {
            Log::error('PayPal credits failed: ' . $e->getMessage());

            return back()->with('error', 'PayPal could not start. Please try another payment method.');
        }
    }

    /** Capture with PayPal, then check payer, amount and that the order was not used before. */
    public function creditsReturned(Request $request, PayPalService $pp)
    {
        $pack = config('plans.credit_packs.' . $request->get('pack'));
        $id = (string) $request->get('token');
        try {
            $o = $pack && $id ? $pp->captureOrder($id) : [];
        } catch (\Throwable) {
            $o = [];
        }
        $cap = $o['purchase_units'][0]['payments']['captures'][0] ?? [];
        $ok = ($o['status'] ?? '') === 'COMPLETED' && (int) ($cap['custom_id'] ?? $o['purchase_units'][0]['custom_id'] ?? 0) === $request->user()->id
            && (float) ($cap['amount']['value'] ?? 0) >= $pack[1];
        if (! $ok) return redirect()->route('settings.billing')->with('error', 'We could not confirm the PayPal payment.');
        if (\Illuminate\Support\Facades\Cache::add('pfx:pp-credits:' . $id, 1, now()->addDays(30))) $request->user()->increment('ai_credits', $pack[0]);

        return redirect()->route('settings.billing')->with('success', number_format($pack[0]) . ' AI credits added.');
    }

    /** Back from PayPal: confirm with PayPal before activating (never trust the query string alone). */
    public function returned(Request $request, PayPalService $pp)
    {
        $id = (string) $request->get('subscription_id');
        try {
            $sub = $id ? $pp->subscription($id) : [];
        } catch (\Throwable $e) {
            $sub = [];
        }
        if (! in_array($sub['status'] ?? '', ['ACTIVE', 'APPROVED'], true) || (int) ($sub['custom_id'] ?? 0) !== $request->user()->id) {
            return redirect()->route('settings.billing')->with('error', 'We could not confirm the PayPal subscription yet. If you completed payment it will activate shortly.');
        }
        $this->activate($request->user(), $id, (int) $request->get('plan'), $request->get('cycle') === 'yearly' ? 'yearly' : 'monthly');

        return redirect()->route('settings.billing', ['paid' => 1]);
    }

    public function webhook(Request $request, PayPalService $pp)
    {
        if (! $pp->verifyWebhook($request->headers->all(), $request->getContent())) {
            Log::warning('PayPal webhook rejected (signature not verified)');
            return response()->json(['error' => 'unverified'], 400);
        }
        $type = $request->input('event_type');
        $res = (array) $request->input('resource', []);
        $subId = $res['id'] ?? ($res['billing_agreement_id'] ?? null);
        $sub = $subId ? Subscription::where('paypal_subscription_id', $subId)->first() : null;

        match (true) {
            in_array($type, ['BILLING.SUBSCRIPTION.ACTIVATED', 'BILLING.SUBSCRIPTION.RE-ACTIVATED'], true) && ($user = User::find((int) ($res['custom_id'] ?? 0))) !== null
                => $this->activate($user, $subId, (int) Plan::where('paypal_monthly_id', $res['plan_id'] ?? '')->orWhere('paypal_yearly_id', $res['plan_id'] ?? '')->value('id'),
                    Plan::where('paypal_yearly_id', $res['plan_id'] ?? '')->exists() ? 'yearly' : 'monthly'),
            in_array($type, ['BILLING.SUBSCRIPTION.CANCELLED', 'BILLING.SUBSCRIPTION.EXPIRED'], true) && $sub
                => $sub->update(['status' => 'canceled', 'canceled_at' => now(), 'ends_at' => $sub->current_period_end]),
            $type === 'BILLING.SUBSCRIPTION.SUSPENDED' && $sub => $sub->update(['status' => 'past_due']),
            $type === 'PAYMENT.SALE.COMPLETED' && $sub
                => $sub->update(['status' => 'active', 'current_period_end' => $sub->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth()]),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function activate(User $user, string $subId, int $planId, string $cycle): void
    {
        if (! $planId) return;
        Subscription::updateOrCreate(['user_id' => $user->id], [
            'plan_id' => $planId, 'billing_cycle' => $cycle, 'status' => 'active', 'paypal_subscription_id' => $subId,
            'current_period_start' => now(), 'current_period_end' => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            'canceled_at' => null, 'ends_at' => null, 'trial_ends_at' => null,
        ]);
    }
}
