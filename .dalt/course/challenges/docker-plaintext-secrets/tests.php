<?php

/**
 * Docker Plaintext Secrets — Test Specification
 *
 * Checked against the *parsed* compose config (via `docker compose config`),
 * not the raw file text, so a check can require a key's absence — something
 * a file_contains/file_not_contains pair cannot do precisely (it can only
 * reject one exact literal, e.g. "POSTGRES_PASSWORD: supersecret", and a
 * learner who renames the value keeps hardcoding a password and still passes).
 *
 * Four fixes:
 *   1. The POSTGRES_PASSWORD environment key is removed from the db service, not just reworded
 *   2. A top-level secrets block defines db_password
 *   3. The db service mounts db_password as a secret
 *   4. POSTGRES_PASSWORD_FILE points at /run/secrets/db_password
 */

return [
    'no_hardcoded_password' => [
        'type'   => 'compose_config',
        'file'   => 'docker-compose.yml',
        'path'   => 'services.db.environment.POSTGRES_PASSWORD',
        'exists' => false,
        'hint'   => 'Remove POSTGRES_PASSWORD entirely from the db service\'s environment block — the password is set via POSTGRES_PASSWORD_FILE instead.',
    ],

    'has_secrets_block' => [
        'type'   => 'compose_config',
        'file'   => 'docker-compose.yml',
        'path'   => 'secrets.db_password',
        'exists' => true,
        'hint'   => 'Add a top-level "secrets:" block defining db_password, pointing to ./secrets/db_password.txt',
    ],

    'mounts_secret' => [
        'type'     => 'compose_config',
        'file'     => 'docker-compose.yml',
        'path'     => 'services.db.secrets',
        'contains' => 'db_password',
        'hint'     => 'Add a service-level "secrets:" list to the db service and reference "- db_password"',
    ],

    'uses_password_file_env' => [
        'type'   => 'compose_config',
        'file'   => 'docker-compose.yml',
        'path'   => 'services.db.environment.POSTGRES_PASSWORD_FILE',
        'equals' => '/run/secrets/db_password',
        'hint'   => 'Add POSTGRES_PASSWORD_FILE: /run/secrets/db_password to the db service\'s environment block',
    ],
];
