<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Widget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'uuid', 'token',

        // Identity
        'ai_name', 'ai_avatar', 'tone',
        'ai_provider', 'ai_model',

        // Appearance
        'primary_color', 'secondary_color', 'text_color', 'bg_color',
        'position', 'launcher_shape', 'launcher_icon', 'font_family', 'theme',

        // Behavior
        'welcome_message', 'welcome_messages', 'suggested_replies', 'conversation_starters', 'conversation_starters_multi', 'placeholder_text', 'placeholder_texts',
        'show_branding', 'auto_open', 'auto_open_delay', 'show_on_mobile',
        'collect_email', 'collect_phone', 'file_upload_enabled',
        'voice_input_enabled', 'emoji_enabled', 'typing_animation', 'message_streaming',
        'sound_enabled', 'offline_form_enabled', 'offline_form_message', 'offline_form_messages',

        // Smart features
        'smart_suggestions_enabled', 'smart_suggestions_count', 'auto_language_detection',

        // Proactive messaging
        'proactive_enabled', 'proactive_trigger_seconds', 'proactive_trigger_pages', 'proactive_message',

        // Handoff
        'show_handoff_button', 'handoff_button_label', 'handoff_acknowledgment',

        // Webhooks
        'webhook_enabled', 'webhook_url', 'webhook_secret', 'webhook_lead_always',

        // Notification integrations
        'slack_webhook_url', 'discord_webhook_url', 'notification_email',
        'notify_on_lead', 'notify_on_handoff',

        // CSAT
        'csat_prompt_enabled',

        // AI Settings
        'temperature', 'response_length', 'strict_mode', 'system_prompt',
        'lead_qualification_flow', 'lead_qualification_enabled',

        // Security
        'allowed_domains', 'domain_restriction', 'rate_limit', 'is_active', 'booking',

        // Timestamps & stats
        'last_deployed_at', 'total_conversations', 'total_messages', 'total_leads',
    ];

    protected function casts(): array
    {
        return [
            // Booleans
            'show_branding'               => 'boolean',
            'auto_open'                   => 'boolean',
            'show_on_mobile'              => 'boolean',
            'collect_email'               => 'boolean',
            'collect_phone'               => 'boolean',
            'file_upload_enabled'         => 'boolean',
            'voice_input_enabled'         => 'boolean',
            'emoji_enabled'               => 'boolean',
            'typing_animation'            => 'boolean',
            'message_streaming'           => 'boolean',
            'sound_enabled'               => 'boolean',
            'offline_form_enabled'        => 'boolean',
            'strict_mode'                 => 'boolean',
            'lead_qualification_enabled'  => 'boolean',
            'domain_restriction'          => 'boolean',
            'is_active'                   => 'boolean',
            'booking'                     => 'array',
            'show_handoff_button'         => 'boolean',
            'webhook_enabled'             => 'boolean',
            'webhook_lead_always'         => 'boolean',
            'csat_prompt_enabled'         => 'boolean',
            'smart_suggestions_enabled'   => 'boolean',
            'auto_language_detection'     => 'boolean',
            'proactive_enabled'           => 'boolean',
            'notify_on_lead'              => 'boolean',
            'notify_on_handoff'           => 'boolean',

            // Numerics
            'temperature'                 => 'float',
            'smart_suggestions_count'     => 'integer',
            'proactive_trigger_seconds'   => 'integer',
            'proactive_trigger_pages'     => 'integer',

            // JSON
            'allowed_domains'             => 'array',
            'lead_qualification_flow'     => 'array',
            'suggested_replies'           => 'array',
            'conversation_starters'       => 'array',
            'welcome_messages'            => 'array',
            'placeholder_texts'            => 'array',
            'conversation_starters_multi' => 'array',
            'offline_form_messages'       => 'array',

            // Dates
            'last_deployed_at'            => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Widget $widget) {
            $widget->uuid  = $widget->uuid  ?: (string) Str::uuid();
            $widget->token = $widget->token ?: Str::random(48);
        });
    }

    // ─────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────

    public function appointments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function calendarConnections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CalendarConnection::class);
    }

    public function crmConnections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CrmConnection::class);
    }

    /** Booking settings merged over defaults (see App\Support\Booking). */
    public function bookingSettings(): array
    {
        return \App\Support\Booking::settings($this);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function knowledgeBases(): HasMany
    {
        return $this->hasMany(KnowledgeBase::class);
    }

    public function activeKnowledgeBase(): HasOne
    {
        return $this->hasOne(KnowledgeBase::class)->where('is_active', true)->latestOfMany();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    public function proactiveMessages(): HasMany
    {
        return $this->hasMany(ProactiveMessage::class);
    }

    public function offlineMessages(): HasMany
    {
        return $this->hasMany(OfflineMessage::class);
    }

    public function abTests(): HasMany
    {
        return $this->hasMany(WidgetAbTest::class);
    }

    public function channelConfigurations(): HasMany
    {
        return $this->hasMany(ChannelConfiguration::class);
    }

    public function channelMessages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class);
    }

    public function channelContacts(): HasMany
    {
        return $this->hasMany(ChannelContact::class);
    }

    /**
     * Get all enabled channels for this widget
     */
    public function enabledChannels()
    {
        return $this->channelConfigurations()->where('is_enabled', true)->orderBy('is_default', 'desc');
    }

    /**
     * Get the default channel for this widget
     */
    public function defaultChannel()
    {
        return $this->channelConfigurations()->where('is_default', true)->first()
            ?? $this->channelConfigurations()->where('channel_type', 'web')->first();
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function getEmbedScript(): string
    {
        $url = config('app.url') . '/widget.js';
        return sprintf(
            '<script src="%s" data-widget-id="%s" defer></script>',
            $url,
            $this->uuid
        );
    }

    public function isDomainAllowed(string $domain): bool
    {
        if (!$this->domain_restriction) return true;
        $domains = $this->allowed_domains ?? [];
        $domain = strtolower($domain);
        foreach ($domains as $allowed) {
            $allowed = rtrim(strtolower(preg_replace('#^https?://#', '', trim($allowed))), '/');
            if (fnmatch($allowed, $domain)) return true;
        }
        return false;
    }

    public function regenerateToken(): void
    {
        $this->update(['token' => Str::random(48)]);
    }

    public function getProviderLabel(): string
    {
        return match ($this->ai_provider ?? 'openai') {
            'anthropic' => 'Anthropic Claude',
            'gemini'    => 'Google Gemini',
            'groq'      => 'Groq',
            'openrouter' => 'OpenRouter',
            default     => 'OpenAI',
        };
    }
}
