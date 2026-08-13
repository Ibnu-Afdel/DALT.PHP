<?php

/**
 * Docker Missing Health Check — Test Specification
 *
 * Two fixes, confirmed by three checks against the *parsed* compose config
 * (via `docker compose config`), not the raw file text:
 *   1. a healthcheck block on the db service, using pg_isready
 *   2. depends_on switched to the long syntax, waiting on service_healthy
 */

return [
    'has_healthcheck' => [
        'type'   => 'compose_config',
        'file'   => 'docker-compose.yml',
        'path'   => 'services.db.healthcheck.test',
        'exists' => true,
        'hint'   => 'Add a "healthcheck:" block to the db service.',
    ],

    'uses_pg_isready' => [
        'type'     => 'compose_config',
        'file'     => 'docker-compose.yml',
        'path'     => 'services.db.healthcheck.test',
        'contains' => 'pg_isready',
        'hint'     => 'Use pg_isready in the healthcheck test command (e.g. test: ["CMD-SHELL", "pg_isready -U postgres"]).',
    ],

    'waits_for_health' => [
        'type'   => 'compose_config',
        'file'   => 'docker-compose.yml',
        'path'   => 'services.app.depends_on.db.condition',
        'equals' => 'service_healthy',
        'hint'   => 'Update depends_on to use object syntax and add "condition: service_healthy" for the db service.',
    ],
];
