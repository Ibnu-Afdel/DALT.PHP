<?php

declare(strict_types=1);

test('application logs can be redirected to an isolated environment path', function () {
    $logPath = sys_get_temp_dir() . '/dalt-log-test-' . bin2hex(random_bytes(8)) . '.log';
    $_ENV['APP_LOG_PATH'] = $logPath;

    try {
        app_log('request failed safely');

        expect(file_get_contents($logPath))->toMatch(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] request failed safely\n$/',
        );
    } finally {
        if (is_file($logPath)) {
            unlink($logPath);
        }
    }
});

test('environment values normalize conventional configuration tokens', function (
    mixed $raw,
    mixed $expected,
) {
    $_ENV['DALT_ENV_TEST'] = $raw;

    expect(env('DALT_ENV_TEST', 'default'))->toBe($expected);
})->with([
    'true' => ['true', true],
    'parenthesized true' => ['(true)', true],
    'false' => ['false', false],
    'parenthesized false' => ['(false)', false],
    'empty' => ['empty', ''],
    'null' => ['null', null],
    'ordinary string' => ['local', 'local'],
    'native value' => [42, 42],
]);

test('environment values use their default only when absent', function () {
    unset($_ENV['DALT_MISSING_ENV'], $_SERVER['DALT_MISSING_ENV']);

    expect(env('DALT_MISSING_ENV', 'fallback'))->toBe('fallback');
});

test('redirect creates a response without terminating execution', function () {
    $response = redirect('/next', 303);
    $continued = true;

    expect($continued)->toBeTrue()
        ->and($response)->toBeInstanceOf(Core\Response::class)
        ->and($response->status())->toBe(303)
        ->and($response->headers()['Location'])->toBe('/next');
});

test('url matching compares only the request path', function () {
    $_SERVER['REQUEST_URI'] = '/posts?page=2';

    expect(urlIs('/posts'))->toBeTrue()
        ->and(urlIs('/posts?page=2'))->toBeFalse();
});

test('base paths join root-relative and ordinary paths consistently', function () {
    expect(base_path())->toBe(rtrim(BASE_PATH, '/\\'))
        ->and(base_path('/config/app.php'))->toBe(rtrim(BASE_PATH, '/\\') . '/config/app.php');
});

test('old input preserves null and falls back when the flash value is malformed', function () {
    Core\Session::flash('old', ['name' => null]);

    expect(old('name', 'fallback'))->toBeNull()
        ->and(old('missing', 'fallback'))->toBe('fallback');

    Core\Session::flash('old', 'invalid');
    expect(old('name', 'fallback'))->toBe('fallback');
});
