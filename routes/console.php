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
