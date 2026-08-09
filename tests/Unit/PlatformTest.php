<?php

declare(strict_types=1);

use Core\Platform;
use Core\Request;
use Core\Router;

function platformFixtureRoot(): string
{
    $root = sys_get_temp_dir() . '/dalt-p01-platform-' . bin2hex(random_bytes(6));

    if (!mkdir($root, 0700, true) && !is_dir($root)) {
        throw new RuntimeException("Unable to create platform fixture: {$root}");
    }

    return $root;
}

function removePlatformFixture(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    if (is_dir($path) && !is_link($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            removePlatformFixture($entry->getPathname());
        }

        rmdir($path);
        return;
    }

    unlink($path);
}

test('an absent platform is an explicit no-op integration', function () {
    $root = platformFixtureRoot();

    try {
        $platform = Platform::discover($root);
        $router = new Router();
        $platform->boot();
        $platform->registerRoutes($router);

        expect($platform->isInstalled())->toBeFalse()
            ->and($platform->viewRoots())->toBe([])
            ->and($platform->controllerRoots())->toBe([]);
    } finally {
        removePlatformFixture($root);
    }
});

test('a complete platform owns its boot routes views and controllers', function () {
    $root = platformFixtureRoot();
    $platformRoot = $root . '/.dalt';

    try {
        mkdir($platformRoot . '/Core', 0700, true);
        mkdir($platformRoot . '/Http/controllers', 0700, true);
        mkdir($platformRoot . '/resources/views', 0700, true);
        mkdir($platformRoot . '/routes', 0700, true);
        file_put_contents($platformRoot . '/bootstrap.php', "<?php\n");
        file_put_contents(
            $platformRoot . '/routes/routes.php',
            "<?php\n\$router->get('/platform-probe', fn () => 'platform');\n",
        );

        $platform = Platform::discover($root);
        $router = new Router();
        $router->get('/platform-probe', fn () => 'application');
        $platform->registerRoutes($router);

        expect($platform->isInstalled())->toBeTrue()
            ->and($platform->viewRoots())->toBe([$platformRoot . '/resources/views'])
            ->and($platform->controllerRoots())->toBe([$platformRoot . '/Http/controllers'])
            ->and($router->route('/platform-probe', 'GET', new Request())->content())->toBe('application');
    } finally {
        removePlatformFixture($root);
    }
});

test('a partially removed platform fails at boot discovery with missing paths', function () {
    $root = platformFixtureRoot();

    try {
        mkdir($root . '/.dalt', 0700, true);
        file_put_contents($root . '/.dalt/bootstrap.php', "<?php\n");

        Platform::discover($root);
    } finally {
        removePlatformFixture($root);
    }
})->throws(
    RuntimeException::class,
    'Guided learning is incomplete; missing or invalid required path(s): .dalt/routes/routes.php, .dalt/Core, .dalt/Http/controllers, .dalt/resources/views',
);

test('composer autoload checks framework classes before optional platform fallbacks', function () {
    $composer = json_decode(
        (string) file_get_contents(base_path('composer.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['autoload']['psr-4']['Core\\'])->toBe([
        'framework/Core/',
        '.dalt/Core/',
    ])
        ->and(class_exists(Core\Container::class))->toBeTrue()
        ->and(class_exists(Core\CourseLoader::class))->toBeTrue();
});
