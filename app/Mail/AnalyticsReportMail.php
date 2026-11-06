<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnalyticsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $owner, public array $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your ' . (\App\Support\AnalyticsReport::PERIODS[$this->report['days']] ?? $this->report['days'] . ' days') . ' report · ' . ($this->owner->company_name ?: config('branding.name')));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notifications.analytics-report');
    }
}
