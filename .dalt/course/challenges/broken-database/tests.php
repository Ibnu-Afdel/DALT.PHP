<?php

/**
 * Broken Database Challenge - Test Specification
 *
 * Verifies two fixes:
 *   1. Database::query() passes bound params to execute() (Symptom A)
 *   2. posts/index.php binds the search term instead of concatenating it (Symptom B)
 */

return [
    // Executable check. The source checks below can be satisfied by writing the
    // right words into code that never runs; this one runs the controller
    // against a seeded database and looks at what came back.
    'bound_id_finds_the_seeded_row' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/posts/show.php',
        'seed' => [
            'CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT, body TEXT)',
            "INSERT INTO posts VALUES (1, 'First Post', 'Hello world')",
        ],
        'query' => ['id' => 1],
        'expect' => [
            'status' => 200,
            'contains' => 'First Post',
        ],
        'hint' => 'query() prepares the statement with :id but calls execute() with no arguments, so the placeholder is never filled. Pass $params to execute().',
    ],

    'query_passes_params' => [
        'type' => 'file_contains',
        'file' => 'framework/Core/Database.php',
        'search' => '->execute($params)',
        'hint' => 'Pass $params to execute() method in Database::query()'
    ],

    'not_executing_empty' => [
        'type' => 'file_not_contains',
        'file' => 'framework/Core/Database.php',
        'search' => '->execute();',
        'hint' => 'Don\'t call execute() without parameters, pass $params'
    ],

    // Executable check. Seeds two posts whose titles never contain the raw
    // injection payload, then submits it as a search term. Concatenated SQL
    // makes the WHERE clause true unconditionally, so both posts come back;
    // a bound parameter can only ever match it as a literal substring.
    'search_term_cannot_inject_sql' => [
        'type' => 'handler_result',
        'file' => 'Http/controllers/posts/index.php',
        'seed' => [
            'CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT, body TEXT)',
            "INSERT INTO posts VALUES (1, 'Alpha Post', 'first')",
            "INSERT INTO posts VALUES (2, 'Beta Post', 'second')",
        ],
        'query' => ['search' => "' OR '1'='1"],
        'expect' => [
            'status' => 200,
            'not_contains' => 'Alpha Post',
        ],
        'hint' => "Concatenating the search term into the SQL string makes \"OR '1'='1\" part of the query instead of data, so every post matches. Bind it as a parameter instead.",
    ],

    'search_uses_binding' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/posts/index.php',
        'search' => ':search',
        'hint' => 'Use parameter binding (:search) instead of string concatenation'
    ],

    'no_sql_injection' => [
        'type' => 'file_not_contains',
        'file' => 'Http/controllers/posts/index.php',
        'search' => '" . $search . "',
        'hint' => 'Remove string concatenation - it causes SQL injection!'
    ]
];
