<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HelpArticle extends Model
{
    protected $fillable = ['widget_id', 'help_category_id', 'title', 'slug', 'body', 'is_published', 'views'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'help_category_id');
    }

    /** Markdown to safe HTML (raw HTML is escaped, javascript: links dropped). */
    public function html(): string
    {
        return Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    public static function uniqueSlug(int $widgetId, string $title, ?int $ignoreId = null, string $model = self::class): string
    {
        $base = Str::slug($title) ?: 'item';
        $slug = $base;
        for ($i = 2; $model::where('widget_id', $widgetId)->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
