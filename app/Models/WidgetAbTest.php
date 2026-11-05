<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetAbTest extends Model
{
    protected $fillable = [
        'widget_id', 'name', 'variant_a', 'variant_b', 'split_ratio',
        'impressions_a', 'impressions_b', 'leads_a', 'leads_b',
        'conversations_a', 'conversations_b', 'is_active', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'variant_a'       => 'array',
        'variant_b'       => 'array',
        'split_ratio'     => 'float',
        'is_active'       => 'boolean',
        'started_at'      => 'datetime',
        'ended_at'        => 'datetime',
    ];

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Determine which variant to show for a given visitor fingerprint.
     */
    public function assignVariant(string $fingerprint): string
    {
        $hash = crc32($fingerprint . $this->id);
        return ($hash % 100) / 100 < $this->split_ratio ? 'a' : 'b';
    }

    public function getLeadConversionRate(string $variant): float
    {
        $conv = $this->{"conversations_{$variant}"};
        $lead = $this->{"leads_{$variant}"};
        if ($conv === 0) return 0;
        return round($lead / $conv * 100, 1);
    }

    public function getWinner(): ?string
    {
        $cra = $this->getLeadConversionRate('a');
        $crb = $this->getLeadConversionRate('b');
        if ($cra === $crb) return null;
        return $cra > $crb ? 'a' : 'b';
    }
}
