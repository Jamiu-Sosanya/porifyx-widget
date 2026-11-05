<?php

namespace App\Mail;

use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User    $user,
        public float   $amount        = 0,
        public ?string $failureReason = null,
        public ?Plan   $plan          = null,
        public ?Carbon $nextRetry     = null,
        public int     $graceDays     = 7
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            subject: '⚠️ Payment failed — action required (' . config('branding.name') . ')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.billing.payment-failed');
    }
}
