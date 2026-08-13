<?php

/**
 * Slow Queries — Test Specification
 *
 * Runs against the learner's own PostgreSQL (driver: pgsql, see DECISIONS.md D-09).
 * The fix lives entirely in 004_add_indexes.sql, which the verifier does not and
 * cannot apply itself — it depends on `php artisan migrate`, exactly as the
 * README's success criteria already state. Both checks read pg_indexes, the real
 * system catalog, after that migration has run: this is what actually
 * distinguishes "an index exists on this column" from "the text CREATE INDEX
 * appears somewhere in the file" — a commented-out statement or an index on the
 * wrong column both satisfied the old file_contains checks and neither creates a
 * real index. Neither check seeds anything; they read state the migration left
 * behind, inside a transaction that is rolled back regardless (nothing to roll
 * back here, since these are read-only, but the mechanism is uniform across every
 * pgsql check).
 *
 * Verifies:
 *   1. An index on posts(user_id) actually exists in the catalog.
 *   2. An index on posts(status) actually exists in the catalog.
 */

return [
    'posts_user_id_is_indexed' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/users/posts.php',
        'route'   => ['id' => '1'],
        'inspect' => "SELECT indexdef FROM pg_indexes WHERE tablename = 'posts' AND indexdef ILIKE '%(user_id)%'",
        'expect'  => ['source' => 'inspect', 'contains' => 'user_id'],
        'hint'    => "Add CREATE INDEX ... ON posts(user_id) to 004_add_indexes.sql, then run php artisan migrate. A comment or a TODO does not create an index — pg_indexes has to show it.",
    ],

    'posts_status_is_indexed' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/posts/published.php',
        'inspect' => "SELECT indexdef FROM pg_indexes WHERE tablename = 'posts' AND indexdef ILIKE '%(status)%'",
        'expect'  => ['source' => 'inspect', 'contains' => 'status'],
        'hint'    => "Add CREATE INDEX ... ON posts(status) to 004_add_indexes.sql, then run php artisan migrate. A comment or a TODO does not create an index — pg_indexes has to show it.",
    ],
];
