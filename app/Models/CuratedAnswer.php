<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuratedAnswer extends Model
{
    protected $fillable = ['widget_id', 'question', 'keywords', 'answer', 'is_active', 'hits'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /** Best active curated answer for a visitor message, or null. */
    public static function match(Widget $widget, string $message): ?self
    {
        $msg = KnowledgeGap::normalize($message);
        if ($msg === '') return null;

        foreach (static::where('widget_id', $widget->id)->where('is_active', true)->get() as $c) {
            foreach (array_filter(array_map(fn ($k) => KnowledgeGap::normalize($k), explode(',', (string) $c->keywords))) as $k) {
                if (preg_match('/\b' . preg_quote($k, '/') . '\b/u', $msg)) return $c;
            }
            similar_text($msg, KnowledgeGap::normalize($c->question), $pct);
            if ($pct >= 82) return $c;
        }

        return null;
    }
}
