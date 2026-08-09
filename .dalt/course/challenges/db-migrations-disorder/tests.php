<?php

/**
 * Migration Disorder — Test Specification
 *
 * Verifies three fixes:
 *   1. 001_create_users_table.sql contains the users migration
 *   2. 002_create_posts_table.sql contains the posts migration
 *   3. 002_create_posts_table.sql uses BIGSERIAL, not AUTOINCREMENT
 */

return [
    '001_is_users' => [
        'type'   => 'file_contains',
        'file'   => 'database/migrations/001_create_users_table.sql',
        'search' => 'CREATE TABLE IF NOT EXISTS users',
        'hint'   => 'Move the users CREATE TABLE statement into 001_create_users_table.sql so it runs first.',
    ],

    '002_is_posts' => [
        'type'   => 'file_contains',
        'file'   => 'database/migrations/002_create_posts_table.sql',
        'search' => 'CREATE TABLE IF NOT EXISTS posts',
        'hint'   => 'Move the posts CREATE TABLE statement into 002_create_posts_table.sql so it runs after users.',
    ],

    'uses_bigserial' => [
        'type'   => 'file_contains',
        'file'   => 'database/migrations/002_create_posts_table.sql',
        'search' => 'BIGSERIAL',
        'hint'   => 'Change the posts id column to use BIGSERIAL instead of INTEGER PRIMARY KEY AUTOINCREMENT',
    ],

    'no_autoincrement' => [
        'type'   => 'file_not_contains',
        'file'   => 'database/migrations/002_create_posts_table.sql',
        'search' => 'AUTOINCREMENT',
        'hint'   => 'Remove the SQLite AUTOINCREMENT keyword from the posts migration',
    ],
];
