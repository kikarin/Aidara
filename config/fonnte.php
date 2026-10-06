<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fonnte WhatsApp Gateway
    |--------------------------------------------------------------------------
    |
    | Kredensial untuk mengirim pesan WhatsApp melalui gateway Fonnte
    | (https://fonnte.com). Token perangkat didapat dari dashboard Fonnte
    | pada menu Device.
    |
    */

    'enabled' => (bool) env('FONNTE_ENABLED', false),

    'token' => env('FONNTE_TOKEN'),

    'base_url' => env('FONNTE_BASE_URL', 'https://api.fonnte.com'),

    'country_code' => env('FONNTE_COUNTRY_CODE', '62'),

    'timeout' => (int) env('FONNTE_TIMEOUT', 15),

    'verify_ssl' => (bool) env('FONNTE_VERIFY_SSL', true),

];
