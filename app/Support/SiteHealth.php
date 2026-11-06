<?php

namespace App\Support;

use App\Http\Controllers\BillingController;
use App\Services\MultiProviderAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Automated go-live checks shown on Admin > System. Each: [label, ok|warn|fail, detail]. */
class SiteHealth
{
    public static function checks(): array
    {
        $c = [];

        try {
            DB::select('select 1');
            $c[] = ['Database', 'ok', 'Connected'];
        } catch (\Throwable $e) {
            $c[] = ['Database', 'fail', 'Not reachable'];
        }

        $p = MultiProviderAiService::platformProvider();
        $c[] = ['AI provider', filled(config("ai.{$p}.key")) ? 'ok' : 'fail', filled(config("ai.{$p}.key")) ? ucfirst($p) . ' key set' : 'No AI key: add one in Settings > AI'];
        $c[] = ['Knowledge search', filled(config('ai.openai.key')) ? 'ok' : 'warn', filled(config('ai.openai.key')) ? 'Semantic search (OpenAI embeddings)' : 'Keyword search (add an OpenAI key for semantic search)'];

        $mailer = config('mail.default');
        $c[] = ['Email', in_array($mailer, ['log', 'array', null], true) ? 'fail' : 'ok', in_array($mailer, ['log', 'array', null], true) ? "Mail driver is \"{$mailer}\": emails are not sent" : "Sending via {$mailer}"];

        $beat = Cache::get('pfx:scheduler_heartbeat');
        $c[] = ['Scheduler (cron)', $beat && now()->diffInMinutes($beat) < 5 ? 'ok' : 'fail', $beat ? 'Last run ' . \Carbon\Carbon::parse($beat)->diffForHumans() : 'Never ran: add the cron entry "php artisan schedule:run" every minute'];

        try {
            $stuck = config('queue.default') === 'sync' ? 0 : DB::table('jobs')->where('created_at', '<', now()->subMinutes(10)->timestamp)->count();
            $failed = DB::table('failed_jobs')->where('failed_at', '>', now()->subDay())->count();
            $c[] = ['Queue worker', $stuck ? 'fail' : 'ok', $stuck ? "{$stuck} job(s) waiting over 10 minutes: is the queue worker running?" : (config('queue.default') === 'sync' ? 'Runs inline (sync)' : 'Processing')];
            $c[] = ['Failed jobs (24h)', $failed ? 'warn' : 'ok', $failed ? "{$failed} failed: see below" : 'None'];
        } catch (\Throwable) {
            $c[] = ['Queue worker', 'warn', 'Queue tables not found'];
        }

        $c[] = ['Payments', BillingController::stripeReady() || BillingController::bankReady() ? 'ok' : 'warn', BillingController::stripeReady() ? 'Stripe connected' . (BillingController::bankReady() ? ' + bank transfer' : '') : (BillingController::bankReady() ? 'Bank transfer only' : 'No payment method: customers cannot upgrade')];
        if (BillingController::stripeReady()) {
            $c[] = ['Stripe webhook', filled(config('services.stripe.webhook_secret')) ? 'ok' : 'fail', filled(config('services.stripe.webhook_secret')) ? 'Signing secret set' : 'Add the webhook signing secret, or paid plans never activate'];
        }

        $c[] = ['Storage', is_writable(storage_path('app')) && file_exists(public_path('storage')) ? 'ok' : 'fail', file_exists(public_path('storage')) ? 'Writable and linked' : 'Run "php artisan storage:link"'];
        $c[] = ['Debug mode', config('app.debug') && app()->environment('production') ? 'fail' : 'ok', config('app.debug') ? 'APP_DEBUG is on' . (app()->environment('production') ? ': turn off in production' : ' (fine outside production)') : 'Off'];
        $c[] = ['HTTPS', str_starts_with((string) config('app.url'), 'https://') || ! app()->environment('production') ? 'ok' : 'warn', config('app.url')];

        return $c;
    }
}
