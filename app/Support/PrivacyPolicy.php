<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Str;

/** Admin-editable privacy policy and GDPR settings (Admin > Privacy & GDPR), stored as one JSON setting. */
class PrivacyPolicy
{
    public const DEFAULTS = [
        'enabled'          => false,   // false = use the built-in policy page
        'eyebrow'          => 'Privacy & GDPR',
        'title'            => 'Privacy policy',
        'effective_date'   => '',
        'summary'          => '',
        'contact_team'     => 'Privacy team',
        'contact_email'    => '',
        'response_promise' => 'We reply to privacy requests within 30 days.',
        'collection'       => '',
        'rights'           => '',
        'retention_days'   => 0,       // auto-delete visitor conversations older than this (0 = keep)
        'request_form'     => true,    // public "make a privacy request" form on the policy page
    ];

    public static function get(): array
    {
        $v = SystemSetting::get('privacy_page');
        $v = is_array($v) ? $v : (json_decode((string) $v, true) ?: []);

        return array_merge(self::DEFAULTS, $v);
    }

    public static function save(array $values): void
    {
        SystemSetting::updateOrCreate(['key' => 'privacy_page'], ['label' => 'Privacy page', 'type' => 'json', 'group' => 'privacy', 'value' => json_encode(array_merge(self::get(), $values))]);
        \Illuminate\Support\Facades\Cache::flush();
    }

    public static function html(string $markdown): string
    {
        return Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}
