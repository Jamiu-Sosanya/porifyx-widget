<?php

namespace App\Mail;

use App\Models\OfflineMessage;
use App\Models\Widget;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OfflineMessageNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OfflineMessage $offlineMessage,
        public Widget         $widget
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->offlineMessage->name ?? 'A visitor';

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            subject: "📬 Offline message from {$name} — {$this->widget->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.offline-message');
    }
}
