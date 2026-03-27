<?php

use Core\App;
use Core\Container;
use Core\Database;
use Core\DatabaseManager;

$dotenv = Dotenv\Dotenv::createImmutable(base_path(''));
if (method_exists($dotenv, 'safeLoad')) {
    $dotenv->safeLoad();
} else {
    try {
        $dotenv->load();
    } catch (\Throwable $e) {
    }
}

$dbConfig  = require base_path('config/database.php');

$container = new Container();

$container->bind('Core\Database', function () use ($dbConfig) {
    return DatabaseManager::create($dbConfig['database']);
});

App::setContainer($container);
