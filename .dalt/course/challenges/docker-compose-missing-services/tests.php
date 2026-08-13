<?php

/**
 * Missing Compose Services Challenge - Test Specification
 *
 * Verifies that the learner has added both the db (Postgres) and nginx
 * services to the docker-compose.yml, with correct volume mounts, port
 * mapping, and service dependency — checked against the *parsed* compose
 * config (via `docker compose config`) so a value in the wrong service, or
 * inside a comment, cannot pass.
 */

return [
    'has_db_service' => [
        'type'   => 'compose_config',
        'file'   => 'docker-compose.yml',
        'path'   => 'services.db.image',
        'equals' => 'postgres:16-alpine',
        'hint'   => 'Add a db service using the image: postgres:16-alpine',
    ],

    'has_nginx_service' => [
        'type'   => 'compose_config',
        'file'   => 'docker-compose.yml',
        'path'   => 'services.nginx.image',
        'equals' => 'nginx:alpine',
        'hint'   => 'Add an nginx service using the image: nginx:alpine',
    ],

    'mounts_postgres_volume' => [
        'type'     => 'compose_config',
        'file'     => 'docker-compose.yml',
        'path'     => 'services.db.volumes',
        'contains' => '/var/lib/postgresql/data',
        'hint'     => 'Mount the pgdata volume at /var/lib/postgresql/data inside the db service',
    ],

    'app_depends_on_db' => [
        'type'   => 'compose_config',
        'file'   => 'docker-compose.yml',
        'path'   => 'services.app.depends_on.db',
        'exists' => true,
        'hint'   => 'Add depends_on: [db] to the app service so it waits for the database container to start',
    ],

    'exposes_port_8080' => [
        'type'     => 'compose_config',
        'file'     => 'docker-compose.yml',
        'path'     => 'services.nginx.ports',
        'contains' => '8080',
        'hint'     => 'Expose port 8080 on the nginx service: ports: ["8080:80"]',
    ],

    'no_todos_remaining' => [
        'type'             => 'file_not_contains',
        'file'             => 'docker-compose.yml',
        'search'           => '# TODO',
        'include_comments' => true,
        'hint'             => 'Remove all # TODO comments once you have added both services',
    ],
];
