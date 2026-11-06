<?php

namespace App\Support;

/** SSRF guard: only public internet addresses (no localhost, private networks or cloud metadata). */
class SafeUrl
{
    public static function isPublic(string $url, bool $allowHttp = true): bool
    {
        if (! preg_match($allowHttp ? '#^https?://#i' : '#^https://#i', $url)) return false;
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) return false;
        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (! $ips) return false;
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
        }

        return true;
    }

    /** Guzzle options that re-check every redirect hop. */
    public static function httpOptions(): array
    {
        return ['allow_redirects' => ['max' => 5, 'on_redirect' => function ($req, $res, $uri) {
            if (! self::isPublic((string) $uri)) throw new \RuntimeException('Blocked redirect to a private address.');
        }]];
    }
}
