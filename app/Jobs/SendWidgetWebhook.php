<?php

namespace App\Jobs;

use App\Models\WebhookLog;
use App\Models\Widget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWidgetWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int    $widgetId,
        public string $event,
        public array  $payload = [],
        public ?int   $logId = null,
    ) {}

    public function handle(): void
    {
        $widget = Widget::query()->find($this->widgetId);

        if (! $widget || ! $widget->webhook_enabled || ! $widget->webhook_url) {
            return;
        }

        $body = json_encode([
            'event'       => $this->event,
            'widget_uuid' => $widget->uuid,
            'timestamp'   => now()->toIso8601String(),
            'data'        => $this->payload,
        ], JSON_THROW_ON_ERROR);

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent'   => 'porifyxwidgetPro-Webhook/1.0',
            'X-PFX-Event'  => $this->event,
            'X-PFX-Widget' => $widget->uuid,
        ];

        if ($widget->webhook_secret) {
            $headers['X-PFX-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $widget->webhook_secret);
        }

        // Create or retrieve log entry
        $log = $this->logId
            ? WebhookLog::find($this->logId)
            : WebhookLog::create([
                'widget_id' => $widget->id,
                'event'     => $this->event,
                'url'       => $widget->webhook_url,
                'payload'   => $this->payload,
                'status'    => 'pending',
                'attempt'   => $this->attempts(),
            ]);

        $startedAt = microtime(true);

        try {
            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($widget->webhook_url);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $success    = $response->successful();

            $log?->update([
                'response_status'  => $response->status(),
                'response_body'    => mb_substr($response->body(), 0, 2000),
                'response_time_ms' => $durationMs,
                'status'           => $success ? 'success' : 'failed',
                'attempt'          => $this->attempts(),
                'delivered_at'     => $success ? now() : null,
                'error_message'    => $success ? null : "HTTP {$response->status()}",
            ]);

            if (! $success) {
                Log::warning('Widget webhook returned non-2xx', [
                    'widget_id' => $widget->id,
                    'event'     => $this->event,
                    'status'    => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $log?->update([
                'response_time_ms' => $durationMs,
                'status'           => 'failed',
                'attempt'          => $this->attempts(),
                'error_message'    => $e->getMessage(),
            ]);

            Log::warning('Widget webhook delivery failed', [
                'widget_id' => $widget->id,
                'event'     => $this->event,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        if ($this->logId) {
            WebhookLog::where('id', $this->logId)->update([
                'status'        => 'failed',
                'error_message' => 'Job failed after max retries: ' . $exception->getMessage(),
            ]);
        }
    }
}
