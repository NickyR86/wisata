<?php
return [
    // Enable only after creating an initial database account.
    'database_business' => env('SIAP_DATABASE_BUSINESS', false),
    'database_accounts' => env('SIAP_DATABASE_ACCOUNTS', false),
    // Daftar IP proxy tepercaya dipisah koma, atau '*' bila aplikasi hanya dapat dijangkau lewat proxy. Kosong = tidak ada.
    'trusted_proxies' => env('SIAP_TRUSTED_PROXIES'),
    // Content-Security-Policy: report-only (default, hanya melapor di konsol browser), enforce, atau off.
    'csp' => env('SIAP_CSP', 'report-only'),
    // Identitas pada SKRD/STS/STBP. Kosong = di lokal/testing memakai data contoh, di lingkungan lain tertulis "(belum diatur)".
    'documents' => [
        'number_prefix' => env('SIAP_DOC_NUMBER_PREFIX'),
        'bank' => env('SIAP_DOC_BANK'),
        'account' => env('SIAP_DOC_ACCOUNT'),
        'signer_name' => env('SIAP_DOC_SIGNER_NAME'),
        'signer_rank' => env('SIAP_DOC_SIGNER_RANK'),
        'signer_nip' => env('SIAP_DOC_SIGNER_NIP'),
        'authority_name' => env('SIAP_DOC_AUTHORITY_NAME'),
        'authority_nip' => env('SIAP_DOC_AUTHORITY_NIP'),
    ],
    'browser_access_minutes' => (int) env('SIAP_BROWSER_ACCESS_MINUTES', 43200),
];
