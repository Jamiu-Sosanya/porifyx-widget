<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    protected $fillable = [
        'widget_id', 'fingerprint', 'email', 'name', 'phone',
        'ip_address', 'country', 'city', 'browser', 'os', 'device_type',
        'timezone', 'language', 'visit_count', 'first_seen_at', 'last_seen_at',
        'behavior_data', 'custom_attributes',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at'     => 'datetime',
            'last_seen_at'      => 'datetime',
            'behavior_data'     => 'array',
            'custom_attributes' => 'array',
        ];
    }

    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }
    public function leads(): HasMany { return $this->hasMany(Lead::class); }
}
