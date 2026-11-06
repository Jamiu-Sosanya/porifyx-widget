<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Interface translation for 133 languages.
 * Source strings are extracted from the Blade views (php artisan i18n:extract -> lang/ui-source.json) plus the widget's
 * own strings. Translations are generated once per language by the admin-chosen AI (i18n:translate / queued job),
 * stored in `translations`, editable in Admin > Translations, and applied to pages by the TranslatePage middleware.
 */
class I18n
{
    public const LOCALES = [
        'en' => 'English', 'af' => 'Afrikaans', 'sq' => 'Albanian', 'am' => 'Amharic', 'ar' => 'العربية', 'hy' => 'Armenian', 'as' => 'Assamese', 'ay' => 'Aymara', 'az' => 'Azərbaycan', 'bm' => 'Bambara',
        'eu' => 'Euskara', 'be' => 'Беларуская', 'bn' => 'বাংলা', 'bho' => 'Bhojpuri', 'bs' => 'Bosanski', 'bg' => 'Български', 'ca' => 'Català', 'ceb' => 'Cebuano', 'ny' => 'Chichewa', 'zh' => '中文 (简体)',
        'zh-TW' => '中文 (繁體)', 'co' => 'Corsu', 'hr' => 'Hrvatski', 'cs' => 'Čeština', 'da' => 'Dansk', 'dv' => 'Dhivehi', 'doi' => 'Dogri', 'nl' => 'Nederlands', 'eo' => 'Esperanto', 'et' => 'Eesti',
        'ee' => 'Ewe', 'fil' => 'Filipino', 'fi' => 'Suomi', 'fr' => 'Français', 'fy' => 'Frysk', 'gl' => 'Galego', 'ka' => 'ქართული', 'de' => 'Deutsch', 'el' => 'Ελληνικά', 'gn' => 'Guarani',
        'gu' => 'ગુજરાતી', 'ht' => 'Kreyòl ayisyen', 'ha' => 'Hausa', 'haw' => 'Hawaiian', 'he' => 'עברית', 'hi' => 'हिन्दी', 'hmn' => 'Hmong', 'hu' => 'Magyar', 'is' => 'Íslenska', 'ig' => 'Igbo',
        'ilo' => 'Ilocano', 'id' => 'Bahasa Indonesia', 'ga' => 'Gaeilge', 'it' => 'Italiano', 'ja' => '日本語', 'jv' => 'Basa Jawa', 'kn' => 'ಕನ್ನಡ', 'kk' => 'Қазақ', 'km' => 'ខ្មែរ', 'rw' => 'Kinyarwanda',
        'gom' => 'Konkani', 'ko' => '한국어', 'kri' => 'Krio', 'ku' => 'Kurdî', 'ckb' => 'کوردی', 'ky' => 'Кыргызча', 'lo' => 'ລາວ', 'la' => 'Latina', 'lv' => 'Latviešu', 'ln' => 'Lingála',
        'lt' => 'Lietuvių', 'lg' => 'Luganda', 'lb' => 'Lëtzebuergesch', 'mk' => 'Македонски', 'mai' => 'Maithili', 'mg' => 'Malagasy', 'ms' => 'Bahasa Melayu', 'ml' => 'മലയാളം', 'mt' => 'Malti', 'mi' => 'Māori',
        'mr' => 'मराठी', 'mni' => 'Meitei', 'lus' => 'Mizo', 'mn' => 'Монгол', 'my' => 'မြန်မာ', 'ne' => 'नेपाली', 'no' => 'Norsk', 'or' => 'ଓଡ଼ିଆ', 'om' => 'Oromoo', 'ps' => 'پښتو',
        'fa' => 'فارسی', 'pl' => 'Polski', 'pt' => 'Português', 'pt-BR' => 'Português (Brasil)', 'pa' => 'ਪੰਜਾਬੀ', 'qu' => 'Runasimi', 'ro' => 'Română', 'ru' => 'Русский', 'sm' => 'Samoan', 'sa' => 'संस्कृतम्',
        'gd' => 'Gàidhlig', 'nso' => 'Sepedi', 'sr' => 'Српски', 'st' => 'Sesotho', 'sn' => 'Shona', 'sd' => 'سنڌي', 'si' => 'සිංහල', 'sk' => 'Slovenčina', 'sl' => 'Slovenščina', 'so' => 'Soomaali',
        'es' => 'Español', 'su' => 'Basa Sunda', 'sw' => 'Kiswahili', 'sv' => 'Svenska', 'tg' => 'Тоҷикӣ', 'ta' => 'தமிழ்', 'tt' => 'Татар', 'te' => 'తెలుగు', 'th' => 'ไทย', 'ti' => 'ትግርኛ',
        'ts' => 'Xitsonga', 'tr' => 'Türkçe', 'tk' => 'Türkmen', 'ak' => 'Twi', 'uk' => 'Українська', 'ur' => 'اردو', 'ug' => 'ئۇيغۇرچە', 'uz' => 'Oʻzbek', 'vi' => 'Tiếng Việt', 'cy' => 'Cymraeg',
        'xh' => 'isiXhosa', 'yi' => 'ייִדיש', 'yo' => 'Yorùbá', 'zu' => 'isiZulu',
    ];

    public const RTL = ['ar', 'he', 'fa', 'ur', 'ps', 'sd', 'ckb', 'ug', 'yi', 'dv'];

    /** Strings the widget shows to visitors (translated per visitor language). */
    public const WIDGET = [
        'Type a message...', 'Send', 'Online', 'Talk to a human', 'Speak your question', 'Listening…', 'Recording… tap the mic to send', 'Transcribing…',
        'Searching our knowledge…', 'Thinking…', 'Sources:', 'Accept', 'Decline', 'Privacy policy', 'Not now', 'Thank you!', 'Please fill in:',
        'Leave us a message and we\'ll get back to you shortly.', 'Your name (optional)', 'Your email address *', 'Your message *', 'Send Message',
        'How helpful was this chat?', 'Dismiss', 'Sorry, something went wrong. Please try again.', 'Powered by',
        'Thanks for stopping by! Our assistant is not available right now. Leave your name, email and message below and the team will get back to you.',
    ];

    public static function normalize(?string $code): ?string
    {
        if (! $code) return null;
        foreach ([$code, str_replace('_', '-', $code), strtolower(substr($code, 0, 2))] as $c) {
            if (isset(self::LOCALES[$c])) return $c;
        }

        return null;
    }

    /** Language for the current web request: ?lang, cookie, the person's profile, else English. */
    public static function current(): string
    {
        $r = request();
        if ($l = self::normalize($r->query('lang'))) return $l;
        if ($l = self::normalize($r->cookie('pfx_lang'))) return $l;
        $u = Workspace::realUser();

        return self::normalize($u?->locale) ?? 'en';
    }

    public static function sources(): array
    {
        static $s;
        if ($s !== null) return $s;
        $file = base_path('lang/ui-source.json');
        $ui = is_file($file) ? (array) json_decode(file_get_contents($file), true) : [];

        return $s = array_values(array_unique(array_merge($ui, self::WIDGET)));
    }

    /** source => translation for a language (cached; cleared when translations change). */
    public static function dict(string $locale): array
    {
        if ($locale === 'en') return [];

        return Cache::rememberForever('i18n:' . $locale, fn () => DB::table('translations')->where('locale', $locale)->pluck('value', 'source')->all());
    }

    public static function forget(string $locale): void
    {
        Cache::forget('i18n:' . $locale);
    }

    /** A language that is not fully translated yet: translate in the background. */
    public static function ensure(string $locale): void
    {
        if ($locale === 'en' || Cache::has('i18n:queued:' . $locale)) return;
        if (DB::table('translations')->where('locale', $locale)->count() >= count(self::sources())) return; // complete
        Cache::put('i18n:queued:' . $locale, true, 3600);
        \App\Jobs\TranslateLocale::dispatch($locale)->afterResponse();
    }

    public static function t(string $text, ?string $locale = null): string
    {
        $locale ??= self::current();

        return self::dict($locale)[$text] ?? $text;
    }
}
