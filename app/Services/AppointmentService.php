<?php

namespace App\Services;

use App\Jobs\SyncAppointment;
use App\Mail\AppointmentMail;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Widget;
use App\Support\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(protected WidgetWebhookService $webhooks) {}

    /**
     * Book a slot. Re-checks availability inside a transaction so two visitors
     * cannot take the same time.
     *
     * $data: service_id, start, name, email, phone?, notes?, timezone?, staff_id?, answers?
     */
    public function book(Widget $widget, array $data, ?Conversation $conversation = null, string $source = 'widget'): Appointment
    {
        $s = Booking::settings($widget);
        $service = Booking::service($s, $data['service_id']);
        if (! $s['enabled'] || ! $service) {
            throw ValidationException::withMessages(['service_id' => 'This service is not available for booking.']);
        }
        // Plan gate: monthly booking allowance, or subscription inactive.
        if (! $widget->user->canUse('bookings')) {
            throw ValidationException::withMessages(['service_id' => 'Online booking is not available right now. Please contact us directly to book.']);
        }

        $answers = $this->answers($s, (array) ($data['answers'] ?? []));
        $start = CarbonImmutable::parse($data['start'])->utc();

        $appointment = DB::transaction(function () use ($widget, $data, $service, $s, $start, $conversation, $answers, $source) {
            // Lock the widget row so concurrent bookings for it run one at a time.
            Widget::whereKey($widget->id)->lockForUpdate()->first();

            $staff = Booking::claim($widget, $service, $start, $data['staff_id'] ?? null);
            if ($staff === false) {
                throw ValidationException::withMessages(['start' => 'Sorry, that time was just taken. Please choose another.']);
            }

            $lead = $this->lead($widget, $data, $service, $s, $start, $conversation);

            return Appointment::create([
                'widget_id'        => $widget->id,
                'user_id'          => $widget->user_id,
                'conversation_id'  => $conversation?->id,
                'lead_id'          => $lead->id,
                'service_id'       => (string) $service['id'],
                'service_name'     => $service['name'],
                'staff_id'         => $staff['id'] ?? null,
                'staff_name'       => $staff['name'] ?? null,
                'duration'         => (int) $service['duration'],
                'name'             => $data['name'],
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'answers'          => $answers ?: null,
                'starts_at'        => $start,
                'ends_at'          => $start->addMinutes((int) $service['duration']),
                'visitor_timezone' => $data['timezone'] ?? null,
                'status'           => $s['auto_confirm'] || $source === 'dashboard' ? 'confirmed' : 'pending',
                'source'           => $source,
            ]);
        });
        if (! $widget->isPlaygroundRequest()) $widget->user->incrementUsage('bookings');

        $this->announce($appointment);

        return $appointment;
    }

    /** Emails, webhook and CRM/calendar sync for a booking that is now real (free, or paid). */
    public function announce(Appointment $a): void
    {
        $this->mail($a, 'booked');
        $this->mail($a, 'owner_new');
        $this->webhook($a, 'appointment.booked');
        SyncAppointment::dispatch($a->id, 'appointment.booked');
    }

    /** Validate custom intake answers against the configured questions; keyed by question label. */
    private function answers(array $s, array $given): array
    {
        $out = [];
        $errors = [];
        foreach ($s['questions'] as $q) {
            $v = $given[$q['id']] ?? null;
            if ($q['type'] === 'checkbox') {
                $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                if (! empty($q['required']) && ! $v) {
                    $errors['answers.' . $q['id']] = 'Please tick: ' . $q['label'];
                }
            } else {
                $v = is_scalar($v) ? trim((string) $v) : '';
                if (! empty($q['required']) && $v === '') {
                    $errors['answers.' . $q['id']] = 'Please answer: ' . $q['label'];
                }
                if ($q['type'] === 'select' && $v !== '' && ! in_array($v, self::options($q), true)) {
                    $errors['answers.' . $q['id']] = 'Please choose a valid option for: ' . $q['label'];
                }
                $v = mb_substr($v, 0, 1000);
            }
            if ($v !== '' && $v !== null) {
                $out[$q['label']] = $v;
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $out;
    }

    public static function options(array $q): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ($q['options'] ?? '')))));
    }

    private function lead(Widget $widget, array $data, array $service, array $s, CarbonImmutable $start, ?Conversation $conversation): Lead
    {
        $lead = Lead::where('widget_id', $widget->id)->where('email', $data['email'])->first();
        $note = 'Booked ' . $service['name'] . ' for ' . $start->setTimezone($s['timezone'])->format('D j M Y, g:i A') . '.';
        if ($lead) {
            $lead->update([
                'name'               => $lead->name ?: $data['name'],
                'phone'              => $lead->phone ?: ($data['phone'] ?? null),
                'temperature'        => 'hot',
                'notes'              => trim(($lead->notes ? $lead->notes . "\n" : '') . $note),
                'last_engagement_at' => now(),
            ]);

            return $lead;
        }

        return Lead::create([
            'user_id'            => $widget->user_id,
            'widget_id'          => $widget->id,
            'conversation_id'    => $conversation?->id,
            'visitor_id'         => $conversation?->visitor_id,
            'name'               => $data['name'],
            'email'              => $data['email'],
            'phone'              => $data['phone'] ?? null,
            'service_interest'   => $service['name'],
            'temperature'        => 'hot',
            'score'              => 90,
            'status'             => 'new',
            'notes'              => $note,
            'last_engagement_at' => now(),
        ]);
    }

    /** Change status and tell the right people. $byVisitor is true when the visitor cancels from their link. */
    public function setStatus(Appointment $appointment, string $status, bool $byVisitor = false): void
    {
        $previous = $appointment->status;
        if ($previous === $status) {
            return;
        }
        $appointment->update(['status' => $status]);

        if ($status === 'confirmed' && $previous === 'pending') {
            $this->mail($appointment, 'confirmed');
        }
        if ($status === 'cancelled') {
            $this->mail($appointment, $byVisitor ? 'owner_cancelled' : 'cancelled');
            if ($byVisitor) {
                $this->mail($appointment, 'cancelled');
            }
        }
        $this->webhook($appointment, 'appointment.' . $status);
        SyncAppointment::dispatch($appointment->id, 'appointment.' . $status);
    }

    /** Move a booking to a new start time (same service), keeping or reassigning staff. */
    public function reschedule(Appointment $a, string $start, ?string $staffId = null, bool $byVisitor = false): void
    {
        $widget = $a->widget;
        $service = Booking::service(Booking::settings($widget), $a->service_id)
            ?? ['id' => $a->service_id, 'name' => $a->service_name, 'duration' => $a->duration];
        $start = CarbonImmutable::parse($start)->utc();

        DB::transaction(function () use ($a, $widget, $service, $start, $staffId) {
            Widget::whereKey($widget->id)->lockForUpdate()->first();
            $staff = Booking::claim($widget, $service, $start, $staffId ?? $a->staff_id, $a->id);
            if ($staff === false) {
                throw ValidationException::withMessages(['start' => 'That time is no longer free. Please choose another.']);
            }
            $a->update([
                'starts_at'        => $start,
                'ends_at'          => $start->addMinutes($a->duration),
                'staff_id'         => $staff['id'] ?? null,
                'staff_name'       => $staff['name'] ?? null,
                'reminder_sent_at' => null,
            ]);
        });

        $this->mail($a, 'rescheduled');
        if ($byVisitor) {
            $this->mail($a, 'owner_rescheduled');
        }
        $this->webhook($a, 'appointment.rescheduled');
        SyncAppointment::dispatch($a->id, 'appointment.rescheduled');
    }

    public function mail(Appointment $appointment, string $kind): void
    {
        $widget = $appointment->widget;
        $owner = str_starts_with($kind, 'owner_');
        $to = $owner ? ($widget->notification_email ?? $widget->user?->email) : $appointment->email;
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->queue(new AppointmentMail($appointment, $kind));
            // Staff members with an email also hear about their own bookings.
            $staffEmail = $owner && $appointment->staff_id
                ? collect(Booking::settings($widget)['staff'])->firstWhere('id', $appointment->staff_id)['email'] ?? null
                : null;
            if ($staffEmail && $staffEmail !== $to) {
                Mail::to($staffEmail)->queue(new AppointmentMail($appointment, $kind));
            }
        } catch (\Throwable $e) {
            Log::warning('Appointment email failed: ' . $e->getMessage());
        }
    }

    public static function payload(Appointment $a): array
    {
        return [
            'id'             => $a->id,
            'reference'      => $a->reference,
            'widget_id'      => $a->widget_id,
            'status'         => $a->status,
            'service_id'     => $a->service_id,
            'service'        => $a->service_name,
            'staff_id'       => $a->staff_id,
            'staff'          => $a->staff_name,
            'duration'       => $a->duration,
            'starts_at'      => $a->starts_at->toIso8601ZuluString(),
            'ends_at'        => $a->ends_at->toIso8601ZuluString(),
            'name'           => $a->name,
            'email'          => $a->email,
            'phone'          => $a->phone,
            'notes'          => $a->notes,
            'answers'        => $a->answers ?? (object) [],
            'source'         => $a->source,
            'lead_id'        => $a->lead_id,
            'manage_url'     => $a->manageUrl(),
            'created_at'     => $a->created_at?->toIso8601ZuluString(),
        ];
    }

    private function webhook(Appointment $a, string $event): void
    {
        $this->webhooks->dispatch($a->widget, $event, ['appointment' => self::payload($a)]);
    }
}
