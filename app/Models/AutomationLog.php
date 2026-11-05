<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AutomationLog extends Model
{
    protected $fillable = [
        'automation_id', 'triggerable_type', 'triggerable_id',
        'status', 'error_message', 'payload',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function automation(): BelongsTo { return $this->belongsTo(Automation::class); }
    public function triggerable(): MorphTo { return $this->morphTo(); }
}
