<?php
return [
    // Enable only after creating an initial database account.
    'database_business' => env('SIAP_DATABASE_BUSINESS', false),
    'database_accounts' => env('SIAP_DATABASE_ACCOUNTS', false),
    'browser_access_minutes' => (int) env('SIAP_BROWSER_ACCESS_MINUTES', 43200),
];
