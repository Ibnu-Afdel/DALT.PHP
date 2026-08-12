<?php

/**
 * Static checks for the missing pagination on GET /db/users.
 *
 * These deliberately do not require json_encode(): a controller returns its
 * data and the route boundary normalizes it (Lesson 02, Lesson 11). Requiring
 * the printed form would reject the correct solution.
 * Runtime assertions are pending the challenge-verifier work (DALT-0062).
 */

return [
    // Executable check: 25 users exist, so a paginated endpoint must return 10.
    // Writing LIMIT into the file is not the same as the query applying one.
    'page_returns_only_one_page_of_rows' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/db/users/index.php',
        'seed' => [
            'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, created_at TEXT)',
            "INSERT INTO users (name, email, created_at) SELECT 'u' || value, 'u' || value || '@e.com', '2026-01-01'"
                . ' FROM (WITH RECURSIVE c(value) AS (SELECT 1 UNION ALL SELECT value + 1 FROM c WHERE value < 25) SELECT value FROM c)',
        ],
        'expect' => [
            'status' => 200,
            'count' => 10,
            'count_key' => 'data',
        ],
        'hint' => 'With 25 users seeded and a default limit of 10, the endpoint should return 10 rows under "data".',
    ],

    'query_limits_rows' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/db/users/index.php',
        'search' => 'LIMIT',
        'hint' => 'Add a LIMIT clause so the endpoint cannot return the whole table.',
    ],

    'query_skips_earlier_pages' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/db/users/index.php',
        'search' => 'OFFSET',
        'hint' => 'Add an OFFSET clause so later pages skip the rows already returned.',
    ],

    'limit_is_bound' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/db/users/index.php',
        'search' => ':limit',
        'hint' => 'Bind the limit as a named parameter rather than concatenating it into the SQL.',
    ],

    'offset_is_bound' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/db/users/index.php',
        'search' => ':offset',
        'hint' => 'Bind the offset as a named parameter rather than concatenating it into the SQL.',
    ],

    'response_is_enveloped' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/db/users/index.php',
        'search' => "'data'",
        'hint' => "Return {'data': [...], 'page': ..., 'limit': ...} so the client can tell which page it received.",
    ],
];
