<?php

namespace App\Console\Commands;

use App\Jobs\TranslateLocale;
use App\Support\I18n;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * php artisan i18n:extract            collect fixed interface text from all Blade views into lang/ui-source.json
 * php artisan i18n:translate fr de    translate missing strings now (all = every language)
 */
class I18nCommands extends Command
{
    protected $signature = 'i18n:run {action : extract|translate} {locales?*}';
    protected $description = 'Extract interface strings or translate them with the admin-chosen AI';

    public function handle(): int
    {
        if ($this->argument('action') === 'extract') {
            $strings = [];
            foreach (File::allFiles(resource_path('views')) as $f) {
                if (! str_ends_with($f->getFilename(), '.blade.php') || str_contains($f->getPathname(), DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR)) continue;
                foreach (self::extract(file_get_contents($f->getPathname())) as $s) $strings[$s] = true;
            }
            ksort($strings);
            File::put(base_path('lang/ui-source.json'), json_encode(array_keys($strings), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->info(count($strings) . ' strings written to lang/ui-source.json');

            return self::SUCCESS;
        }

        $locales = $this->argument('locales') ?: [];
        if ($locales === ['all']) $locales = array_keys(I18n::LOCALES);
        foreach ($locales as $l) {
            if ($l = I18n::normalize($l)) {
                $this->info("Translating {$l}…");
                (new TranslateLocale($l))->handle();
            }
        }

        return self::SUCCESS;
    }

    /** Static text between tags and in placeholder/title/aria-label attributes (skips Blade/PHP/JS). */
    public static function extract(string $blade): array
    {
        $b = preg_replace(['/\{\{--.*?--\}\}/s', '/@php.*?@endphp/s', '/<script\b.*?<\/script>/is', '/<style\b.*?<\/style>/is', '/<svg\b.*?<\/svg>/is'], ' ', $blade);
        $out = [];
        preg_match_all('/>([^<>]+)</', $b, $m);
        preg_match_all('/\s(?:placeholder|title|aria-label)="([^"{}]+)"/', $b, $a);
        foreach (array_merge($m[1], $a[1]) as $s) {
            $s = trim(preg_replace('/\s+/u', ' ', html_entity_decode($s, ENT_QUOTES)));
            if (mb_strlen($s) < 2 || mb_strlen($s) > 300 || ! preg_match('/\p{L}{2,}/u', $s)) continue;
            if (preg_match('/[{}$@]|::|=>|->|\bx-|&&|\|\||\]\)|\[\x27|\x27\]|\);|\(\x27/', $s)) continue; // Blade, PHP or JS fragments
            $out[] = $s;
            if (preg_match('/^[^\p{L}\p{N}]+(\p{L}.*)$/u', $s, $pm)) $out[] = $pm[1]; // also the words without a leading emoji
        }

        return $out;
    }
}
