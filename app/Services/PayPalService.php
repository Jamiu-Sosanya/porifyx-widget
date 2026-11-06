<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** PayPal Subscriptions (REST v1). Credentials come from Admin > Settings > Payment. */
class PayPalService
{
    public static function ready(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    private function base(): string
    {
        return config('services.paypal.mode') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    private function http()
    {
        $token = Cache::remember('paypal:token:' . md5(config('services.paypal.client_id') . config('services.paypal.mode')), 3000, function () {
            $r = Http::asForm()->timeout(15)->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
                ->post($this->base() . '/v1/oauth2/token', ['grant_type' => 'client_credentials'])->throw();

            return $r->json('access_token');
        });

        return Http::withToken($token)->acceptJson()->timeout(20);
    }

    /** Returns [subscription id, approval URL]. */
    public function createSubscription(string $planId, int $userId, string $email, string $returnUrl, string $cancelUrl): array
    {
        $r = $this->http()->post($this->base() . '/v1/billing/subscriptions', [
            'plan_id' => $planId,
            'custom_id' => (string) $userId,
            'subscriber' => ['email_address' => $email],
            'application_context' => ['brand_name' => config('branding.name'), 'user_action' => 'SUBSCRIBE_NOW', 'return_url' => $returnUrl, 'cancel_url' => $cancelUrl],
        ])->throw();
        $approve = collect($r->json('links'))->firstWhere('rel', 'approve')['href'] ?? null;

        return [$r->json('id'), $approve];
    }

    public function subscription(string $id): array
    {
        return $this->http()->get($this->base() . '/v1/billing/subscriptions/' . rawurlencode($id))->throw()->json();
    }

    public function cancel(string $id, string $reason = 'Cancelled by customer'): void
    {
        $this->http()->post($this->base() . '/v1/billing/subscriptions/' . rawurlencode($id) . '/cancel', ['reason' => $reason]);
    }

    /** Ask PayPal whether a webhook really came from PayPal. */
    public function verifyWebhook(array $headers, string $body): bool
    {
        $id = config('services.paypal.webhook_id');
        if (blank($id)) return false;
        $h = fn ($k) => $headers[strtolower($k)][0] ?? null;
        try {
            $r = $this->http()->post($this->base() . '/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $h('paypal-auth-algo'), 'cert_url' => $h('paypal-cert-url'), 'transmission_id' => $h('paypal-transmission-id'),
                'transmission_sig' => $h('paypal-transmission-sig'), 'transmission_time' => $h('paypal-transmission-time'),
                'webhook_id' => $id, 'webhook_event' => json_decode($body, true),
            ]);

            return $r->json('verification_status') === 'SUCCESS';
        } catch (\Throwable) {
            return false;
        }
    }
}
