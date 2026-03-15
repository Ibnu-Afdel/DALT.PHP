<?php

/**
 * Broken Database Challenge - Test Specification
 * 
 * This file defines the expected behavior after fixing the database bugs.
 */

return [
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
