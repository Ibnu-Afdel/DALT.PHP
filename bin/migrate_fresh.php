#!/usr/bin/env php
<?php

// Define BASE_PATH
define('BASE_PATH', __DIR__ . '/../');

// Load functions
require BASE_PATH . 'Core/functions.php';

// Load Composer autoloader
require base_path('vendor/autoload.php');

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(base_path(''));
$dotenv->load();

// Load configuration
$dbConfig = require base_path('config/database.php');

echo "Running migrate:fresh...\n";

// Delete the entire database file for SQLite
if (($dbConfig['database']['driver'] ?? 'sqlite') === 'sqlite') {
    $dbPath = $dbConfig['database']['database'];
    if (file_exists($dbPath)) {
        unlink($dbPath);
        echo "Dropped existing database file.\n";
    }
} else {
    // For other databases, we'd need to drop all tables
    // This is more complex and would require connecting to the database
    // and dropping each table individually
    echo "Warning: migrate:fresh for non-SQLite databases not implemented yet.\n";
}

// Create database connection
$database = new Core\Database($dbConfig['database']);

// Create migration instance and run migrations
$migration = new Core\Migration($database);
$migration->runMigrations();

echo "Fresh migration completed.\n";
