<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Admin-controlled cookie consent banner settings, stored as one JSON system setting. */
class CookieConsent
{
    public const KEY = 'cookie_consent';

    public const LAYOUTS = [
        'bar'       => ['Full-width bar', 'A slim bar across the bottom of the screen. Least intrusive.'],
        'box-left'  => ['Corner box (left)', 'A compact card in the bottom-left corner.'],
        'box-right' => ['Corner box (right)', 'A compact card in the bottom-right corner. Keep clear of the chat launcher.'],
        'modal'     => ['Centre modal', 'A dialog in the middle of the screen that dims the page. Most visible.'],
    ];

    public const DEFAULTS = [
        'enabled'         => true,
        'layout'          => 'box-left',
        'title'           => 'We value your privacy',
        'message'         => 'We use essential cookies to make this site work. With your permission, we would also like to use analytics cookies to understand how the site is used and improve it.',
        'accept_label'    => 'Accept all',
        'reject_label'    => 'Reject non-essential',
        'customize_label' => 'Customise',
        'show_reject'     => true,
        'show_customize'  => true,
        'analytics'       => true,
        'marketing'       => false,
        'block_page'      => false,
        'version'         => 1,
    ];

    public static function get(): array
    {
        return Cache::rememberForever(self::KEY, function () {
            try {
                $saved = Schema::hasTable('system_settings') ? SystemSetting::get(self::KEY, []) : [];
            } catch (\Throwable) {
                $saved = [];
            }

            return array_merge(self::DEFAULTS, is_array($saved) ? $saved : []);
        });
    }

    public static function save(array $settings): void
    {
        SystemSetting::set(self::KEY, array_merge(self::DEFAULTS, $settings), 'json', 'consent');
        Cache::forget(self::KEY);
    }
}
