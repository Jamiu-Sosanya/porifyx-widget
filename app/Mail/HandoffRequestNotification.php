<?php

namespace App\Mail;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Widget;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoffRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public Widget       $widget,
        public ?Message     $lastMessage = null
    ) {}

    public function envelope(): Envelope
    {
        $visitor = $this->conversation->visitor?->name
            ?? $this->conversation->visitor?->email
            ?? 'A visitor';

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            subject: "🙋 Agent requested by {$visitor} — {$this->widget->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.handoff-request');
    }
}
