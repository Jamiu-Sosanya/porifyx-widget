<?php

use App\Jobs\AggregateAnalytics;
use Illuminate\Support\Facades\Schedule;

// Aggregate daily analytics at midnight
Schedule::job(new AggregateAnalytics)->daily()->at('00:30');

// Send trial-ending reminder emails — runs daily, targets trials ending in 3 days and 1 day
Schedule::command('app:trial-reminders --days=3')->daily()->at('09:00');
Schedule::command('app:trial-reminders --days=1')->daily()->at('09:05');

// Send weekly analytics summary emails every Monday morning
Schedule::command('app:weekly-reports')->weekly()->mondays()->at('08:00');

// Check for system alerts daily
Schedule::command('alerts:check')->daily()->at('07:00');

// Send usage warning emails when users hit 80% or 90% of plan limits
Schedule::command('app:usage-warnings')->daily()->at('10:00');

// Booking reminders: emails visitors before confirmed appointments (per-widget "reminder_hours").
Artisan::command('appointments:remind', function (App\Services\AppointmentService $service) {
    $sent = 0;
    App\Models\Appointment::with('widget')
        ->where('status', 'confirmed')->whereNull('reminder_sent_at')
        ->whereBetween('starts_at', [now(), now()->addDays(7)])
        ->each(function ($a) use ($service, &$sent) {
            $hours = (int) $a->widget->bookingSettings()['reminder_hours'];
            if ($hours > 0 && $a->starts_at->lte(now()->addHours($hours))) {
                $service->mail($a, 'reminder');
                $a->update(['reminder_sent_at' => now()]);
                $sent++;
            }
        });
    $this->info("Sent {$sent} reminder(s).");
})->purpose('Email booking reminders');
Schedule::command('appointments:remind')->everyFifteenMinutes();
Schedule::command('tickets:escalate')->hourly();
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put('pfx:scheduler_heartbeat', now()->toIso8601String(), 3600))->everyMinute()->name('scheduler-heartbeat');

// GDPR retention: delete visitor conversations older than the admin's setting
Schedule::call(function () {
    $days = (int) \App\Support\PrivacyPolicy::get()['retention_days'];
    if ($days < 1) return;
    \App\Models\Conversation::where('last_message_at', '<', now()->subDays($days))->orWhere(fn ($q) => $q->whereNull('last_message_at')->where('created_at', '<', now()->subDays($days)))
        ->chunkById(200, function ($cs) { foreach ($cs as $c) { $c->messages()->delete(); $c->delete(); } });
})->daily()->at('03:15')->name('privacy-retention');
