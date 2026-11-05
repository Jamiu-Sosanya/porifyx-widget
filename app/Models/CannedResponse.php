<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CannedResponse extends Model
{
    protected $fillable = [
        'user_id', 'title', 'shortcut', 'category', 'content', 'sort_order', 'use_count',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'use_count'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
