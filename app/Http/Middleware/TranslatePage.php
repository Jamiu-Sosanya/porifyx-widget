<?php

namespace App\Http\Middleware;

use App\Support\I18n;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows pages in the visitor's chosen language. Only exact interface strings from the dictionary are
 * swapped, so customer data (messages, names, leads) is never changed or sent anywhere.
 * Scripts, styles, textareas, <pre> and <!--notranslate--> blocks are left untouched.
 */
class TranslatePage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $locale = I18n::current();
        if ($locale === 'en' || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html') || ! method_exists($response, 'getContent')
            || $response->getStatusCode() >= 300 || $request->is('api/*')) {
            return $response;
        }

        I18n::ensure($locale);
        $html = (string) $response->getContent();
        $html = preg_replace('/<html\b([^>]*)\blang="[^"]*"/i', '<html$1lang="' . e($locale) . '"' . (in_array($locale, I18n::RTL, true) ? ' dir="rtl"' : ''), $html, 1);
        $dict = I18n::dict($locale);
        if ($dict) $response->setContent(self::translate($html, $dict));
        else $response->setContent($html);

        return $response;
    }

    public static function translate(string $html, array $dict): string
    {
        $parts = preg_split('/(<script\b.*?<\/script>|<style\b.*?<\/style>|<textarea\b.*?<\/textarea>|<pre\b.*?<\/pre>|<!--notranslate-->.*?<!--\/notranslate-->)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $p) {
            if ($i % 2 === 1) continue; // protected block
            $p = preg_replace_callback('/>([^<>]+)</u', function ($m) use ($dict) {
                $key = trim(preg_replace('/\s+/u', ' ', html_entity_decode($m[1], ENT_QUOTES)));
                $prefix = '';
                if (! isset($dict[$key])) {
                    // "📤 Export", "✓ Resolve": keep the emoji/symbol, translate the words.
                    if (! preg_match('/^([^\p{L}\p{N}]+)(\p{L}.*)$/u', $key, $pm) || ! isset($dict[$pm[2]])) return $m[0];
                    [$prefix, $key] = [$pm[1], $pm[2]];
                }
                preg_match('/^\s*/u', $m[1], $lead);
                preg_match('/\s*$/u', $m[1], $trail);

                return '>' . $lead[0] . e($prefix . $dict[$key]) . $trail[0] . '<';
            }, $p);
            $parts[$i] = preg_replace_callback('/(\s(?:placeholder|title|aria-label)=")([^"]*)(")/u', function ($m) use ($dict) {
                $key = trim(html_entity_decode($m[2], ENT_QUOTES));

                return isset($dict[$key]) ? $m[1] . e($dict[$key]) . $m[3] : $m[0];
            }, $p);
        }

        return implode('', $parts);
    }
}
