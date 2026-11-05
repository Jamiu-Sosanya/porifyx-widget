<?php

/**
 * Branding & customisation config.
 * All values read from .env so buyers can white-label without touching PHP.
 */
return [
    'name'             => env('APP_NAME', 'PoriFyx Widget'),
    'tagline'          => env('BRAND_TAGLINE', 'AI chat that sells, supports and qualifies — on autopilot'),
    'description'      => env('BRAND_DESCRIPTION', 'Train your AI on your business data, embed it on your website, and watch it capture leads, answer questions, and convert visitors — 24/7.'),
    'support_email'    => env('BRAND_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'info@porifyx.com')),
    'support_url'      => env('BRAND_SUPPORT_URL', env('APP_URL', 'http://localhost') . '/support'),
    'docs_url'         => env('BRAND_DOCS_URL', env('APP_URL', 'http://localhost') . '/docs'),
    'privacy_url'      => env('BRAND_PRIVACY_URL', env('APP_URL', 'http://localhost') . '/privacy'),
    'terms_url'        => env('BRAND_TERMS_URL', env('APP_URL', 'http://localhost') . '/terms'),
    'social_proof'     => env('BRAND_SOCIAL_PROOF', '2,400+'),
    'social_proof_text'=> env('BRAND_SOCIAL_PROOF_TEXT', 'businesses trust us'),
    'trial_days'       => env('TRIAL_DAYS', 14),
    'logo_url'         => env('BRAND_LOGO_URL', null),
    'favicon_url'      => env('BRAND_FAVICON_URL', null),
    'primary_color'    => env('BRAND_PRIMARY_COLOR', '#2f5ee8'),
    'twitter_url'      => env('BRAND_TWITTER_URL', null),
    'facebook_url'     => env('BRAND_FACEBOOK_URL', null),
    'linkedin_url'     => env('BRAND_LINKEDIN_URL', null),

    // Admin panel IP whitelist (comma-separated). Empty = allow all.
    'admin_ip_whitelist' => env('ADMIN_IP_WHITELIST', ''),

    // Whether the public landing page is enabled.
    'landing_enabled'  => env('LANDING_PAGE_ENABLED', true),

    // Copyright line in emails and footer.
    'copyright'        => env('BRAND_COPYRIGHT', null), // null = auto "© {year} {APP_NAME}"
];
