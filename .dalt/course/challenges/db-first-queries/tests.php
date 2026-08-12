<?php

/**
 * Static checks for the three defects in this challenge.
 *
 * Controllers return their data and the route boundary normalizes it, so these
 * checks deliberately do not require json_encode() — that would reject the
 * solution Lessons 02 and 11 teach.
 * Runtime assertions are pending the challenge-verifier work (DALT-0062).
 */

return [
    'search_is_not_interpolated' => [
        'type' => 'file_not_contains',
        'file' => 'Http/controllers/users/index.php',
        'search' => '{$search}',
        'hint' => 'The search term is being written into the SQL string before the database parses it.',
    ],

    'search_is_bound' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/users/index.php',
        'search' => ':search',
        'hint' => 'Pass the search term as a bound parameter. The % wildcards belong in the bound value, not in the SQL.',
    ],

    'lookup_uses_the_real_column' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/users/show.php',
        'search' => 'WHERE id = :id',
        'hint' => 'Check the actual column names on the users table before trusting the one in the query.',
    ],

    'lookup_does_not_use_a_missing_column' => [
        'type' => 'file_not_contains',
        'file' => 'Http/controllers/users/show.php',
        'search' => 'WHERE user_id',
        'hint' => 'There is no user_id column on users; that name belongs to the posts table.',
    ],

    'not_found_sets_the_status_on_the_response' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/users/show.php',
        'search' => 'Response::json(',
        'hint' => 'Return a Response carrying the status, rather than setting it as a side effect.',
    ],

    'not_found_does_not_use_global_status_state' => [
        'type' => 'file_not_contains',
        'file' => 'Http/controllers/users/show.php',
        'search' => 'http_response_code(',
        'hint' => 'Response::send() sets the status from the Response object, overwriting anything set this way.',
    ],
];
