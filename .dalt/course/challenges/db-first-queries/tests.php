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
    // Executable checks. The source checks below describe the shape of the fix;
    // these two confirm the endpoints actually behave.
    'lookup_finds_an_existing_user' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/users/show.php',
        'seed' => [
            'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, created_at TEXT)',
            "INSERT INTO users VALUES (7, 'Alice', 'alice@example.com', '2026-01-01')",
        ],
        'route' => ['id' => '7'],
        'query' => ['id' => '7'],
        'expect' => ['status' => 200, 'contains' => 'alice@example.com'],
        'hint' => 'User 7 exists, so this must return the row rather than a 404. Check the column the WHERE clause names.',
    ],

    'search_does_not_match_an_injected_condition' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/users/index.php',
        'seed' => [
            'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, created_at TEXT)',
            "INSERT INTO users VALUES (1, 'Alice', 'alice@example.com', '2026-01-01')",
            "INSERT INTO users VALUES (2, 'Bob', 'bob@example.com', '2026-01-02')",
        ],
        'query' => ['search' => "' OR '1'='1"],
        'expect' => ['status' => 200, 'count' => 0],
        'hint' => 'That search term matches no real email, so a safely bound query returns nothing. If it returns every user, the value is being parsed as SQL.',
    ],

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
