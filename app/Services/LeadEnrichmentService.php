<?php

namespace App\Services;

use App\Models\Lead;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Free lead enrichment from the email domain: personal vs business email, and for business
 * domains the company website's name, description, logo and social profiles. No paid API.
 */
class LeadEnrichmentService
{
    public const FREE_MAIL = ['gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.uk', 'hotmail.com', 'hotmail.co.uk', 'outlook.com', 'live.com', 'msn.com', 'icloud.com', 'me.com', 'aol.com',
        'proton.me', 'protonmail.com', 'gmx.com', 'gmx.de', 'mail.com', 'yandex.com', 'yandex.ru', 'zoho.com', 'qq.com', '163.com', 'web.de', 'btinternet.com', 'sky.com', 'virginmedia.com'];

    public function enrich(Lead $lead): ?array
    {
        $domain = strtolower((string) substr(strrchr((string) $lead->email, '@') ?: '', 1));
        if ($domain === '') return null;

        $data = ['domain' => $domain, 'type' => in_array($domain, self::FREE_MAIL, true) ? 'personal' : 'business', 'enriched_at' => now()->toIso8601String()];
        if ($data['type'] === 'business') $data += $this->company($domain);

        $lead->enrichment = $data;
        if (blank($lead->company) && filled($data['company'] ?? null)) $lead->company = $data['company'];
        if (blank($lead->website) && $data['type'] === 'business') $lead->website = 'https://' . $domain;
        $lead->save();

        return $data;
    }

    /** Company details from the domain's home page (cached for a week). */
    public function company(string $domain): array
    {
        return Cache::remember('enrich:' . $domain, 604800, function () use ($domain) {
            $url = 'https://' . $domain;
            if (! SafeUrl::isPublic($url)) return [];
            try {
                $res = Http::timeout(8)->withOptions(SafeUrl::httpOptions())->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; PoriFyxBot/1.0)'])->get($url);
                if (! $res->successful()) return [];
                $html = substr($res->body(), 0, 400000);
            } catch (\Throwable) {
                return [];
            }

            return $this->parse($html, $domain);
        });
    }

    /** Company name, description, logo and social links from a home page. */
    public function parse(string $html, string $domain): array
    {
            $meta = function (string $name) use ($html) {
                return preg_match('#<meta[^>]+(?:property|name)=["\']' . preg_quote($name, '#') . '["\'][^>]+content=["\']([^"\']+)#i', $html, $m)
                    || preg_match('#<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']' . preg_quote($name, '#') . '["\']#i', $html, $m)
                    ? trim(html_entity_decode($m[1], ENT_QUOTES)) : null;
            };
            preg_match('#<title[^>]*>(.*?)</title>#is', $html, $t);
            $title = trim(html_entity_decode($t[1] ?? '', ENT_QUOTES));
            $name = $meta('og:site_name') ?: trim(preg_split('/\s[|\-–—:·]\s/u', $title)[0] ?? '') ?: ucfirst(explode('.', $domain)[0]);
            preg_match_all('~href=["\'](https?://(?:www\.)?(?:linkedin\.com/company|twitter\.com|x\.com|facebook\.com|instagram\.com)/[^"\'\s?#]+)~i', $html, $s);

            return array_filter([
                'company'     => mb_substr($name, 0, 120),
                'description' => mb_substr((string) ($meta('og:description') ?: $meta('description')), 0, 300) ?: null,
                'logo'        => 'https://www.google.com/s2/favicons?sz=64&domain=' . $domain,
                'socials'     => array_values(array_unique(array_slice($s[1] ?? [], 0, 4))) ?: null,
            ]);
    }
}
