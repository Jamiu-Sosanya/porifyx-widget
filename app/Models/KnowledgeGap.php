<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeGap extends Model
{
    protected $fillable = ['widget_id', 'normalized', 'question', 'times_asked', 'status', 'last_asked_at'];

    protected function casts(): array
    {
        return ['last_asked_at' => 'datetime'];
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public static function normalize(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }

    /** Record a question the AI could not answer. Greetings and very short messages are ignored. */
    public static function record(Widget $widget, string $question): void
    {
        $n = mb_substr(static::normalize($question), 0, 191);
        if (str_word_count($n) < 3) return;

        $gap = static::firstOrNew(['widget_id' => $widget->id, 'normalized' => $n]);
        if ($gap->exists) {
            $gap->times_asked++;
            if ($gap->status === 'answered') return;
        } else {
            $gap->question = mb_substr(trim($question), 0, 500);
        }
        $gap->last_asked_at = now();
        $gap->save();
    }
}
