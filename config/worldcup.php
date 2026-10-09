<?php

return [
    'base_url' => env('WORLDCUP_API_BASE_URL', 'https://worldcup26.ir'),
    'token' => env('WORLDCUP_API_TOKEN'),
    'cache_ttl' => (int) env('WORLDCUP_CACHE_TTL', 60),
    'cache_ttl_static' => (int) env('WORLDCUP_STATIC_CACHE_TTL', 300),
    'preview_matches' => (int) env('WORLDCUP_PREVIEW_MATCHES', 7),
    'timeout' => (int) env('WORLDCUP_API_TIMEOUT', 10),
    'settings_cache_ttl' => (int) env('WORLDCUP_SETTINGS_CACHE_TTL', 300),
];
