<?php

declare(strict_types=1);

return [
    'database' => [
        'driver' => env('DB_DRIVER', 'sqlite'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 5432),
        'dbname' => env('DB_NAME', 'dalt_php_app'),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8'),
        // SQLite specific
        'database' => env('DB_DATABASE', base_path('database/app.sqlite')),
    ]
];
