<?php

declare(strict_types=1);

use Core\App;
use Core\Container;

test('the facade fails clearly before the application is bootstrapped', function () {
    App::forgetContainer();

    App::container();
})->throws(LogicException::class, 'The application container has not been bootstrapped.');

test('the facade exposes and forwards to its typed container', function () {
    $container = new Container();
    App::setContainer($container);
    App::bind('transient', fn (): object => new stdClass());
    App::singleton('shared', fn (): object => new stdClass());
    $instance = new stdClass();
    App::instance('instance', $instance);

    expect(App::container())->toBe($container)
        ->and(App::containerOrNull())->toBe($container)
        ->and(App::resolve('transient'))->not->toBe(App::resolve('transient'))
        ->and(App::resolve('shared'))->toBe(App::resolve('shared'))
        ->and(App::resolve('instance'))->toBe($instance);
});
