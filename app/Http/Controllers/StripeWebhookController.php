<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceEmail;
use App\Mail\PaymentFailed;
use App\Mail\SubscriptionCancelled;
use App\Mail\SubscriptionCreated;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');

        if ($secret) {
            try {
                $event = Webhook::constructEvent($payload, $sig, $secret);
            } catch (SignatureVerificationException $e) {
                Log::warning('Stripe webhook signature mismatch', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } else {
            // Without a signing secret anyone could post fake payment events.
            Log::error('Stripe webhook rejected: set the Stripe webhook signing secret in Admin > Settings > Payment.');
            return response()->json(['error' => 'Webhook signing secret not configured'], 400);
        }

        match ($event->type) {
            'checkout.session.completed'      => $this->onCheckoutCompleted($event),
            'customer.subscription.created'   => $this->onSubscriptionCreated($event),
            'customer.subscription.updated'   => $this->onSubscriptionUpdated($event),
            'customer.subscription.deleted'   => $this->onSubscriptionDeleted($event),
            'invoice.payment_succeeded'       => $this->onInvoicePaymentSucceeded($event),
            'invoice.payment_failed'          => $this->onInvoicePaymentFailed($event),
            default                           => null,
        };

        return response()->json(['received' => true]);
    }

    private function onSubscriptionCreated(Event $event): void
    {
        $stripeSub = $event->data->object;
        $user      = $this->findUserByStripeCustomer($stripeSub->customer);

        if (! $user) {
            return;
        }

        $priceId = $stripeSub->items->data[0]->price->id ?? null;
        $plan = $priceId
            ? Plan::where('stripe_monthly_id', $priceId)->orWhere('stripe_yearly_id', $priceId)->first()
            : null;

        $subscription = Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSub->id],
            [
                'user_id'              => $user->id,
                'plan_id'              => $plan?->id,
                'stripe_customer_id'   => $stripeSub->customer,
                'status'               => $stripeSub->status,
                'billing_cycle'        => ($stripeSub->items->data[0]->price->recurring->interval ?? 'month') === 'year' ? 'yearly' : 'monthly',
                'current_period_start' => now()->createFromTimestamp($stripeSub->current_period_start),
                'current_period_end'   => now()->createFromTimestamp($stripeSub->current_period_end),
                'trial_ends_at'        => $stripeSub->trial_end ? now()->createFromTimestamp($stripeSub->trial_end) : null,
            ]
        );

        if ($plan) {
            Mail::to($user->email)->queue(new SubscriptionCreated($user, $subscription, $plan));
        }
    }

    private function onSubscriptionUpdated(Event $event): void
    {
        $stripeSub    = $event->data->object;
        $subscription = Subscription::where('stripe_subscription_id', $stripeSub->id)->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status'               => $stripeSub->status,
            'current_period_start' => now()->createFromTimestamp($stripeSub->current_period_start),
            'current_period_end'   => now()->createFromTimestamp($stripeSub->current_period_end),
            'trial_ends_at'        => $stripeSub->trial_end ? now()->createFromTimestamp($stripeSub->trial_end) : null,
            'canceled_at'          => $stripeSub->canceled_at ? now()->createFromTimestamp($stripeSub->canceled_at) : null,
            'ends_at'              => $stripeSub->cancel_at ? now()->createFromTimestamp($stripeSub->cancel_at) : null,
        ]);
    }

    private function onSubscriptionDeleted(Event $event): void
    {
        $stripeSub    = $event->data->object;
        $subscription = Subscription::where('stripe_subscription_id', $stripeSub->id)->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status'     => 'canceled',
            'canceled_at'=> now(),
            'ends_at'    => now(),
        ]);

        $user = $subscription->user;
        if ($user && $subscription->plan) {
            Mail::to($user->email)->queue(new SubscriptionCancelled($user, $subscription->plan, $subscription->ends_at));
        }
    }

    private function onInvoicePaymentSucceeded(Event $event): void
    {
        $stripeInvoice = $event->data->object;
        $user          = $this->findUserByStripeCustomer($stripeInvoice->customer);

        if (! $user || $stripeInvoice->amount_paid === 0) {
            return;
        }

        $invoice = Invoice::updateOrCreate(
            ['stripe_invoice_id' => $stripeInvoice->id],
            [
                'user_id'        => $user->id,
                'invoice_number' => $stripeInvoice->number ?? ('INV-' . strtoupper(substr($stripeInvoice->id, -8))),
                'amount'         => $stripeInvoice->amount_paid / 100,
                'currency'       => strtoupper($stripeInvoice->currency),
                'status'         => 'paid',
                'description'    => $stripeInvoice->description ?? ('Subscription — ' . now()->format('M Y')),
                'paid_at'        => now()->createFromTimestamp($stripeInvoice->status_transitions->paid_at ?? now()->timestamp),
            ]
        );

        $subscription = Subscription::where('stripe_subscription_id', $stripeInvoice->subscription)->first();
        $plan         = $subscription?->plan;

        Mail::to($user->email)->queue(new InvoiceEmail($user, $invoice, $plan, $subscription));
    }

    private function onInvoicePaymentFailed(Event $event): void
    {
        $stripeInvoice = $event->data->object;
        $user          = $this->findUserByStripeCustomer($stripeInvoice->customer);

        if (! $user) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeInvoice->subscription)->first();

        if ($subscription) {
            $subscription->update(['status' => 'past_due']);
        }

        $amount        = $stripeInvoice->amount_due / 100;
        $failureReason = $stripeInvoice->last_finalization_error?->message ?? 'Card declined';
        $nextRetry     = $stripeInvoice->next_payment_attempt
            ? now()->createFromTimestamp($stripeInvoice->next_payment_attempt)
            : null;

        Mail::to($user->email)->queue(new PaymentFailed(
            user: $user,
            amount: $amount,
            failureReason: $failureReason,
            nextRetry: $nextRetry,
            plan: $subscription?->plan,
        ));
    }

    /** Checkout finished: remember which Stripe customer and subscription belong to the user. */
    private function onCheckoutCompleted(Event $event): void
    {
        $s = $event->data->object;
        $user = User::find((int) ($s->client_reference_id ?? $s->metadata->user_id ?? 0));
        if ($user && ($s->mode ?? '') === 'payment' && ($s->payment_status ?? '') === 'paid' && ($n = (int) ($s->metadata->credits ?? 0)) > 0) {
            // Idempotent: Stripe may resend the same event.
            if (\Illuminate\Support\Facades\Cache::add('pfx:credits:' . $s->id, 1, now()->addDays(30))) $user->increment('ai_credits', $n);

            return;
        }
        if (! $user || ($s->mode ?? '') !== 'subscription' || ($s->payment_status ?? 'paid') === 'unpaid') return;

        Subscription::updateOrCreate(['user_id' => $user->id], [
            'plan_id'                => (int) ($s->metadata->plan_id ?? $user->subscription?->plan_id),
            'billing_cycle'          => ($s->metadata->billing_cycle ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly',
            'stripe_customer_id'     => $s->customer,
            'stripe_subscription_id' => $s->subscription,
            'status'                 => 'active',
            'current_period_start'   => now(),
            'canceled_at'            => null,
            'ends_at'                => null,
            'trial_ends_at'          => null,
        ]);
    }

    private function findUserByStripeCustomer(string $customerId): ?User
    {
        return User::whereHas('subscription', fn ($q) => $q->where('stripe_customer_id', $customerId))->first()
            ?? Subscription::where('stripe_customer_id', $customerId)->first()?->user;
    }
}
