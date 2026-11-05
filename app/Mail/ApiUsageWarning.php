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

class ApiUsageWarning extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User    $user,
        public string  $resourceName,
        public int     $used,
        public int     $limit,
        public int     $usagePercent,
        public ?Plan   $plan      = null,
        public ?Carbon $resetDate = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name', config('branding.name'))
            ),
            subject: "⚠️ {$this->usagePercent}% of your {$this->resourceName} used — " . config('branding.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.api-usage-warning');
    }
}
