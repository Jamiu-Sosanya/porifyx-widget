<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User    $inviter,
        public string  $acceptUrl,
        public string  $role         = 'member',
        public ?string $inviteeName  = null,
        public ?Carbon $expiresAt    = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            replyTo: [
                new \Illuminate\Mail\Mailables\Address($this->inviter->email, $this->inviter->name),
            ],
            subject: $this->inviter->name . ' invited you to join ' . config('branding.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.team.invitation');
    }
}
