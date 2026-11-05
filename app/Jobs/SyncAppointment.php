<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\CalendarService;
use App\Services\CrmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Pushes a booking change to connected calendars and CRMs. */
class SyncAppointment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $appointmentId, public string $event) {}

    public function handle(CalendarService $calendars, CrmService $crm): void
    {
        $a = Appointment::with('widget.calendarConnections', 'widget.crmConnections')->find($this->appointmentId);
        if (! $a) {
            return;
        }
        $calendars->sync($a);
        $crm->sync($a, $this->event);
    }
}
