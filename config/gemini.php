<?php

return [

    'enabled' => env('GEMINI_CHAT_ENABLED', false),

    'api_key' => env('GEMINI_API_KEY'),

    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),

    'base_url' => env('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com/v1beta'),

    'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 4096),

    'temperature' => (float) env('GEMINI_TEMPERATURE', 0.2),

    'rate_limit' => (int) env('GEMINI_CHAT_RATE_LIMIT', 20),

    'max_message_length' => (int) env('GEMINI_CHAT_MAX_MESSAGE_LENGTH', 2000),

    'max_history' => (int) env('GEMINI_CHAT_MAX_HISTORY_MESSAGES', 24),

    'max_history_item_length' => (int) env('GEMINI_CHAT_MAX_HISTORY_ITEM_LENGTH', 1200),

    'knowledge_paths' => array_filter(array_map('trim', explode(',', env('GEMINI_CHAT_KNOWLEDGE_PATHS', 'docs')))),

    'knowledge_max_chars_per_file' => (int) env('GEMINI_CHAT_KNOWLEDGE_MAX_CHARS_PER_FILE', 4000),

    'knowledge_max_total_chars' => (int) env('GEMINI_CHAT_KNOWLEDGE_MAX_TOTAL_CHARS', 50000),

    'app_name' => env('GEMINI_CHAT_APP_NAME', env('APP_NAME', 'Aidara')),

    'rejection_message' => env(
        'GEMINI_CHAT_REJECTION_MESSAGE',
        'Saya hanya dapat membantu penggunaan modul CMS Aidara. Silakan tanyakan tentang modul seperti Atlet, Program Latihan, Pemeriksaan, Cabor, atau Data Master.'
    ),

    'log_enabled' => env('GEMINI_CHAT_LOG_ENABLED', false),

];
