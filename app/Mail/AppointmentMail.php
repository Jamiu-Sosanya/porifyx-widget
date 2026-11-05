<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** One mailable for every booking email. $kind: booked, confirmed, cancelled, reminder, owner_new, owner_cancelled. */
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
        };

        return new Envelope(subject: $subject, replyTo: $this->forOwner() ? [$a->email] : []);
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
