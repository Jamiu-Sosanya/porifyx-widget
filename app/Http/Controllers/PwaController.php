<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PwaController extends Controller
{
    public function manifest()
    {
        return response()->json([
            'name'             => config('app.name', 'PoriFyx Widget'),
            'short_name'       => 'Chat Widget',
            'description'      => 'AI-powered chat widget for lead generation',
            'start_url'        => '/dashboard',
            'scope'            => '/',
            'display'          => 'standalone',
            'orientation'      => 'portrait-primary',
            'theme_color'      => '#ff6a2b',
            'background_color' => '#ffffff',
            'icons'            => [
                ['src' => '/images/icon-192x192.png',          'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/images/icon-512x512.png',          'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/images/icon-maskable-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => '/images/icon-maskable-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function serviceWorker()
    {
        return response()->file(public_path('service-worker.js'), [
            'Content-Type'  => 'application/javascript',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    public function health()
    {
        return response()->json([
            'status'    => 'online',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function subscribePush(Request $request)
    {
        $validated = $request->validate([
            'endpoint'      => 'required|url',
            'keys'          => 'required|array',
            'keys.p256dh'   => 'required|string',
            'keys.auth'     => 'required|string',
        ]);

        /** @var User $user */
        $user = auth()->user();

        try {
            $user->pushSubscriptions()->updateOrCreate(
                ['endpoint' => $validated['endpoint']],
                [
                    'p256dh'     => $validated['keys']['p256dh'],
                    'auth'       => $validated['keys']['auth'],
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json(['success' => true, 'message' => 'Push subscription registered']);
        } catch (\Exception $e) {
            Log::error('Failed to register push subscription', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to register push subscription'], 500);
        }
    }

    public function unsubscribePush(Request $request)
    {
        $validated = $request->validate(['endpoint' => 'required|url']);

        /** @var User $user */
        $user = auth()->user();

        try {
            $user->pushSubscriptions()->where('endpoint', $validated['endpoint'])->delete();

            return response()->json(['success' => true, 'message' => 'Push subscription removed']);
        } catch (\Exception $e) {
            Log::error('Failed to remove push subscription', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to remove push subscription'], 500);
        }
    }

    public function sendTestNotification(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return response()->json(['error' => 'No push subscriptions found. Enable notifications first.'], 400);
        }

        $results = $this->sendPushToSubscriptions(
            $subscriptions,
            'Test Notification',
            'Push notifications are working! Your dashboard will alert you for new leads and conversations.',
            '/dashboard'
        );

        return response()->json([
            'success' => true,
            'message' => "Test notification sent to {$results['sent']} device(s).",
            'details' => $results,
        ]);
    }

    public function trackInstall(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $user->update([
                'pwa_installed_at' => now(),
                'pwa_platform'     => $request->input('platform'),
            ]);

            return response()->json(['success' => true, 'message' => 'Installation tracked']);
        } catch (\Exception $e) {
            Log::error('Failed to track PWA installation', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to track installation'], 500);
        }
    }

    public function status(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json([
            'installed'    => ! is_null($user->pwa_installed_at),
            'installed_at' => $user->pwa_installed_at,
            'platform'     => $user->pwa_platform,
            'push_enabled' => $user->pushSubscriptions()->exists(),
            'push_count'   => $user->pushSubscriptions()->count(),
        ]);
    }

    // ─── Internal helpers ────────────────────────────────────────────────────

    /**
     * Send a push notification to a collection of PushSubscription models.
     */
    private function sendPushToSubscriptions(
        $subscriptions,
        string $title,
        string $message,
        string $url = '/dashboard',
        array  $extra = []
    ): array {
        $vapidPublic  = config('services.vapid.public_key');
        $vapidPrivate = config('services.vapid.private_key');
        $vapidSubject = config('services.vapid.subject', 'mailto:admin@localhost');

        if (empty($vapidPublic) || empty($vapidPrivate)) {
            Log::error('[PWA] VAPID keys not configured. Set VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY in .env');

            return ['sent' => 0, 'failed' => $subscriptions->count(), 'error' => 'VAPID keys missing'];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject'    => $vapidSubject,
                'publicKey'  => $vapidPublic,
                'privateKey' => $vapidPrivate,
            ],
        ]);

        $payload = json_encode(array_merge([
            'title'   => $title,
            'message' => $message,
            'icon'    => '/images/icon-192x192.png',
            'url'     => $url,
            'tag'     => 'notification-' . time(),
        ], $extra));

        $toDelete = [];
        $sent     = 0;
        $failed   = 0;

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'contentEncoding' => 'aesgcm',
                    'keys'            => [
                        'p256dh' => $sub->p256dh,
                        'auth'   => $sub->auth,
                    ],
                ]),
                $payload
            );
        }

        /** @var MessageSentReport $report */
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
                Log::info('[PWA] Push sent', ['endpoint' => substr($report->getEndpoint(), 0, 60)]);
            } else {
                $failed++;
                Log::warning('[PWA] Push failed', [
                    'endpoint' => substr($report->getEndpoint(), 0, 60),
                    'reason'   => $report->getReason(),
                ]);

                // 410 Gone / 404 Not Found = subscription expired; remove it
                if ($report->isSubscriptionExpired()) {
                    $toDelete[] = $report->getEndpoint();
                }
            }
        }

        if (! empty($toDelete)) {
            PushSubscription::whereIn('endpoint', $toDelete)->delete();
            Log::info('[PWA] Cleaned up expired subscriptions', ['count' => count($toDelete)]);
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Public method so other services (LeadCaptured, Escalated, etc.) can send pushes.
     */
    public static function notify(User $user, string $title, string $message, string $url = '/dashboard', array $extra = []): array
    {
        $instance      = app(self::class);
        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0];
        }

        return $instance->sendPushToSubscriptions($subscriptions, $title, $message, $url, $extra);
    }
}
