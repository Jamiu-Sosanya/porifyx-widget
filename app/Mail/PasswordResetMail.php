<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User    $user,
        public string  $resetUrl,
        public ?string $ipAddress = null,
        public int     $expiry    = 60
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new \Illuminate\Mail\Mailables\Address($this->user->email, $this->user->name)],
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            subject: 'Reset your ' . config('branding.name') . ' password',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth.reset-password');
    }
}
