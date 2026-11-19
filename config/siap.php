<?php
return [
    // Enable only after creating an initial database account.
    'database_business' => env('SIAP_DATABASE_BUSINESS', false),
    'database_accounts' => env('SIAP_DATABASE_ACCOUNTS', false),
    // Daftar IP proxy tepercaya dipisah koma, atau '*' bila aplikasi hanya dapat dijangkau lewat proxy. Kosong = tidak ada.
    'trusted_proxies' => env('SIAP_TRUSTED_PROXIES'),
    // Content-Security-Policy: report-only (default, hanya melapor di konsol browser), enforce, atau off.
    'csp' => env('SIAP_CSP', 'report-only'),
    'browser_access_minutes' => (int) env('SIAP_BROWSER_ACCESS_MINUTES', 43200),
];
