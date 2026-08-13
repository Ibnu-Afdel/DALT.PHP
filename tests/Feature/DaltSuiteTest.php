<?php

declare(strict_types=1);

test('the optional DALT suite runs with the normal project test command', function () {
    if (getenv('DALT_NESTED_TEST_RUN') === '1') {
        expect(true)->toBeTrue();

        return;
    }

    if (!is_dir(base_path('.dalt/tests'))) {
        expect(true)->toBeTrue();

        return;
    }

    if (!is_file(base_path('.dalt/vendor/autoload.php'))) {
        $this->markTestSkipped('DALT dependencies are not installed; run composer install --working-dir=.dalt.');
    }

    $environment = getenv();
    $environment = is_array($environment) ? [...$environment, 'DALT_NESTED_TEST_RUN' => '1'] : null;

    $process = proc_open(
        [
            PHP_BINARY,
            base_path('vendor/bin/pest'),
            base_path('.dalt/tests'),
            '--bootstrap=' . base_path('.dalt/bootstrap.php'),
            '--colors=never',
        ],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        base_path(),
        $environment,
        ['bypass_shell' => true],
    );

    expect($process)->not->toBeFalse();
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    expect(proc_close($process))->toBe(0, $stdout . $stderr);
});
