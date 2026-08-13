<?php

declare(strict_types=1);

use Core\App;
use Core\Config;
use Core\Database;
use Core\Platform;

test('framework bootstrap installs configuration and keeps database lazy', function () {
    require base_path('framework/Core/bootstrap.php');

    $container = App::container();

    expect($container->resolve(Config::class))->toBeInstanceOf(Config::class)
        ->and(config('app.name'))->toBe('DALT.PHP Framework')
        ->and($container->resolve(Platform::class)->isInstalled())->toBe(is_dir(base_path('.dalt')))
        ->and($container->has(Database::class))->toBeTrue()
        ->and($container->resolved(Database::class))->toBeFalse();

    $database = $container->resolve(Database::class);

    expect($database)->toBe($container->resolve(Database::class))
        ->and($database->query("SELECT name FROM sqlite_master WHERE type = 'table'")->get())->toBe([]);
});
