<?php
return [
    'name' => env('APP_NAME', 'SIAP-Wisata'), 'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', false), 'url' => env('APP_URL', 'http://127.0.0.1:8017'),
    'timezone' => 'Asia/Jakarta', 'locale' => 'id', 'fallback_locale' => 'en',
    'key' => env('APP_KEY'), 'cipher' => 'AES-256-CBC',
];
