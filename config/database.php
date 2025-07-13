<?php

return [
    'database' => [
        'driver' => $_ENV['DB_DRIVER'] ?? 'pgsql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? 5432,
        'dbname' => $_ENV['DB_NAME'] ?? 'dalt_php_app',
        'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8',
        // SQLite specific
        'database' => $_ENV['DB_DATABASE'] ?? (defined('BASE_PATH') ? BASE_PATH . 'database/app.sqlite' : __DIR__ . '/../database/app.sqlite'),
    ]
];
