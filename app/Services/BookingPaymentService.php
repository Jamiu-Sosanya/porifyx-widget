<?php

namespace App\Services;

use App\Models\Appointment;
use App\Support\Booking;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Takes payment for bookings with the business's own Stripe account (their secret key),
 * so money goes straight to them. Uses Stripe Checkout, then checks the session on return.
 */
class BookingPaymentService
{
    private function stripe(string $secret): PendingRequest
    {
        return Http::withToken($secret)->asForm()->acceptJson()->timeout(20)->baseUrl('https://api.stripe.com/v1');
    }

    public function checkoutUrl(Appointment $a, string $secret): string
    {
        $res = $this->stripe($secret)->post('/checkout/sessions', [
            'mode'                 => 'payment',
            'customer_email'       => $a->email,
            'client_reference_id'  => (string) $a->id,
            'metadata'             => ['appointment' => $a->id, 'source' => 'porifyx'],
            'expires_at'           => now()->addMinutes(Appointment::PAYMENT_HOLD + 1)->timestamp,
            'line_items'           => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => strtolower($a->currency),
                    'unit_amount'  => $a->amount,
                    'product_data' => ['name' => $a->service_name . ' – ' . $a->localStart()->format('D j M, g:i A')],
                ],
            ]],
            'success_url' => route('appointments.paid', $a->token) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $a->manageUrl(),
        ])->throw()->json();

        $a->update(['payment_ref' => $res['id']]);

        return $res['url'];
    }

    /** True when the Checkout session for this booking has been paid. Stores the payment intent for refunds. */
    public function confirm(Appointment $a, string $secret, string $sessionId): bool
    {
        if ($sessionId !== $a->payment_ref) {
            return false;
        }
        $session = $this->stripe($secret)->get('/checkout/sessions/' . $sessionId)->throw()->json();
        if (($session['payment_status'] ?? null) !== 'paid') {
            return false;
        }
        $a->update(['payment_status' => 'paid', 'payment_ref' => $session['payment_intent'] ?? $sessionId]);

        return true;
    }

    public function refund(Appointment $a): bool
    {
        $secret = Booking::stripeSecret(Booking::settings($a->widget));
        if (! $secret || $a->payment_status !== 'paid' || ! str_starts_with((string) $a->payment_ref, 'pi_')) {
            return false;
        }
        $this->stripe($secret)->post('/refunds', ['payment_intent' => $a->payment_ref])->throw();
        $a->update(['payment_status' => 'refunded']);

        return true;
    }
}
