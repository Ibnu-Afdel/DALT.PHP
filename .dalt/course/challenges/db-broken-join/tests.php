<?php

/**
 * Broken Join Challenge — Test Specification
 *
 * Verifies two fixes:
 *   1. INNER JOIN changed to LEFT JOIN
 *   2. ON clause fixed from posts.id = users.id to posts.user_id = users.id
 */

return [
    // Executable check. The source checks below can be satisfied by writing the
    // right words into code that never runs; this one runs the controller
    // against a seeded database and looks at what came back.
    'every_post_is_returned_with_its_author' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/db/posts/index.php',
        'seed' => [
            'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE posts (id INTEGER PRIMARY KEY, user_id INT, title TEXT, created_at TEXT)',
            "INSERT INTO users VALUES (5, 'Alice')",
            "INSERT INTO posts VALUES (1, 5, 'first', '2026-01-01')",
            "INSERT INTO posts VALUES (2, 5, 'second', '2026-01-02')",
            "INSERT INTO posts VALUES (3, 5, 'third', '2026-01-03')",
        ],
        'expect' => [
            'status' => 200,
            'count' => 3,
            'contains' => 'Alice',
        ],
        'hint' => 'All three posts belong to user 5, so all three should come back with Alice as the author. Joining on the wrong column matches nothing.',
    ],

    'uses_left_join' => [
        'type'   => 'file_contains',
        'file'   => 'Http/controllers/db/posts/index.php',
        'search' => 'LEFT JOIN',
        'hint'   => 'Change INNER JOIN to LEFT JOIN — INNER JOIN drops users with no posts from the results',
    ],

    'no_inner_join' => [
        'type'   => 'file_not_contains',
        'file'   => 'Http/controllers/db/posts/index.php',
        'search' => 'INNER JOIN',
        'hint'   => 'Remove INNER JOIN — replace it with LEFT JOIN users ON posts.user_id = users.id',
    ],

    'correct_on_clause' => [
        'type'   => 'file_contains',
        'file'   => 'Http/controllers/db/posts/index.php',
        'search' => 'posts.user_id = users.id',
        'hint'   => 'Fix the ON clause: it should be posts.user_id = users.id, not posts.id = users.id',
    ],

    'no_wrong_on_clause' => [
        'type'   => 'file_not_contains',
        'file'   => 'Http/controllers/db/posts/index.php',
        'search' => 'posts.id = users.id',
        'hint'   => 'Remove "posts.id = users.id" — posts.id is the post\'s own primary key, not the foreign key to users',
    ],
];
