<?php

/**
 * Missing Compose Services Challenge - Test Specification
 *
 * Verifies that the learner has added both the db (Postgres) and nginx services
 * to the docker-compose.yml, with correct volume mounts, port mapping, and
 * service dependency.
 */

return [
    'has_postgres_image' => [
        'type' => 'file_contains',
        'file' => 'docker-compose.yml',
        'search' => 'postgres:16-alpine',
        'hint' => 'Add a db service using the image: postgres:16-alpine',
    ],

    'has_nginx_image' => [
        'type' => 'file_contains',
        'file' => 'docker-compose.yml',
        'search' => 'nginx:alpine',
        'hint' => 'Add an nginx service using the image: nginx:alpine',
    ],

    'mounts_postgres_volume' => [
        'type' => 'file_contains',
        'file' => 'docker-compose.yml',
        'search' => '/var/lib/postgresql/data',
        'hint' => 'Mount the pgdata volume at /var/lib/postgresql/data inside the db service',
    ],

    'has_depends_on' => [
        'type' => 'file_contains',
        'file' => 'docker-compose.yml',
        'search' => 'depends_on',
        'hint' => 'Add depends_on: [db] to the app service so it waits for the database container to start',
    ],

    'exposes_port_8080' => [
        'type' => 'file_contains',
        'file' => 'docker-compose.yml',
        'search' => '8080',
        'hint' => 'Expose port 8080 on the nginx service: ports: ["8080:80"]',
    ],

    'no_todos_remaining' => [
        'type' => 'file_not_contains',
        'file' => 'docker-compose.yml',
        'search' => '# TODO',
        'include_comments' => true,
        'hint' => 'Remove all # TODO comments once you have added both services',
    ],
];
