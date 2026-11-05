<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'group', 'config_key',
        'label', 'description', 'is_public', 'is_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'is_public'    => 'boolean',
            'is_sensitive' => 'boolean',
        ];
    }

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'integer' => (int)  $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    public static function set(
        string $key,
        mixed  $value,
        string $type   = 'string',
        string $group  = 'general',
        ?string $configKey = null,
    ): void {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value'      => is_array($value) ? json_encode($value) : (string) $value,
                'type'       => $type,
                'group'      => $group,
                'config_key' => $configKey,
            ]
        );
    }
}
