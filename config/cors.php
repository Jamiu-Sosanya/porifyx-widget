<?php

return [
    /*
     * Paths that should have CORS headers applied.
     * The widget API must be open to all origins so the embed script can call home.
     */
    'paths' => ['api/widget/*', 'api/*'],

    'allowed_methods' => ['*'],

    /*
     * Allow any origin — the widget can be embedded on any customer website.
     * Domain-level authorization is enforced inside WidgetApiController::config()
     * via Widget::isDomainAllowed(), not at the CORS layer.
     */
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
