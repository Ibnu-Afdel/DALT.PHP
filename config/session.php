<?php

declare(strict_types=1);

return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'name' => env('SESSION_NAME', 'daltphp_' . substr(sha1(BASE_PATH), 0, 8)),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'cookie' => [
        'lifetime' => 0,
        'path' => env('SESSION_PATH', '/'),
        'domain' => env('SESSION_DOMAIN', ''),
        'secure' => env('SESSION_SECURE_COOKIE'),
        'httponly' => true,
        'samesite' => env('SESSION_SAME_SITE', 'Lax'),
    ],
];
