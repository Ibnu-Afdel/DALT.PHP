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
