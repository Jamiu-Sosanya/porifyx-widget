<?php

namespace App\Support;

use App\Models\SystemSetting;

/** Look of the public marketing site, chosen in Admin > Settings > Branding. */
class MarketingTheme
{
    /** key => [label, forced mode or null, hero CSS] */
    public const THEMES = [
        'aurora'   => ['Aurora (follows visitor light/dark)', null, ''],
        'midnight' => ['Midnight (always dark)', 'dark', '.pfx-hero{background:radial-gradient(900px 500px at 80% -10%,rgba(143,180,227,.18),transparent 60%),radial-gradient(700px 400px at 0% 20%,rgba(255,106,43,.12),transparent 60%)}'],
        'daylight' => ['Daylight (always light)', 'light', '.pfx-hero{background:linear-gradient(180deg,#fff 0%,#f6f7fb 100%)}'],
        'sunset'   => ['Sunset (warm, dark)', 'dark', '.pfx-hero{background:linear-gradient(160deg,#ff6a2b 0%,#7d2b12 35%,#0d1a33 75%)}.pfx-hero .pfx-grid{opacity:.25}'],
        'prism'    => ['Prism (bright, light)', 'light', '.pfx-hero{background:radial-gradient(600px 400px at 10% 10%,rgba(255,106,43,.22),transparent 60%),radial-gradient(600px 400px at 90% 0%,rgba(143,180,227,.45),transparent 60%),radial-gradient(500px 300px at 50% 100%,rgba(167,139,250,.25),transparent 60%),#fbfcff}'],
    ];

    public static function current(): string
    {
        $t = (string) SystemSetting::get('marketing_theme', 'aurora');

        return isset(self::THEMES[$t]) ? $t : 'aurora';
    }

    public static function forced(): ?string
    {
        return self::THEMES[self::current()][1];
    }

    public static function css(): string
    {
        return self::THEMES[self::current()][2];
    }

    public static function hero(): array
    {
        return [
            'title'     => SystemSetting::get('hero_title') ?: 'Turn website visitors into',
            'highlight' => SystemSetting::get('hero_highlight') ?: 'customers, 24/7.',
            'subtitle'  => SystemSetting::get('hero_subtitle') ?: config('branding.description'),
        ];
    }
}
