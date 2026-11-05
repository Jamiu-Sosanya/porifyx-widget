<?php

namespace App\Mail;

use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Widget;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLeadNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead          $lead,
        public Widget        $widget,
        public ?Conversation $conversation = null
    ) {}

    public function envelope(): Envelope
    {
        $temp = ucfirst($this->lead->temperature ?? 'cold');
        $name = $this->lead->name ?? $this->lead->email ?? 'Unknown';

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            subject: "🎯 New {$temp} Lead: {$name} — {$this->widget->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.new-lead');
    }
}
