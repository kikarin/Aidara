<?php

return [

    'headers' => [
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'camera=(), microphone=(), geolocation=()',
        'hsts' => env('SECURITY_HSTS_ENABLED', env('APP_ENV') === 'production'),
        'hsts_max_age' => 31536000,
    ],

    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', env('APP_ENV') === 'production'),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),
    ],

];
