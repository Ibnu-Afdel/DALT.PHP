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
