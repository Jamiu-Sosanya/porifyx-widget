<?php

namespace App\Console\Commands;

use App\Mail\TrialEnding;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialEndingReminders extends Command
{
    protected $signature = 'app:trial-reminders
                            {--days=3 : How many days before trial end to send the reminder}
                            {--dry-run : List users who would be emailed without sending}';

    protected $description = 'Send trial-ending reminder emails to users whose trials expire soon';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');

        $windowStart = now()->addDays($days)->startOfDay();
        $windowEnd   = now()->addDays($days)->endOfDay();

        $subscriptions = Subscription::query()
            ->with('user', 'plan')
            ->where('status', 'trialing')
            ->whereBetween('trial_ends_at', [$windowStart, $windowEnd])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info("No trials ending in exactly {$days} day(s). Nothing to send.");
            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} trial(s) ending in {$days} day(s).");

        $sent = 0;
        $errors = 0;

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;

            if (! $user) {
                continue;
            }

            $trialEndsAt = $subscription->trial_ends_at;
            $daysLeft    = (int) now()->diffInDays($trialEndsAt, false);

            $usageStats = [
                'conversations' => $user->conversations()->count(),
                'leads'         => $user->leads()->count(),
                'widgets'       => $user->widgets()->count(),
            ];

            if ($isDryRun) {
                $this->line("  [dry-run] Would email: {$user->email} (trial ends {$trialEndsAt->toDateString()})");
                continue;
            }

            try {
                Mail::to($user->email)->queue(
                    new TrialEnding($user, $daysLeft, $trialEndsAt, $usageStats)
                );
                $sent++;
                $this->line("  ✓ Queued: {$user->email}");
            } catch (\Throwable $e) {
                $errors++;
                $this->warn("  ✗ Failed for {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Sent: {$sent}, Errors: {$errors}.");

        return self::SUCCESS;
    }
}
