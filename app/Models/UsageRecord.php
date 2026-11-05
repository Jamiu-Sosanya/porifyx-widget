<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    protected $fillable = ['user_id', 'metric', 'count', 'overage_cost', 'year', 'month'];

    protected function casts(): array
    {
        return ['overage_cost' => 'decimal:2'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
