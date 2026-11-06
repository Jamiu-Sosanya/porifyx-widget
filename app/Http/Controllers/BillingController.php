<?php

namespace App\Http\Controllers;

use App\Models\ManualPayment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Plan changes. Paid plans are only activated after payment: Stripe Checkout (activated by the
 * signed webhook) or a bank transfer the platform admin approves. Free plans switch immediately.
 */
class BillingController extends Controller
{
    public static function stripeReady(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public static function bankReady(): bool
    {
        return filter_var(SystemSetting::get('bank_transfer_enabled', false), FILTER_VALIDATE_BOOLEAN) && filled(SystemSetting::get('bank_transfer_details'));
    }

    public function change(Request $request)
    {
        $d = $request->validate(['plan_id' => 'required|exists:plans,id', 'billing_cycle' => 'required|in:monthly,yearly', 'method' => 'nullable|in:card,bank']);
        $user = $request->user();
        $plan = Plan::active()->findOrFail($d['plan_id']);
        $price = (float) ($d['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly);

        if ($price <= 0) {
            Subscription::updateOrCreate(['user_id' => $user->id], [
                'plan_id' => $plan->id, 'billing_cycle' => $d['billing_cycle'], 'status' => 'active',
                'current_period_start' => now(), 'current_period_end' => null, 'canceled_at' => null, 'ends_at' => null,
            ]);

            return back()->with('success', "You are now on {$plan->name}.");
        }

        $method = $d['method'] ?? (self::stripeReady() ? 'card' : 'bank');
        if ($method === 'bank' && self::bankReady()) {
            return redirect()->route('billing.bank', ['plan' => $plan->id, 'cycle' => $d['billing_cycle']]);
        }
        if ($method === 'card' && self::stripeReady()) {
            return $this->stripeCheckout($user, $plan, $d['billing_cycle']);
        }

        return back()->with('error', 'Online payment is not set up yet. Please contact us to upgrade.');
    }

    private function stripeCheckout($user, Plan $plan, string $cycle)
    {
        $priceId = $cycle === 'yearly' ? $plan->stripe_yearly_id : $plan->stripe_monthly_id;
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $customer = $user->subscription?->stripe_customer_id;
            $session = $stripe->checkout->sessions->create(array_filter([
                'mode' => 'subscription',
                'line_items' => [['price' => $priceId, 'quantity' => 1]],
                'customer' => $customer ?: null,
                'customer_email' => $customer ? null : $user->email,
                'client_reference_id' => (string) $user->id,
                'metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id, 'billing_cycle' => $cycle],
                'subscription_data' => ['metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id]],
                'allow_promotion_codes' => true,
                'success_url' => route('settings.billing') . '?paid=1',
                'cancel_url' => route('settings.billing'),
            ]));

            return redirect()->away($session->url);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout failed: ' . $e->getMessage(), ['plan' => $plan->id]);

            return back()->with('error', 'Card payment could not start. ' . (self::bankReady() ? 'You can pay by bank transfer instead.' : 'Please try again later.'));
        }
    }

    /** Stripe's hosted page to update the card, download invoices or cancel. */
    public function portal(Request $request)
    {
        $customer = $request->user()->subscription?->stripe_customer_id;
        abort_unless(self::stripeReady() && $customer, 404);
        try {
            $s = (new \Stripe\StripeClient(config('services.stripe.secret')))->billingPortal->sessions->create(['customer' => $customer, 'return_url' => route('settings.billing')]);

            return redirect()->away($s->url);
        } catch (\Throwable $e) {
            Log::error('Stripe portal failed: ' . $e->getMessage());

            return back()->with('error', 'The billing portal is not available right now.');
        }
    }

    public function bank(Request $request)
    {
        abort_unless(self::bankReady(), 404);
        $plan = Plan::active()->findOrFail($request->integer('plan'));
        $cycle = $request->get('cycle') === 'yearly' ? 'yearly' : 'monthly';

        return view('settings.bank-transfer', [
            'plan' => $plan, 'cycle' => $cycle,
            'amount' => $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly,
            'details' => SystemSetting::get('bank_transfer_details'),
            'suggested' => 'PFX-' . $request->user()->id . '-' . strtoupper(substr($plan->slug, 0, 3)),
        ]);
    }

    public function bankSubmit(Request $request)
    {
        abort_unless(self::bankReady(), 404);
        $d = $request->validate(['plan_id' => 'required|exists:plans,id', 'billing_cycle' => 'required|in:monthly,yearly', 'reference' => 'required|string|max:120']);
        $plan = Plan::active()->findOrFail($d['plan_id']);
        $user = $request->user();
        if (ManualPayment::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return redirect()->route('settings.billing')->with('error', 'You already have a bank payment waiting for approval.');
        }
        ManualPayment::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'billing_cycle' => $d['billing_cycle'], 'reference' => $d['reference'],
            'amount' => $d['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly,
        ]);

        return redirect()->route('settings.billing')->with('success', 'Thanks! We will activate ' . $plan->name . ' as soon as your transfer arrives (usually within 1 working day).');
    }
}
