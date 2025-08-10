<?php

use Core\App;
use Core\Container;
use Core\Database;
use Core\DatabaseManager;

// Load Composer autoloader
require base_path('vendor/autoload.php');

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(base_path(''));
$dotenv->load();

// Load configuration files
$appConfig = require base_path('config/app.php');
$dbConfig = require base_path('config/database.php');

$container = new Container();

$container->bind('Core\Database', function () use ($dbConfig) {
    return DatabaseManager::create($dbConfig['database']);
});

App::setContainer($container);