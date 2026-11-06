<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatFlow extends Model
{
    public const STEP_TYPES = [
        'message'  => 'Send a message',
        'question' => 'Ask a question',
        'branch'   => 'Branch on an answer',
        'tag'      => 'Tag the conversation',
        'webhook'  => 'Send to a webhook',
        'escalate' => 'Hand over to a human',
    ];

    public const OPERATORS = ['equals' => 'equals', 'contains' => 'contains', 'starts_with' => 'starts with', 'is_empty' => 'is empty', 'not_empty' => 'is not empty'];

    protected $fillable = ['widget_id', 'name', 'is_active', 'trigger_keywords', 'match_mode', 'steps', 'runs'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'steps' => 'array'];
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /** Does a visitor message start this flow? */
    public function matches(string $message): bool
    {
        $msg = KnowledgeGap::normalize($message);
        $keys = array_values(array_filter(array_map(fn ($k) => KnowledgeGap::normalize($k), explode(',', (string) $this->trigger_keywords))));
        if (! $keys || $msg === '') return false;

        return match ($this->match_mode) {
            'exact' => in_array($msg, $keys, true),
            'all'   => collect($keys)->every(fn ($k) => str_contains(" $msg ", " $k ")),
            default => collect($keys)->contains(fn ($k) => str_contains(" $msg ", " $k ")),
        };
    }
}
