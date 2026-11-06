<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** kind: created | reply (to the customer) · owner_customer | owner_escalated | owner_new (to the team) */
class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public string $kind, public string $body = '') {}

    public function envelope(): Envelope
    {
        $t = $this->ticket;
        $brand = $t->widget?->name ?: $t->owner->company_name ?: config('app.name');
        $subject = match ($this->kind) {
            'created'         => "We received your request: {$t->subject}",
            'reply'           => "Re: {$t->subject}",
            'owner_new'       => "New ticket from {$t->requester_email}: {$t->subject}",
            'owner_customer'  => "Customer replied: {$t->subject}",
            'owner_escalated' => "Escalated, no reply yet: {$t->subject}",
        };
        $toCustomer = in_array($this->kind, ['created', 'reply'], true);

        return new Envelope(
            from: $toCustomer ? new Address(config('mail.from.address'), $brand) : null,
            subject: $subject . ' [' . $t->number . ']',
            replyTo: $toCustomer ? array_filter([($t->owner->support_settings['inbound_address'] ?? null) ?: $t->owner->email]) : [$t->requester_email],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.ticket', with: [
            'toCustomer' => in_array($this->kind, ['created', 'reply'], true),
            'link'       => in_array($this->kind, ['created', 'reply'], true) ? $this->ticket->publicUrl() : route('tickets.show', $this->ticket),
        ]);
    }
}
