<?php

namespace App\Services;

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
     */
    public function book(Widget $widget, array $data, ?Conversation $conversation = null): Appointment
    {
        $settings = Booking::settings($widget);
        $service = Booking::service($settings, $data['service_id']);
        if (! $settings['enabled'] || ! $service) {
            throw ValidationException::withMessages(['service_id' => 'This service is not available for booking.']);
        }

        $start = CarbonImmutable::parse($data['start'])->utc();

        $appointment = DB::transaction(function () use ($widget, $data, $service, $settings, $start, $conversation) {
            // Lock the widget row so concurrent bookings for it run one at a time.
            Widget::whereKey($widget->id)->lockForUpdate()->first();

            if (! Booking::isFree($widget, $service, $start)) {
                throw ValidationException::withMessages(['start' => 'Sorry, that time was just taken. Please choose another.']);
            }

            $lead = Lead::where('widget_id', $widget->id)->where('email', $data['email'])->first();
            $note = 'Booked ' . $service['name'] . ' for ' . $start->setTimezone($settings['timezone'])->format('D j M Y, g:i A') . '.';
            if ($lead) {
                $lead->update([
                    'name'               => $lead->name ?: $data['name'],
                    'phone'              => $lead->phone ?: ($data['phone'] ?? null),
                    'temperature'        => 'hot',
                    'notes'              => trim(($lead->notes ? $lead->notes . "\n" : '') . $note),
                    'last_engagement_at' => now(),
                ]);
            } else {
                $lead = Lead::create([
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

            return Appointment::create([
                'widget_id'        => $widget->id,
                'user_id'          => $widget->user_id,
                'conversation_id'  => $conversation?->id,
                'lead_id'          => $lead->id,
                'service_id'       => (string) $service['id'],
                'service_name'     => $service['name'],
                'duration'         => (int) $service['duration'],
                'name'             => $data['name'],
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'starts_at'        => $start,
                'ends_at'          => $start->addMinutes((int) $service['duration']),
                'visitor_timezone' => $data['timezone'] ?? null,
                'status'           => $settings['auto_confirm'] ? 'confirmed' : 'pending',
            ]);
        });

        $this->mail($appointment, 'booked');
        $this->mail($appointment, 'owner_new');
        $this->webhook($appointment, 'appointment.booked');

        return $appointment;
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
    }

    public function mail(Appointment $appointment, string $kind): void
    {
        $widget = $appointment->widget;
        $to = str_starts_with($kind, 'owner_') ? ($widget->notification_email ?? $widget->user?->email) : $appointment->email;
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->queue(new AppointmentMail($appointment, $kind));
        } catch (\Throwable $e) {
            Log::warning('Appointment email failed: ' . $e->getMessage());
        }
    }

    private function webhook(Appointment $a, string $event): void
    {
        $this->webhooks->dispatch($a->widget, $event, [
            'appointment' => [
                'id'        => $a->id,
                'status'    => $a->status,
                'service'   => $a->service_name,
                'duration'  => $a->duration,
                'starts_at' => $a->starts_at->toIso8601ZuluString(),
                'ends_at'   => $a->ends_at->toIso8601ZuluString(),
                'name'      => $a->name,
                'email'     => $a->email,
                'phone'     => $a->phone,
                'notes'     => $a->notes,
            ],
        ]);
    }
}
