<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'conversation_id', 'role', 'agent_id', 'content', 'content_type',
        'file_path', 'file_name', 'file_mime', 'confidence_score', 'sources_used',
        'is_fallback', 'metadata', 'is_read',
    ];

    protected function casts(): array
    {
        return [
            'sources_used'     => 'array',
            'metadata'         => 'array',
            'is_fallback'      => 'boolean',
            'is_read'          => 'boolean',
            'confidence_score' => 'float',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->uuid = $m->uuid ?: (string) Str::uuid());
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function isFromVisitor(): bool  { return $this->role === 'visitor'; }
    public function isFromAi(): bool       { return $this->role === 'ai'; }
    public function isFromAgent(): bool    { return $this->role === 'agent'; }
    public function isFromSystem(): bool   { return $this->role === 'system'; }
}
