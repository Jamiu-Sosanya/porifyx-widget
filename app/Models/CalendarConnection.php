<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarConnection extends Model
{
    public const PROVIDERS = ['google' => 'Google Calendar', 'microsoft' => 'Outlook / Microsoft 365'];

    protected $fillable = ['widget_id', 'staff_id', 'provider', 'account_email', 'calendar_id', 'access_token', 'refresh_token', 'expires_at', 'check_busy', 'push_events', 'last_error'];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'access_token'  => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at'    => 'datetime',
        'check_busy'    => 'boolean',
        'push_events'   => 'boolean',
    ];

    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }

    /** Key used in appointments.external_refs. */
    public function refKey(): string { return $this->provider . ':' . $this->id; }
}
