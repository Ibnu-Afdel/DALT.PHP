<?php

declare(strict_types=1);

use Core\PlatformRemovalException;
use Core\PlatformRemovalManager;

function createP07Project(): string
{
    $root = sys_get_temp_dir() . '/dalt-p07-' . bin2hex(random_bytes(6));
    mkdir($root . '/.dalt/course', 0700, true);
    mkdir($root . '/app/Http/controllers', 0700, true);
    mkdir($root . '/routes', 0700, true);
    mkdir($root . '/resources/views', 0700, true);
    file_put_contents($root . '/.dalt/course/lesson.txt', 'platform');
    file_put_contents($root . '/app/Http/controllers/custom.php', 'learner controller');
    file_put_contents($root . '/routes/routes.php', 'learner routes');
    file_put_contents($root . '/resources/views/custom.php', 'learner view');
    file_put_contents($root . '/README.md', 'learner readme');
    file_put_contents($root . '/package.json', '{"learner":true}');
    file_put_contents($root . '/vite.config.mjs', 'learner vite config');

    return $root;
}

function removeP07Project(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }
    if (is_dir($path) && !is_link($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            removeP07Project($entry->getPathname());
        }
        rmdir($path);
        return;
    }
    unlink($path);
}

test('platform removal preserves every learner-facing project file', function () {
    $root = createP07Project();

    try {
        $message = (new PlatformRemovalManager($root))->remove();

        expect($message)->toContain('framework-core mode')
            ->and(file_exists($root . '/.dalt'))->toBeFalse()
            ->and(file_get_contents($root . '/app/Http/controllers/custom.php'))->toBe('learner controller')
            ->and(file_get_contents($root . '/routes/routes.php'))->toBe('learner routes')
            ->and(file_get_contents($root . '/resources/views/custom.php'))->toBe('learner view')
            ->and(file_get_contents($root . '/README.md'))->toBe('learner readme')
            ->and(file_get_contents($root . '/package.json'))->toBe('{"learner":true}')
            ->and(file_get_contents($root . '/vite.config.mjs'))->toBe('learner vite config')
            ->and(glob($root . '/.dalt-removing-*'))->toBe([]);

        expect((new PlatformRemovalManager($root))->remove())->toContain('already removed');
    } finally {
        removeP07Project($root);
    }
});

test('an installed auth example becomes learner owned without losing application code', function () {
    $root = createP07Project();
    mkdir($root . '/storage/framework/examples', 0700, true);
    mkdir($root . '/app/Http/controllers/session', 0700, true);
    file_put_contents($root . '/storage/framework/examples/auth.json', '{"schema":1}');
    file_put_contents($root . '/app/Http/controllers/session/create.php', 'learner changed auth code');
    file_put_contents($root . '/routes/routes.php', "learner routes\n// DALT auth example routes:begin\nrequire 'auth.php';\n// DALT auth example routes:end\n");

    try {
        $message = (new PlatformRemovalManager($root))->remove();

        expect($message)->toContain('preserved as learner-owned')
            ->and(file_get_contents($root . '/app/Http/controllers/session/create.php'))->toBe('learner changed auth code')
            ->and(file_get_contents($root . '/routes/routes.php'))->toContain('DALT auth example routes:begin')
            ->and(file_exists($root . '/storage/framework/examples/auth.json'))->toBeFalse();
    } finally {
        removeP07Project($root);
    }
});

test('platform removal unlinks nested links without traversing them', function () {
    $root = createP07Project();
    $outside = sys_get_temp_dir() . '/dalt-p07-outside-' . bin2hex(random_bytes(6));
    file_put_contents($outside, 'preserve');
    symlink($outside, $root . '/.dalt/outside-link');

    try {
        (new PlatformRemovalManager($root))->remove();

        expect(file_get_contents($outside))->toBe('preserve');
    } finally {
        removeP07Project($root);
        if (file_exists($outside)) {
            unlink($outside);
        }
    }
});

test('a symbolic link platform root is rejected without touching its target', function () {
    $root = createP07Project();
    $outside = sys_get_temp_dir() . '/dalt-p07-target-' . bin2hex(random_bytes(6));
    mkdir($outside, 0700);
    file_put_contents($outside . '/preserved.txt', 'preserve');
    removeP07Project($root . '/.dalt');
    symlink($outside, $root . '/.dalt');

    try {
        expect(fn () => (new PlatformRemovalManager($root))->remove())
            ->toThrow(PlatformRemovalException::class, 'unsafe .dalt')
            ->and(file_get_contents($outside . '/preserved.txt'))->toBe('preserve');
    } finally {
        removeP07Project($root);
        removeP07Project($outside);
    }
});

test('linked platform state is rejected before the platform is moved', function () {
    $root = createP07Project();
    $outside = sys_get_temp_dir() . '/dalt-p07-state-' . bin2hex(random_bytes(6));
    mkdir($root . '/storage/framework', 0700, true);
    mkdir($outside, 0700);
    file_put_contents($outside . '/auth.json', 'outside manifest');
    symlink($outside, $root . '/storage/framework/examples');

    try {
        expect(fn () => (new PlatformRemovalManager($root))->remove())
            ->toThrow(PlatformRemovalException::class, 'symbolic-link directory')
            ->and(file_exists($root . '/.dalt/course/lesson.txt'))->toBeTrue()
            ->and(file_get_contents($outside . '/auth.json'))->toBe('outside manifest');
    } finally {
        removeP07Project($root);
        removeP07Project($outside);
    }
});
