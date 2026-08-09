<?php

declare(strict_types=1);

use Core\App;
use Core\Config;
use Core\Container;
use Core\Database;
use Core\DatabaseManager;
use Core\Platform;
use Core\View;
use Dotenv\Dotenv;

Dotenv::createImmutable(base_path(''))->safeLoad();

$config = Config::load(base_path('config'));
$platform = Platform::discover(base_path());
$container = new Container();
$container->instance(Config::class, $config);
$container->instance(Platform::class, $platform);
$container->singleton(
    View::class,
    fn (): View => new View([
        base_path('resources/views'),
        ...$platform->viewRoots(),
    ]),
);

// Registration is cheap; the database connection is created only when a
// handler first resolves Database from the container.
$container->singleton(
    Database::class,
    fn (Container $container): Database => DatabaseManager::create(
        $container->resolve(Config::class)->array('database.database'),
    ),
);

App::setContainer($container);
