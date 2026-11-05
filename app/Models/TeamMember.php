<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'owner_id', 'user_id', 'role', 'widget_access',
        'can_view_leads', 'can_manage_training', 'can_manage_settings',
        'status', 'invite_token', 'invite_expires_at', 'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'widget_access'       => 'array',
            'can_view_leads'      => 'boolean',
            'can_manage_training' => 'boolean',
            'can_manage_settings' => 'boolean',
            'invite_expires_at'   => 'datetime',
            'joined_at'           => 'datetime',
        ];
    }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isActive(): bool { return $this->status === 'active'; }
    public function isPending(): bool { return $this->status === 'pending'; }
}
