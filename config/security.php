<?php

return [
    'login' => [
        'max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('SECURITY_LOGIN_DECAY_SECONDS', 60),
        'lockout_seconds' => (int) env('SECURITY_LOGIN_LOCKOUT_SECONDS', 900),
    ],
    // Enforce by default. Report-only is available for staging diagnostics.
    'csp_report_only' => env('CSP_REPORT_ONLY', false),
    'connect_sources' => array_filter(array_map('trim', explode(',', (string) env('CSP_CONNECT_SOURCES', '')))),
    'reverb_origin' => env('VITE_REVERB_HOST')
        ? (env('VITE_REVERB_SCHEME', 'https') === 'https' ? 'wss' : 'ws').'://'.env('VITE_REVERB_HOST').':'.env('VITE_REVERB_PORT', 443)
        : null,
    'image_sources' => array_filter(array_map('trim', explode(',', (string) env('CSP_IMAGE_SOURCES', 'https://ui-avatars.com')))),
    'frame_sources' => array_filter(array_map('trim', explode(',', (string) env('CSP_FRAME_SOURCES', '')))),
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))), fn ($proxy) => $proxy !== '' && $proxy !== '*')),
    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 86400),
    'hsts_include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', false),
];
