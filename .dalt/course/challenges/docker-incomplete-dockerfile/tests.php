<?php

/**
 * Incomplete Dockerfile Challenge - Test Specification
 *
 * Verifies that the learner has completed the three missing parts
 * of the Dockerfile: WORKDIR, PHP extensions, and CMD.
 */

return [
    'has_workdir' => [
        'type' => 'file_contains',
        'file' => 'Dockerfile',
        'search' => 'WORKDIR /var/www/html',
        'hint' => 'Add: WORKDIR /var/www/html  (sets the working directory inside the container)',
    ],

    'has_ext_install' => [
        'type' => 'file_contains',
        'file' => 'Dockerfile',
        'search' => 'docker-php-ext-install',
        'hint' => 'Use docker-php-ext-install to install PHP extensions inside the Docker image',
    ],

    'has_pdo_pgsql' => [
        'type' => 'file_contains',
        'file' => 'Dockerfile',
        'search' => 'pdo_pgsql',
        'hint' => 'Include pdo_pgsql in your docker-php-ext-install command — it\'s the PostgreSQL driver for PDO',
    ],

    'installs_postgres_client_headers' => [
        'type' => 'file_contains',
        'file' => 'Dockerfile',
        'search' => 'postgresql-dev',
        'hint' => 'docker-php-ext-install compiles pdo_pgsql against libpq-fe.h, which the Alpine PHP image does not ship. Add postgresql-dev with apk in the same RUN, before the helper runs.',
    ],

    'has_cmd' => [
        'type' => 'file_contains',
        'file' => 'Dockerfile',
        'search' => 'CMD ["php-fpm"]',
        'hint' => 'Add: CMD ["php-fpm"]  (starts PHP-FPM when the container launches)',
    ],

    'no_todos_remaining' => [
        'type' => 'file_not_contains',
        'file' => 'Dockerfile',
        'search' => '# TODO',
        'include_comments' => true,
        'hint' => 'Remove all # TODO comments after completing each part',
    ],
];
