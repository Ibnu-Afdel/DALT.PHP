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

// Create database connection
$database = new Core\Database($dbConfig['database']);

// Create migration instance and run migrations
$migration = new Core\Migration($database);
$migration->runMigrations();

echo "Migration process completed.\n";
