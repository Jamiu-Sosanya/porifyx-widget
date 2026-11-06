<?php

namespace App\Support;

use App\Models\Widget;

/** Launcher button icons: built-in presets or an uploaded image. */
class LauncherIcons
{
    /** Upload rules shown to users and enforced on save. */
    public const UPLOAD_RULE = 'image|mimes:jpg,jpeg,png|max:512|dimensions:min_width=64,min_height=64,max_width=512,max_height=512,ratio=1';
    public const UPLOAD_HINT = 'JPG or PNG, square, 64×64 to 512×512 pixels, up to 512 KB. 256×256 looks sharpest.';

    /** Chat window and launcher sizes that work on most sites and phones. */
    public const RECOMMENDED = ['chat_w' => 380, 'chat_h' => 600, 'm_mode' => 'full', 'm_w' => 94, 'm_h' => 85, 'm_size' => 56];

    /** Message bubbles and typing indicator. Empty colours follow the chat theme. */
    public const BUBBLES = ['bubble_shape' => 'rounded', 'bubble_tail' => true, 'ai_bg' => '', 'ai_text' => '', 'user_bg' => '', 'user_text' => '#ffffff', 'bubble_size' => 13, 'typing_style' => 'dots'];

    public const ANIMATIONS = ['none' => 'None', 'pulse' => 'Pulse', 'ping' => 'Ping', 'glow' => 'Glow', 'bounce' => 'Bounce', 'float' => 'Float', 'wiggle' => 'Wiggle', 'shake' => 'Shake', 'heartbeat' => 'Heartbeat'];

    public const KEYFRAMES = '@keyframes pfxPulse{0%{box-shadow:0 0 0 0 var(--pfx-ring)}70%{box-shadow:0 0 0 16px transparent}100%{box-shadow:0 0 0 0 transparent}}@keyframes pfxPing{0%{box-shadow:0 0 0 0 var(--pfx-ring)}30%{box-shadow:0 0 0 14px transparent}100%{box-shadow:0 0 0 0 transparent}}@keyframes pfxGlow{from{box-shadow:0 0 6px 0 var(--pfx-ring)}to{box-shadow:0 0 26px 6px var(--pfx-ring)}}@keyframes pfxBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}@keyframes pfxFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}@keyframes pfxWiggle{0%,30%,100%{transform:rotate(0)}6%,18%{transform:rotate(-10deg)}12%,24%{transform:rotate(10deg)}}@keyframes pfxShake{0%,25%,100%{transform:translateX(0)}5%,15%{transform:translateX(-4px)}10%,20%{transform:translateX(4px)}}@keyframes pfxHeart{0%,40%,100%{transform:scale(1)}10%,30%{transform:scale(1.12)}20%{transform:scale(.97)}}.pfx-anim-pulse{animation:pfxPulse 2.2s infinite}.pfx-anim-ping{animation:pfxPing 3s infinite}.pfx-anim-glow{animation:pfxGlow 1.6s ease-in-out infinite alternate}.pfx-anim-bounce{animation:pfxBounce 2s ease-in-out infinite}.pfx-anim-float{animation:pfxFloat 3s ease-in-out infinite}.pfx-anim-wiggle{animation:pfxWiggle 2.6s ease-in-out infinite}.pfx-anim-shake{animation:pfxShake 3s ease-in-out infinite}.pfx-anim-heartbeat{animation:pfxHeart 1.8s ease-in-out infinite}@media(prefers-reduced-motion:reduce){[class*=pfx-anim-]{animation:none!important}}';

    /** key => [label, SVG body drawn in white on a 24×24 grid] */
    public const PRESETS = [
        'default'   => ['Chat spark', '<path d="M5 3h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-8l-5 4v-4H5a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z" fill="white" fill-opacity=".95"/><path d="M12 6.5l1.2 2.8 2.8 1.2-2.8 1.2L12 14.5l-1.2-2.8L8 10.5l2.8-1.2z" fill="currentColor"/>'],
        'bubble'    => ['Speech bubble', '<path d="M12 3C6.5 3 2 6.6 2 11c0 2.4 1.3 4.6 3.4 6.1L4.5 21l4.3-2.3c1 .2 2.1.3 3.2.3 5.5 0 10-3.6 10-8s-4.5-8-10-8z" fill="white"/>'],
        'dots'      => ['Typing dots', '<path d="M4 4h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-7l-5 4v-4H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" fill="white"/><circle cx="8" cy="11" r="1.4" fill="currentColor"/><circle cx="12" cy="11" r="1.4" fill="currentColor"/><circle cx="16" cy="11" r="1.4" fill="currentColor"/>'],
        'question'  => ['Help', '<circle cx="12" cy="12" r="10" fill="white"/><path d="M9.5 9.2a2.6 2.6 0 0 1 5 .9c0 1.7-2.5 2.2-2.5 3.6M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>'],
        'headset'   => ['Support agent', '<path d="M4 13v-1a8 8 0 0 1 16 0v1" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/><rect x="3" y="12" width="4" height="7" rx="2" fill="white"/><rect x="17" y="12" width="4" height="7" rx="2" fill="white"/><path d="M19 19c0 1.5-2 2.5-5 2.5" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>'],
        'robot'     => ['Assistant', '<rect x="4" y="7" width="16" height="12" rx="4" fill="white"/><circle cx="9" cy="13" r="1.6" fill="currentColor"/><circle cx="15" cy="13" r="1.6" fill="currentColor"/><path d="M12 3v4M9 17h6" stroke="white" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="3" r="1.3" fill="white"/>'],
        'calendar'  => ['Bookings', '<rect x="3" y="5" width="18" height="16" rx="3" fill="white"/><path d="M8 3v4M16 3v4" stroke="white" stroke-width="2" stroke-linecap="round"/><path d="M3 10h18" stroke="currentColor" stroke-width="1.6"/><path d="m9 15 2 2 4-4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'],
        'heart'     => ['Care', '<path d="M12 21s-8-5.2-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.8-8 11-8 11z" fill="white"/>'],
        'cart'      => ['Shop', '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H6.2" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.6" fill="white"/><circle cx="17" cy="20" r="1.6" fill="white"/>'],
        'wave'      => ['Hello', '<path d="M7 11V6.5a1.5 1.5 0 0 1 3 0V11m0-1V4.5a1.5 1.5 0 0 1 3 0V10m0-.5V5.5a1.5 1.5 0 0 1 3 0V12m0-3.5a1.5 1.5 0 0 1 3 0V14a7 7 0 0 1-12.4 4.4L4.2 14.6a1.6 1.6 0 0 1 2.5-2l.3.4" stroke="white" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'],
    ];

    public static function defaults(Widget $widget): array
    {
        return [
            'size' => 60, 'radius' => 18, 'bg_type' => 'solid', 'bg' => $widget->primary_color ?: '#ff6a2b',
            'bg2' => $widget->secondary_color && $widget->secondary_color !== '#ffffff' ? $widget->secondary_color : '#8fb4e3',
            'angle' => 135, 'icon_color' => '#ffffff', 'border_width' => 0, 'border_color' => '#ffffff',
            'shadow' => 'medium', 'offset_x' => 20, 'offset_y' => 20, 'label' => '', 'animation' => 'none',
        ] + self::RECOMMENDED + self::BUBBLES;
    }

    public static function style(Widget $widget): array
    {
        return array_replace(self::defaults($widget), (array) $widget->launcher);
    }

    public static function svg(string $key, int $size = 30, string $color = '#ffffff'): string
    {
        $body = str_replace('"white"', '"' . e($color) . '"', (self::PRESETS[$key] ?? self::PRESETS['default'])[1]);

        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" aria-hidden="true">' . $body . '</svg>';
    }

    /** What the chat widget needs to draw the launcher. */
    public static function forWidget(Widget $widget): array
    {
        if ($widget->launcher_icon === 'custom' && $widget->launcher_icon_path) {
            return ['type' => 'image', 'url' => asset('storage/' . $widget->launcher_icon_path)];
        }

        return ['type' => 'svg', 'svg' => self::svg($widget->launcher_icon ?: 'default', 30, self::style($widget)['icon_color'])];
    }
}
