<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    public const STATUSES = [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show'   => 'No-show',
    ];

    /** Statuses that hold a time slot. */
    public const ACTIVE = ['pending', 'confirmed'];

    protected $fillable = [
        'widget_id', 'user_id', 'conversation_id', 'lead_id', 'service_id', 'service_name', 'duration',
        'name', 'email', 'phone', 'notes', 'starts_at', 'ends_at', 'visitor_timezone', 'status', 'token', 'reminder_sent_at',
    ];

    protected $casts = [
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $a) => $a->token ??= Str::random(48));
    }

    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }

    /** Start time in the business's own timezone. */
    public function localStart(): \Carbon\Carbon
    {
        return $this->starts_at->copy()->setTimezone($this->widget->bookingSettings()['timezone']);
    }

    public function manageUrl(): string
    {
        return route('appointments.manage', $this->token);
    }

    /** iCalendar file so the visitor can add the booking to their calendar. */
    public function toIcs(): string
    {
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $esc = fn ($s) => addcslashes((string) $s, ",;\\");
        $method = $this->status === 'cancelled' ? 'CANCEL' : 'REQUEST';

        return implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//PoriFyx//Bookings//EN', 'METHOD:' . $method,
            'BEGIN:VEVENT',
            'UID:appointment-' . $this->id . '@' . parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $fmt($this->starts_at),
            'DTEND:' . $fmt($this->ends_at),
            'SUMMARY:' . $esc($this->service_name . ' with ' . $this->widget->bookingSettings()['business_name']),
            'DESCRIPTION:' . $esc('Manage or cancel: ' . $this->manageUrl()),
            'STATUS:' . ($this->status === 'cancelled' ? 'CANCELLED' : 'CONFIRMED'),
            'END:VEVENT', 'END:VCALENDAR', '',
        ]);
    }
}
