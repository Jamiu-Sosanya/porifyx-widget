<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReport;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReports extends Command
{
    protected $signature = 'app:weekly-reports
                            {--dry-run : List users who would receive reports without sending}';

    protected $description = 'Send weekly analytics summary emails to all active users';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $users = User::query()
            ->where('is_active', true)
            ->where('role', '!=', 'admin')
            ->whereHas('widgets')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No active users with widgets. Nothing to send.');
            return self::SUCCESS;
        }

        $this->info("Preparing weekly reports for {$users->count()} user(s).");

        $sent   = 0;
        $errors = 0;
        $weekStart = now()->subWeek()->startOfDay();
        $weekEnd   = now()->startOfDay();

        foreach ($users as $user) {
            $stats = $this->buildStats($user, $weekStart, $weekEnd);

            if ($isDryRun) {
                $this->line("  [dry-run] Would email: {$user->email}");
                continue;
            }

            try {
                Mail::to($user->email)->queue(
                    new WeeklyReport(
                        user: $user,
                        stats: $stats['totals'],
                        topWidgets: $stats['topWidgets'],
                        hotLeads: $stats['hotLeads'],
                    )
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

    private function buildStats(User $user, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $widgetIds = $user->widgets()->pluck('id');

        $newConversations = \App\Models\Conversation::whereIn('widget_id', $widgetIds)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $newLeads = \App\Models\Lead::whereIn('widget_id', $widgetIds)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $messages = \App\Models\Message::whereHas(
            'conversation',
            fn ($q) => $q->whereIn('widget_id', $widgetIds)
        )->whereBetween('created_at', [$from, $to])->count();

        $totals = [
            'conversations'      => $newConversations,
            'leads'              => $newLeads,
            'messages'           => $messages,
            'widgets_active'     => $user->widgets()->where('is_active', true)->count(),
            'period_label'       => $from->format('M j') . ' – ' . $to->format('M j, Y'),
        ];

        $topWidgets = $user->widgets()
            ->where('is_active', true)
            ->withCount(['conversations' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
            ->orderByDesc('conversations_count')
            ->limit(3)
            ->get(['id', 'name', 'conversations_count'])
            ->map(fn ($w) => ['name' => $w->name, 'conversations' => $w->conversations_count])
            ->toArray();

        $hotLeads = \App\Models\Lead::whereIn('widget_id', $widgetIds)
            ->where('temperature', 'hot')
            ->whereBetween('created_at', [$from, $to])
            ->limit(5)
            ->get(['id', 'name', 'email', 'score', 'service_interest'])
            ->map(fn ($l) => [
                'name'     => $l->name ?? $l->email ?? 'Anonymous',
                'score'    => $l->score,
                'interest' => $l->service_interest,
            ])
            ->toArray();

        return compact('totals', 'topWidgets', 'hotLeads');
    }
}
