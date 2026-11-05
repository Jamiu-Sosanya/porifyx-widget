<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User  $user,
        public array $stats      = [],
        public array $topWidgets = [],
        public array $hotLeads   = []
    ) {}

    public function envelope(): Envelope
    {
        $start = now()->subWeek()->format('M d');
        $end   = now()->format('M d, Y');

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            subject: "[" . config('branding.name') . "] Your weekly report — {$start} to {$end}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.weekly-report');
    }
}
