<?php

declare(strict_types=1);

use Core\App;
use Core\Container;
use Core\View;

function fixtureViewRenderer(): View
{
    return new View([
        base_path('tests/Fixtures/views/app'),
        base_path('tests/Fixtures/views/platform'),
    ]);
}

test('views render attributes in an isolated template scope', function () {
    $content = fixtureViewRenderer()->render('greeting.view.php', [
        'name' => '<DALT>',
        '__template' => 'cannot replace the resolved template',
    ]);

    expect(trim($content))->toBe('Hello, &lt;DALT&gt;!');
});

test('application views take precedence over platform fallbacks', function () {
    expect(trim(fixtureViewRenderer()->render('shared.view.php')))->toBe('application');
});

test('the default renderer uses the application view directory', function () {
    expect((new View())->resolve('welcome.view.php'))->toBe(realpath(base_path('resources/views/welcome.view.php')));
});

test('view paths cannot escape a configured root', function (string $path) {
    fixtureViewRenderer()->resolve($path);
})->with([
    '../app/shared.view.php',
    '/etc/passwd',
    'folder/./view.php',
    'folder\\..\\view.php',
])->throws(InvalidArgumentException::class);

test('missing views fail with a clear exception', function () {
    fixtureViewRenderer()->render('missing.view.php');
})->throws(RuntimeException::class, 'View not found: missing.view.php');

test('failed rendering restores the output buffer stack', function () {
    $level = ob_get_level();

    try {
        fixtureViewRenderer()->render('throwing.view.php');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('render failed');
    }

    expect(ob_get_level())->toBe($level);
});

test('the view helper emits rendered content and supports nested components', function () {
    App::setContainer(new Container());
    App::instance(View::class, fixtureViewRenderer());
    ob_start();

    try {
        $returned = view('nested.view.php', ['name' => 'Ada']);
        $emitted = (string) ob_get_clean();
    } catch (Throwable $exception) {
        ob_end_clean();
        throw $exception;
    }

    $normalizeWhitespace = static fn (string $value): string => (string) preg_replace('/\s+/', ' ', trim($value));

    expect($normalizeWhitespace($returned))->toBe('Before Hello, Ada! After')
        ->and($normalizeWhitespace($emitted))->toBe('Before Hello, Ada! After');
});
