<?php

declare(strict_types=1);

namespace Tests\Support;

use JsonException;
use RuntimeException;

final class ApplicationTestClient
{
    private const RESULT_MARKER = '__DALT_TEST_RESULT__';

    public function __construct(private readonly string $projectRoot = BASE_PATH)
    {
    }

    /**
     * Run the real front controller in a child process so constants, sessions,
     * headers, and fatal errors cannot leak into the PHPUnit process.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $input
     * @param array<string, mixed> $server
     */
    public function request(
        string $method,
        string $uri,
        array $query = [],
        array $input = [],
        array $server = [],
    ): ApplicationResponse {
        $payload = base64_encode(json_encode([
            'method' => $method,
            'uri' => $uri,
            'query' => $query,
            'input' => $input,
            'server' => $server,
            'project_root' => $this->projectRoot,
        ], JSON_THROW_ON_ERROR));

        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/run-application.php', $payload],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            BASE_PATH,
            null,
            ['bypass_shell' => true],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start the DALT application test process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $markerPosition = strrpos($stdout, self::RESULT_MARKER);

        if ($markerPosition === false) {
            throw new RuntimeException(
                "Application test process returned no result metadata.\n"
                . "Exit code: {$exitCode}\n"
                . "STDOUT: {$stdout}\n"
                . "STDERR: {$stderr}",
            );
        }

        $encodedResult = substr($stdout, $markerPosition + strlen(self::RESULT_MARKER));

        try {
            /** @var array{status: int, body: string, headers: list<string>, error: array{type: int, message: string, file: string, line: int}|null} $result */
            $result = json_decode(base64_decode($encodedResult, true), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Application test process returned invalid result metadata.', 0, $exception);
        }

        return new ApplicationResponse(
            exitCode: $exitCode,
            statusCode: $result['status'],
            body: $result['body'],
            headers: $result['headers'],
            error: $result['error'],
            stderr: $stderr,
        );
    }
}
