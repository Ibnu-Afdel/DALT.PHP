<?php

declare(strict_types=1);

use Core\App;
use Core\Config;
use Core\Database;

test('framework bootstrap installs configuration and keeps database lazy', function () {
    require base_path('framework/Core/bootstrap.php');

    $container = App::container();

    expect($container->resolve(Config::class))->toBeInstanceOf(Config::class)
        ->and(config('app.name'))->toBe('DALT.PHP Framework')
        ->and($container->has(Database::class))->toBeTrue()
        ->and($container->resolved(Database::class))->toBeFalse();
});
