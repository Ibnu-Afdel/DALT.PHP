<?php

declare(strict_types=1);

use Core\AuthScaffoldException;
use Core\AuthScaffoldManager;

function copyP06Tree(string $source, string $destination): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        $target = $destination . '/' . $relative;
        if ($item->isDir()) {
            mkdir($target, 0755, true);
        } else {
            $directory = dirname($target);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            copy($item->getPathname(), $target);
        }
    }
}

function removeP06Tree(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($directory);
}

function createP06Project(string $routes = "<?php\n\nglobal \$router;\n\n\$router->get('/', 'welcome.php');\n"): string
{
    $project = sys_get_temp_dir() . '/dalt-p06-' . bin2hex(random_bytes(6));
    mkdir($project . '/routes', 0755, true);
    mkdir($project . '/.dalt/stubs/auth', 0755, true);
    file_put_contents($project . '/routes/routes.php', $routes);
    copyP06Tree(base_path('.dalt/stubs/auth'), $project . '/.dalt/stubs/auth');

    return $project;
}

test('auth scaffold install is transactional repeatable and route scoped', function () {
    $project = createP06Project();
    $manager = new AuthScaffoldManager($project);

    try {
        $first = $manager->install();
        $routes = file_get_contents($project . '/routes/routes.php');

        expect($first)->toContain('Auth example installed')
            ->and(file_exists($project . '/storage/framework/examples/auth.json'))->toBeTrue()
            ->and(file_exists($project . '/routes/auth.php'))->toBeTrue()
            ->and($routes)->toContain("require base_path('routes/auth.php')")
            ->and(substr_count($routes, 'DALT auth example routes:begin'))->toBe(1)
            ->and(file_get_contents($project . '/routes/auth.php'))->toContain("->post('/session'")
            ->and(file_get_contents($project . '/routes/auth.php'))->toContain("['guest', 'csrf']")
            ->and(file_get_contents($project . '/routes/auth.php'))->toContain("['auth', 'csrf']");

        expect($manager->install())->toContain('already installed and current')
            ->and(file_get_contents($project . '/routes/routes.php'))->toBe($routes);
    } finally {
        removeP06Tree($project);
    }
});

test('install rejects file and route collisions before changing anything', function (string $kind) {
    $routeSource = $kind === 'route'
        ? "<?php\n\nglobal \$router;\n\n\$router->get('/login', 'custom.php');\n"
        : "<?php\n\nglobal \$router;\n\n\$router->get('/', 'welcome.php');\n";
    $project = createP06Project($routeSource);
    if ($kind === 'file') {
        mkdir($project . '/app/Http/controllers/session', 0755, true);
        file_put_contents($project . '/app/Http/controllers/session/create.php', 'learner work');
    }

    try {
        expect(fn () => (new AuthScaffoldManager($project))->install())
            ->toThrow(AuthScaffoldException::class, $kind === 'route' ? 'existing routes' : 'overwrite existing files')
            ->and(file_get_contents($project . '/routes/routes.php'))->toBe($routeSource)
            ->and(file_exists($project . '/storage/framework/examples/auth.json'))->toBeFalse();
    } finally {
        removeP06Tree($project);
    }
})->with(['file', 'route']);

test('commented route examples do not create false installation conflicts', function () {
    $routes = "<?php\n\n// \$router->get('/login', 'example.php');\n\$router->get('/', 'welcome.php');\n";
    $project = createP06Project($routes);

    try {
        expect((new AuthScaffoldManager($project))->install())->toContain('Auth example installed');
    } finally {
        removeP06Tree($project);
    }
});

test('install cannot write through a symbolic link outside the project', function () {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('Symbolic-link creation is not consistently available on Windows.');
    }

    $project = createP06Project();
    $outside = sys_get_temp_dir() . '/dalt-p06-outside-' . bin2hex(random_bytes(6));
    mkdir($project . '/app/Http/controllers', 0755, true);
    mkdir($outside, 0755, true);
    symlink($outside, $project . '/app/Http/controllers/session');

    try {
        expect(fn () => (new AuthScaffoldManager($project))->install())
            ->toThrow(AuthScaffoldException::class, 'symbolic-link directory')
            ->and(scandir($outside))->toBe(['.', '..'])
            ->and(file_get_contents($project . '/routes/routes.php'))->not->toContain('DALT auth example routes');
    } finally {
        removeP06Tree($project);
        rmdir($outside);
    }
});

test('update replaces only scaffold-owned files that remain untouched', function () {
    $project = createP06Project();
    $manager = new AuthScaffoldManager($project);

    try {
        $manager->install();
        $source = $project . '/.dalt/stubs/auth/Http/controllers/session/create.php';
        file_put_contents($source, file_get_contents($source) . "\n// updated scaffold\n");

        expect($manager->install())->toContain('example:update auth')
            ->and($manager->update())->toContain('Auth example updated')
            ->and(file_get_contents($project . '/app/Http/controllers/session/create.php'))->toContain('updated scaffold');

        file_put_contents($project . '/app/Http/controllers/session/create.php', "<?php\n// learner customization\n");
        expect(fn () => $manager->update())->toThrow(AuthScaffoldException::class, 'missing or modified');
    } finally {
        removeP06Tree($project);
    }
});

test('uninstall preserves modified generated work unless force is explicit', function () {
    $project = createP06Project();
    $manager = new AuthScaffoldManager($project);

    try {
        $manager->install();
        $customized = $project . '/resources/views/auth/login.view.php';
        file_put_contents($customized, "<?php\n// learner customization\n");

        expect(fn () => $manager->uninstall())->toThrow(AuthScaffoldException::class, 'missing or modified')
            ->and(file_exists($customized))->toBeTrue()
            ->and(file_get_contents($project . '/routes/routes.php'))->toContain('DALT auth example routes:begin');

        expect($manager->uninstall(true))->toContain('including modified generated files')
            ->and(file_exists($customized))->toBeFalse()
            ->and(file_exists($project . '/routes/auth.php'))->toBeFalse()
            ->and(file_exists($project . '/storage/framework/examples/auth.json'))->toBeFalse()
            ->and(file_get_contents($project . '/routes/routes.php'))->not->toContain('DALT auth example routes');
    } finally {
        removeP06Tree($project);
    }
});

test('auth scaffold views render independently and escape flashed values', function () {
    $_SESSION = [
        '_csrf' => str_repeat('a', 64),
        '_flash' => [
            'new' => [],
            'old' => [
                'errors' => ['email' => '<script>alert(1)</script>'],
                'old' => ['email' => '" onfocus="alert(2)'],
                'success' => '<b>Account ready</b>',
            ],
        ],
    ];

    ob_start();
    require base_path('.dalt/stubs/auth/resources/views/auth/login.view.php');
    $html = ob_get_clean();

    expect($html)->toContain('<!doctype html>')
        ->and($html)->toContain('autocomplete="current-password"')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->and($html)->toContain('&quot; onfocus=&quot;alert(2)')
        ->and($html)->toContain('&lt;b&gt;Account ready&lt;/b&gt;')
        ->and($html)->not->toContain('<script>alert(1)</script>');
});
