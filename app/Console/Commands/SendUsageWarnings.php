<?php

namespace App\Console\Commands;

use App\Mail\ApiUsageWarning;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendUsageWarnings extends Command
{
    protected $signature = 'app:usage-warnings
                            {--dry-run : List users who would be emailed without sending}';

    protected $description = 'Send usage warning emails when users hit 80% or 90% of their plan limits';

    // Thresholds that trigger an email (each fires at most once per billing period)
    private const THRESHOLDS = [80, 90];

    private const METRICS = [
        'messages' => 'AI Messages',
        'leads'    => 'Leads',
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $sent = 0;

        $subscriptions = Subscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->with(['user', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            $plan = $subscription->plan;

            if (! $user || ! $plan) continue;

            foreach (self::METRICS as $metric => $label) {
                $limit = match ($metric) {
                    'messages' => $plan->messages_per_month,
                    'leads'    => $plan->leads_per_month,
                    default    => -1,
                };

                if ($limit <= 0) continue; // unlimited or undefined

                $used    = $user->getUsageThisMonth($metric);
                $percent = (int) round(($used / $limit) * 100);

                foreach (self::THRESHOLDS as $threshold) {
                    if ($percent < $threshold) continue;

                    // Cache key prevents sending the same threshold email twice in the same month
                    $cacheKey = "usage_warning:{$user->id}:{$metric}:{$threshold}:" . now()->format('Y-m');

                    if (Cache::has($cacheKey)) continue;

                    if ($isDryRun) {
                        $this->line("  [dry-run] {$user->email} — {$label}: {$percent}% used ({$threshold}% threshold)");
                        continue;
                    }

                    try {
                        Mail::to($user->email)->queue(new ApiUsageWarning(
                            user: $user,
                            resourceName: $label,
                            used: $used,
                            limit: $limit,
                            usagePercent: $percent,
                            plan: $plan,
                            resetDate: now()->startOfMonth()->addMonth(),
                        ));

                        // Mark as sent for the rest of this billing month
                        Cache::put($cacheKey, true, now()->startOfMonth()->addMonth());

                        $sent++;
                        $this->line("  ✓ Warned: {$user->email} — {$label} at {$percent}%");
                    } catch (\Throwable $e) {
                        $this->warn("  ✗ Failed for {$user->email}: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->info("Done. Warnings sent: {$sent}.");
        return self::SUCCESS;
    }
}
