<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** One mailable for every booking email. $kind: booked, confirmed, cancelled, reminder, rescheduled, owner_new, owner_cancelled, owner_rescheduled. */
class AppointmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment, public string $kind) {}

    public function forOwner(): bool
    {
        return str_starts_with($this->kind, 'owner_');
    }

    public function envelope(): Envelope
    {
        $a = $this->appointment;
        $when = $a->localStart()->format('D j M, g:i A');
        $subject = match ($this->kind) {
            'booked'          => $a->status === 'pending' ? "Booking request received: {$a->service_name}, {$when}" : "Booking confirmed: {$a->service_name}, {$when}",
            'confirmed'       => "Booking confirmed: {$a->service_name}, {$when}",
            'cancelled'       => "Booking cancelled: {$a->service_name}, {$when}",
            'reminder'        => "Reminder: {$a->service_name}, {$when}",
            'owner_new'       => "New booking: {$a->name}, {$a->service_name}, {$when}",
            'owner_cancelled' => "Booking cancelled by {$a->name}: {$when}",
            'rescheduled'       => "Booking moved: {$a->service_name}, now {$when}",
            'owner_rescheduled' => "{$a->name} moved their booking to {$when}",
        };

        $s = \App\Support\Booking::settings($a->widget);
        $replyTo = $this->forOwner() ? $a->email : \App\Support\Booking::replyTo($a->widget, $s);

        return new Envelope(
            from: $this->forOwner() ? null : new \Illuminate\Mail\Mailables\Address(config('mail.from.address'), $s['business_name']),
            subject: $subject . ' · ' . $a->reference,
            replyTo: $replyTo ? [$replyTo] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.appointment');
    }

    public function attachments(): array
    {
        if ($this->forOwner() || $this->kind === 'reminder') {
            return [];
        }

        return [Attachment::fromData(fn () => $this->appointment->toIcs(), 'appointment.ics')->withMime('text/calendar')];
    }
}
