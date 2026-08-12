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
