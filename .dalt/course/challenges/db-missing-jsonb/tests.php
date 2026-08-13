<?php

/**
 * Missing JSONB Metadata — Test Specification
 *
 * All three checks run against the learner's own PostgreSQL (driver: pgsql, see
 * DECISIONS.md D-09) — this challenge has no SQLite equivalent, and the point is
 * proving the value actually round-trips through real jsonb, not that the word
 * "metadata" appears in the right file. Each check opens its own transaction that
 * is always rolled back, so nothing is left behind in the learner's database.
 *
 * Verifies:
 *   1. Submitted metadata is actually persisted as jsonb, not silently dropped.
 *   2. Omitted metadata stores real SQL NULL, not the four-character string "null".
 *   3. GET /posts includes metadata in the response.
 */

return [
    'store_persists_metadata_as_jsonb' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/posts/store.php',
        'input'   => [
            'title'    => 'DALT jsonb probe',
            'body'     => 'x',
            'metadata' => '{"tags":["docker"],"featured":true}',
        ],
        'inspect' => "SELECT metadata FROM posts WHERE title = 'DALT jsonb probe'",
        'expect'  => ['source' => 'inspect', 'contains' => 'docker'],
        'hint'    => 'Add metadata to both the column list and the parameter array in the INSERT in store.php — a bound parameter only reaches the database if it appears in both.',
    ],

    'store_stores_real_null_when_metadata_omitted' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/posts/store.php',
        'input'   => [
            'title' => 'DALT jsonb probe omitted',
            'body'  => 'x',
        ],
        'inspect' => "SELECT metadata FROM posts WHERE title = 'DALT jsonb probe omitted'",
        'expect'  => ['source' => 'inspect', 'not_contains' => '"metadata":"null"'],
        'hint'    => 'A request that omits metadata should bind PHP null, which PDO stores as real SQL NULL. Binding the string "null" instead stores the JSON null literal, which is not the same thing.',
    ],

    'read_includes_metadata' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/posts/index.php',
        'seed'    => [
            "INSERT INTO posts (title, body, user_id, metadata) VALUES ('DALT jsonb read probe', 'x', 1, '{\"source\": \"seed-check\"}')",
        ],
        'expect'  => ['source' => 'body', 'contains' => 'seed-check'],
        'hint'    => 'index.php selects an explicit column list. Add metadata to it — a column you never asked for cannot appear in the result.',
    ],
];
