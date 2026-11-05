<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'user_id', 'invoice_number', 'stripe_invoice_id', 'amount',
        'currency', 'status', 'description', 'line_items',
        'due_date', 'paid_at', 'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'line_items' => 'array',
            'due_date'   => 'datetime',
            'paid_at'    => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isPaid(): bool { return $this->status === 'paid'; }

    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }
}
