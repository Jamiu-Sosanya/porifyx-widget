<?php

namespace App\Services;

class EnvWriter
{
    private const CONFIG_TO_ENV = [
        'app.name'                       => 'APP_NAME',
        'app.url'                        => 'APP_URL',
        'app.debug'                      => 'APP_DEBUG',
        'app.timezone'                   => 'APP_TIMEZONE',
        'app.force_2fa_admin'            => 'FORCE_2FA_ADMIN',
        'app.email_verification_required'=> 'EMAIL_VERIFICATION_REQUIRED',
        'branding.tagline'               => 'BRAND_TAGLINE',
        'branding.description'           => 'BRAND_DESCRIPTION',
        'branding.support_email'         => 'BRAND_SUPPORT_EMAIL',
        'branding.support_url'           => 'BRAND_SUPPORT_URL',
        'branding.docs_url'              => 'BRAND_DOCS_URL',
        'branding.privacy_url'           => 'BRAND_PRIVACY_URL',
        'branding.terms_url'             => 'BRAND_TERMS_URL',
        'branding.primary_color'         => 'BRAND_PRIMARY_COLOR',
        'branding.logo_url'              => 'BRAND_LOGO_URL',
        'branding.favicon_url'           => 'BRAND_FAVICON_URL',
        'branding.social_proof'          => 'BRAND_SOCIAL_PROOF',
        'branding.social_proof_text'     => 'BRAND_SOCIAL_PROOF_TEXT',
        'branding.twitter_url'           => 'BRAND_TWITTER_URL',
        'branding.linkedin_url'          => 'BRAND_LINKEDIN_URL',
        'branding.copyright'             => 'BRAND_COPYRIGHT',
        'branding.landing_enabled'       => 'LANDING_PAGE_ENABLED',
        'branding.admin_ip_whitelist'    => 'ADMIN_IP_WHITELIST',
        'branding.trial_days'            => 'TRIAL_DAYS',
        'ai.openai.key'                  => 'OPENAI_API_KEY',
        'ai.openai.model'                => 'OPENAI_MODEL',
        'ai.openai.embedding_model'      => 'OPENAI_EMBEDDING_MODEL',
        'ai.anthropic.key'               => 'ANTHROPIC_API_KEY',
        'ai.anthropic.model'             => 'ANTHROPIC_MODEL',
        'ai.gemini.key'                  => 'GEMINI_API_KEY',
        'ai.gemini.model'                => 'GEMINI_MODEL',
        'ai.groq.key'                    => 'GROQ_API_KEY',
        'ai.groq.model'                  => 'GROQ_MODEL',
        'ai.openrouter.key'              => 'OPENROUTER_API_KEY',
        'ai.openrouter.model'            => 'OPENROUTER_MODEL',
        'ai.fallback_provider'           => 'AI_FALLBACK_PROVIDER',
        'mail.default'                   => 'MAIL_MAILER',
        'mail.mailers.smtp.host'         => 'MAIL_HOST',
        'mail.mailers.smtp.port'         => 'MAIL_PORT',
        'mail.mailers.smtp.encryption'   => 'MAIL_ENCRYPTION',
        'mail.mailers.smtp.username'     => 'MAIL_USERNAME',
        'mail.mailers.smtp.password'     => 'MAIL_PASSWORD',
        'mail.from.address'              => 'MAIL_FROM_ADDRESS',
        'mail.from.name'                 => 'MAIL_FROM_NAME',
        'services.stripe.key'            => 'STRIPE_KEY',
        'services.stripe.secret'         => 'STRIPE_SECRET',
        'services.stripe.webhook_secret' => 'STRIPE_WEBHOOK_SECRET',
        'session.lifetime'               => 'SESSION_LIFETIME',
        'hashing.bcrypt.rounds'          => 'BCRYPT_ROUNDS',
        'sentry.dsn'                     => 'SENTRY_LARAVEL_DSN',
    ];

    public static function fromConfigKey(string $configKey): ?string
    {
        return self::CONFIG_TO_ENV[$configKey] ?? null;
    }

    public static function write(string $envKey, string $value): void
    {
        self::writeBatch([[$envKey, $value]]);
    }

    /**
     * Write multiple key/value pairs to .env in a single file read/write cycle.
     * Pass an array of [$envKey, $value] pairs.
     */
    public static function writeBatch(array $pairs): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($pairs as [$key, $value]) {
            $content = self::updateLine($content, $key, $value);
        }

        file_put_contents($envPath, $content);
    }

    /**
     * Delete the compiled config cache so .env changes take effect on the next
     * request without calling Artisan (which boots a second kernel instance and
     * can corrupt the current request's session state).
     */
    public static function clearConfigCache(): void
    {
        $cache = base_path('bootstrap/cache/config.php');
        if (file_exists($cache)) {
            @unlink($cache);
        }
    }

    private static function updateLine(string $content, string $key, string $value): string
    {
        if ($value !== '' && preg_match('/[\s#"\']/', $value)) {
            $formatted = "{$key}=\"{$value}\"";
        } else {
            $formatted = "{$key}={$value}";
        }

        $pattern = "/^{$key}=.*$/m";

        if (preg_match($pattern, $content)) {
            return preg_replace_callback($pattern, fn () => $formatted, $content);
        }

        return rtrim($content) . "\n{$formatted}\n";
    }
}
