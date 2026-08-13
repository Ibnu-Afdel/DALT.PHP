<?php

/**
 * Migration Disorder — Test Specification
 *
 * Verifies two fixes by actually applying the current migration files
 * through SQLite, in the same filename order the real runner uses, and
 * inspecting the resulting schema — not by grepping filenames or SQL text:
 *   1. 001_create_users_table.sql is the file that creates `users`, and
 *      002_create_posts_table.sql is the file that creates `posts`.
 *   2. The posts.id column uses BIGSERIAL (native Postgres), not the
 *      SQLite AUTOINCREMENT idiom.
 *
 * All four checks share one probe controller (migration-schema.php, part of
 * this fixture, never routed) because 'seed' must be non-empty even though
 * this challenge has no rows to seed — the controller builds its own schema.
 */

return [
    'users_migration_creates_the_users_table' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/db/migration-schema.php',
        'seed' => ['SELECT 1'],
        'expect' => [
            'status' => 200,
            'contains' => '"users_created_by":"001_create_users_table.sql"',
        ],
        'hint' => 'database/migrations/001_create_users_table.sql must be the file whose CREATE TABLE statement produces the `users` table, not `posts`.',
    ],

    'posts_migration_creates_the_posts_table' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/db/migration-schema.php',
        'seed' => ['SELECT 1'],
        'expect' => [
            'status' => 200,
            'contains' => '"posts_created_by":"002_create_posts_table.sql"',
        ],
        'hint' => 'database/migrations/002_create_posts_table.sql must be the file whose CREATE TABLE statement produces the `posts` table, not `users`.',
    ],

    // Reads the id column's declared type via PRAGMA table_info, not a text
    // search of the migration file or the stored CREATE TABLE SQL — SQLite
    // preserves comments verbatim in the latter, so "AUTOINCREMENT, --
    // BIGSERIAL" would fool a raw substring check without fixing anything.
    'posts_id_uses_native_postgres_type' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/db/migration-schema.php',
        'seed' => ['SELECT 1'],
        'expect' => [
            'status' => 200,
            'contains' => '"posts_id_type":"BIGSERIAL"',
        ],
        'hint' => 'Change the posts id column to BIGSERIAL PRIMARY KEY — native Postgres, not INTEGER PRIMARY KEY AUTOINCREMENT (the SQLite idiom).',
    ],
];
