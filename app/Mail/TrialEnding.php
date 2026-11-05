<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEnding extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User    $user,
        public int     $daysLeft,
        public ?Carbon $trialEndsAt = null,
        public array   $usageStats  = []
    ) {}

    public function envelope(): Envelope
    {
        $days = $this->daysLeft;
        $suffix = $days === 1 ? 'tomorrow' : "in {$days} days";

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            subject: "Your " . config('branding.name') . " trial ends {$suffix}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.billing.trial-ending');
    }
}
