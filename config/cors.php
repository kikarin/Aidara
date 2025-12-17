<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development - React + Vite
        'http://localhost:5173',
        'http://localhost:5174',
        // Production
        'https://aidara-mobile.summitct.co.id',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 0,

    'supports_credentials' => true, // Penting untuk Sanctum
];