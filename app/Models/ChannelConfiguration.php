<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelConfiguration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'widget_id',
        'channel_type',
        'credentials',
        'is_enabled',
        'is_default',
        'settings',
        'webhook_url',
        'webhook_secret',
        'verification_token',
        'rate_limit_per_minute',
        'daily_message_limit',
        'account_id',
        'account_name',
        'account_description',
    ];

    protected function casts(): array
    {
        return [
            'credentials'   => 'encrypted:array',
            'settings'      => 'array',
            'is_enabled'    => 'boolean',
            'is_default'    => 'boolean',
            'last_message_at' => 'datetime',
            'webhook_tested_at' => 'datetime',
        ];
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class, 'channel_config_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ChannelContact::class, 'widget_id', 'widget_id')
            ->where('channel_type', $this->channel_type);
    }

    /**
     * Get the channel service handler for this configuration
     */
    public function getChannelService(): mixed
    {
        return match ($this->channel_type) {
            'email'     => app(\App\Services\Channels\EmailChannelService::class),
            'whatsapp'  => app(\App\Services\Channels\WhatsAppChannelService::class),
            'facebook'  => app(\App\Services\Channels\FacebookChannelService::class),
            'instagram' => app(\App\Services\Channels\InstagramChannelService::class),
            'twitter'   => app(\App\Services\Channels\TwitterChannelService::class),
            'telegram'  => app(\App\Services\Channels\TelegramChannelService::class),
            'slack'     => app(\App\Services\Channels\SlackChannelService::class),
            'discord'   => app(\App\Services\Channels\DiscordChannelService::class),
            'sms'       => app(\App\Services\Channels\SmsChannelService::class),
            default     => app(\App\Services\Channels\WebChannelService::class),
        };
    }

    /**
     * Check if channel has reached daily limit
     */
    public function hasReachedDailyLimit(): bool
    {
        if (!$this->daily_message_limit) return false;
        return $this->messages_today >= $this->daily_message_limit;
    }

    /**
     * Increment daily message counter
     */
    public function incrementDailyCount(): void
    {
        if ($this->created_at->isToday()) {
            $this->increment('messages_today');
        } else {
            $this->update(['messages_today' => 1]);
        }
    }

    /**
     * Test webhook connectivity
     */
    public function testWebhook(): bool
    {
        if (!$this->webhook_url) return false;

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->post($this->webhook_url, [
                    'event' => 'webhook.test',
                    'timestamp' => now()->toIso8601String(),
                    'widget_uuid' => $this->widget->uuid,
                ]);

            if ($response->successful()) {
                $this->update(['webhook_tested_at' => now()]);
                return true;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Channel webhook test failed: {$e->getMessage()}");
        }

        return false;
    }
}
