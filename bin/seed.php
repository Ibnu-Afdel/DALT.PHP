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

echo "Seeding database with test data...\n";

// Create a test user
$name = 'Test User';
$email = 'test@example.com';
$password = password_hash('password123', PASSWORD_DEFAULT);

// Check if user already exists
$existingUser = $database->query('SELECT id FROM users WHERE email = ?', [$email])->find();

if (!$existingUser) {
    $database->query('INSERT INTO users (name, email, password) VALUES (?, ?, ?)', [$name, $email, $password]);
    echo "Created test user: $name ($email)\n";
} else {
    echo "Test user already exists: $email\n";
}

// Get the user ID
$user = $database->query('SELECT id FROM users WHERE email = ?', [$email])->find();
$userId = $user['id'];

// Notes table no longer exists - only users table now

echo "Database seeding completed!\n";
echo "Test user credentials: $email / password123\n";
