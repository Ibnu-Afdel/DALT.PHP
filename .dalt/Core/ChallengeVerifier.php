<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;
use Throwable;

final class ChallengeVerifier
{
    private const TEST_TYPES = [
        'file_contains',
        'file_not_contains',
        'function_call',
        'route_exists',
        'route_order',
        'session_key',
    ];

    private readonly string $projectRoot;
    private readonly string $challengeDirectory;

    /**
     * Challenge specifications are trusted platform PHP. Learner files are only read as text.
     *
     * @param string $challengePath Relative catalog path, for compatibility with existing callers.
     */
    public function __construct(
        string $challengePath,
        private readonly bool $verifyAgainstBase = false,
        ?string $projectRoot = null,
    ) {
        $this->projectRoot = rtrim($projectRoot ?? base_path(), '/\\');
        if ($this->projectRoot === '' || !is_dir($this->projectRoot)) {
            throw new RuntimeException('The verification project root does not exist.');
        }

        $normalized = str_replace('\\', '/', rtrim($challengePath, '/\\'));
        if (preg_match('~\A\.dalt/course/challenges/([a-z0-9]+(?:-[a-z0-9]+)*)\z~D', $normalized, $matches) !== 1) {
            throw new RuntimeException('Challenge verification requires a safe catalog path.');
        }

        $this->challengeDirectory = $this->absolute($normalized);
    }

    /** @return array{status: string, message: string, hint: string, passed: int, failed: int, total: int, results: list<array{name: string, passed: bool, message: string, hint: string}>} */
    public function verify(): array
    {
        try {
            $tests = $this->loadTests();
        } catch (Throwable $exception) {
            return $this->errorResult($exception->getMessage());
        }

        $results = [];
        foreach ($tests as $name => $config) {
            try {
                $result = $this->runTest($config);
            } catch (ChallengeTargetMissingException $exception) {
                $result = ['passed' => false, 'message' => $exception->getMessage()];
            } catch (Throwable $exception) {
                return $this->errorResult("Test '{$name}' could not run: {$exception->getMessage()}", $results);
            }

            $results[] = [
                'name' => $name,
                'passed' => $result['passed'],
                'message' => $result['message'],
                'hint' => $result['passed'] ? '' : $config['hint'],
            ];
        }

        $passed = count(array_filter($results, static fn (array $result): bool => $result['passed']));
        $total = count($results);
        $failed = $total - $passed;

        return [
            'status' => $failed === 0 ? 'pass' : 'fail',
            'message' => $failed === 0
                ? 'All checks passed. Challenge completed successfully.'
                : "{$failed} of {$total} checks failed. Keep debugging!",
            'hint' => $this->firstHint($results),
            'passed' => $passed,
            'failed' => $failed,
            'total' => $total,
            'results' => $results,
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function loadTests(): array
    {
        $this->assertWithin($this->challengeDirectory, $this->projectRoot);
        $this->assertRegularFile($this->challengeDirectory, 'challenge directory', true);
        $testsFile = $this->challengeDirectory . '/tests.php';
        $this->assertRegularFile($testsFile, 'tests.php');

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            $tests = (static fn (string $file): mixed => require $file)($testsFile);
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            throw $exception;
        }
        if ($output !== '') {
            throw new RuntimeException('tests.php must not emit output while loading.');
        }
        if (!is_array($tests) || $tests === []) {
            throw new RuntimeException('tests.php must return a non-empty array of named checks.');
        }

        $validated = [];
        foreach ($tests as $name => $config) {
            if (!is_string($name) || preg_match('/\A[a-z0-9]+(?:_[a-z0-9]+)*\z/D', $name) !== 1) {
                throw new RuntimeException('Every verification check needs a unique snake_case name.');
            }
            if (!is_array($config)) {
                throw new RuntimeException("Check '{$name}' must be an array.");
            }
            $validated[$name] = $this->validateConfig($name, $config);
        }

        return $validated;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private function validateConfig(string $name, array $config): array
    {
        $type = $config['type'] ?? null;
        if (!is_string($type) || !in_array($type, self::TEST_TYPES, true)) {
            throw new RuntimeException("Check '{$name}' has an unknown test type.");
        }
        if (isset($config['hint']) && !is_string($config['hint'])) {
            throw new RuntimeException("Check '{$name}' has an invalid hint.");
        }
        $config['hint'] = trim($config['hint'] ?? 'Review the challenge README and the failed check.');

        if (in_array($type, ['file_contains', 'file_not_contains', 'function_call', 'session_key'], true)) {
            $this->requireString($config, 'file', $name);
            $this->assertAllowedSourcePath($config['file']);
            $this->assertSpecificationSource($config['file']);
        }
        if (in_array($type, ['file_contains', 'file_not_contains'], true)) {
            $this->requireString($config, 'search', $name);
            if (isset($config['include_comments']) && !is_bool($config['include_comments'])) {
                throw new RuntimeException("Check '{$name}' has an invalid include_comments flag.");
            }
            $config['include_comments'] ??= false;
        } elseif ($type === 'function_call') {
            $this->requireString($config, 'function', $name);
            if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $config['function']) !== 1) {
                throw new RuntimeException("Check '{$name}' has an invalid function name.");
            }
        } elseif ($type === 'session_key') {
            $this->requireString($config, 'key', $name);
        } elseif ($type === 'route_exists') {
            $this->assertSpecificationSource('routes/routes.php');
            $this->requireString($config, 'route', $name);
            $method = strtolower($config['method'] ?? 'get');
            if (!is_string($method) || !in_array($method, ['delete', 'get', 'patch', 'post', 'put'], true)) {
                throw new RuntimeException("Check '{$name}' has an unsupported route method.");
            }
            $config['method'] = $method;
        } else {
            $this->assertSpecificationSource('routes/routes.php');
            $this->requireString($config, 'specific', $name);
            $this->requireString($config, 'generic', $name);
        }

        return $config;
    }

    /** @param array<string, mixed> $config */
    private function requireString(array $config, string $field, string $name): void
    {
        if (!is_string($config[$field] ?? null) || trim($config[$field]) === '') {
            throw new RuntimeException("Check '{$name}' requires a non-empty '{$field}' string.");
        }
    }

    /** @param array<string, mixed> $config @return array{passed: bool, message: string} */
    private function runTest(array $config): array
    {
        return match ($config['type']) {
            'route_exists' => $this->testRouteExists($config),
            'route_order' => $this->testRouteOrder($config),
            'file_contains' => $this->testFileContains($config, true),
            'file_not_contains' => $this->testFileContains($config, false),
            'session_key' => $this->testSessionKey($config),
            'function_call' => $this->testFunctionCall($config),
        };
    }

    /** @param array<string, mixed> $config @return array{passed: bool, message: string} */
    private function testRouteExists(array $config): array
    {
        $routes = $this->routeRegistrations();
        $found = false;
        foreach ($routes as $route) {
            if ($route['method'] === $config['method'] && $route['path'] === $config['route']) {
                $found = true;
                break;
            }
        }

        return [
            'passed' => $found,
            'message' => $found
                ? "Route {$config['method']} {$config['route']} is registered."
                : "Route {$config['method']} {$config['route']} is not registered.",
        ];
    }

    /** @param array<string, mixed> $config @return array{passed: bool, message: string} */
    private function testRouteOrder(array $config): array
    {
        $routes = $this->routeRegistrations();
        $specific = null;
        $generic = null;
        foreach ($routes as $index => $route) {
            $specific ??= $route['path'] === $config['specific'] ? $index : null;
            $generic ??= $route['path'] === $config['generic'] ? $index : null;
        }
        $passed = is_int($specific) && is_int($generic) && $specific < $generic;

        return [
            'passed' => $passed,
            'message' => $passed
                ? "Route {$config['specific']} is registered before {$config['generic']}."
                : "Route {$config['specific']} must be registered before {$config['generic']}.",
        ];
    }

    /** @param array<string, mixed> $config @return array{passed: bool, message: string} */
    private function testFileContains(array $config, bool $expected): array
    {
        $content = $this->readTarget($config['file']);
        if (!$config['include_comments']) {
            $content = $this->withoutComments($content, $config['file']);
        }
        $contains = str_contains($content, $config['search']);
        $passed = $expected ? $contains : !$contains;

        return [
            'passed' => $passed,
            'message' => $passed
                ? ($expected ? 'File contains the expected code.' : 'File omits the problematic code.')
                : ($expected ? "File is missing expected code: {$config['search']}" : "File still contains problematic code: {$config['search']}"),
        ];
    }

    /** @param array<string, mixed> $config @return array{passed: bool, message: string} */
    private function testFunctionCall(array $config): array
    {
        $tokens = $this->significantPhpTokens($this->readTarget($config['file']));
        $passed = false;
        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_STRING || strcasecmp($token[1], $config['function']) !== 0) {
                continue;
            }
            $previous = $tokens[$index - 1] ?? null;
            $next = $tokens[$index + 1] ?? null;
            $notAFunctionCall = is_array($previous) && in_array($previous[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON], true);
            if (!$notAFunctionCall && $next === '(') {
                $passed = true;
                break;
            }
        }

        return [
            'passed' => $passed,
            'message' => $passed
                ? "Function {$config['function']}() is called."
                : "Function {$config['function']}() is not called.",
        ];
    }

    /** @param array<string, mixed> $config @return array{passed: bool, message: string} */
    private function testSessionKey(array $config): array
    {
        $tokens = $this->significantPhpTokens($this->readTarget($config['file']));
        $passed = false;
        foreach ($tokens as $index => $token) {
            $key = $tokens[$index + 2] ?? null;
            if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$_SESSION'
                && ($tokens[$index + 1] ?? null) === '['
                && is_array($key) && $key[0] === T_CONSTANT_ENCAPSED_STRING
                && $this->decodePhpString($key[1]) === $config['key']
                && ($tokens[$index + 3] ?? null) === ']'
            ) {
                $passed = true;
                break;
            }
        }

        return [
            'passed' => $passed,
            'message' => $passed
                ? "Session key '{$config['key']}' is used."
                : "Session key '{$config['key']}' is not used.",
        ];
    }

    /** @return list<array{method: string, path: string}> */
    private function routeRegistrations(): array
    {
        $tokens = $this->significantPhpTokens($this->readTarget('routes/routes.php'));
        $routes = [];
        $count = count($tokens);
        for ($index = 0; $index + 4 < $count; $index++) {
            $variable = $tokens[$index];
            $operator = $tokens[$index + 1];
            $method = $tokens[$index + 2];
            $path = $tokens[$index + 4];
            if (!is_array($variable) || $variable[0] !== T_VARIABLE || $variable[1] !== '$router'
                || !is_array($operator) || $operator[0] !== T_OBJECT_OPERATOR
                || !is_array($method) || $method[0] !== T_STRING
                || ($tokens[$index + 3] ?? null) !== '('
                || !is_array($path) || $path[0] !== T_CONSTANT_ENCAPSED_STRING
            ) {
                continue;
            }
            $routes[] = ['method' => strtolower($method[1]), 'path' => $this->decodePhpString($path[1])];
        }

        return $routes;
    }

    /** @return list<array{0: int, 1: string, 2?: int}|string> */
    private function significantPhpTokens(string $content): array
    {
        $tokens = token_get_all($content);

        return array_values(array_filter(
            $tokens,
            static fn (array|string $token): bool => !is_array($token)
                || !in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG, T_WHITESPACE], true),
        ));
    }

    private function withoutComments(string $content, string $path): string
    {
        if (str_ends_with($path, '.php')) {
            $result = '';
            foreach (token_get_all($content) as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $result .= is_array($token) ? $token[1] : $token;
            }

            return $result;
        }

        if (str_ends_with($path, '.sql')) {
            $content = preg_replace('~/\*.*?\*/~s', '', $content) ?? $content;

            return preg_replace('/^[ \t]*--.*$/m', '', $content) ?? $content;
        }

        return preg_replace('/^[ \t]*#.*$/m', '', $content) ?? $content;
    }

    private function readTarget(string $sourcePath): string
    {
        $this->assertAllowedSourcePath($sourcePath);
        $mapped = $this->verifyAgainstBase && str_starts_with($sourcePath, 'Http/controllers/')
            ? 'app/' . $sourcePath
            : $sourcePath;
        $path = $this->verifyAgainstBase
            ? $this->absolute($mapped)
            : $this->challengeDirectory . '/' . $sourcePath;
        $this->assertWithin($path, $this->verifyAgainstBase ? $this->projectRoot : $this->challengeDirectory);

        if (!file_exists($path) && !is_link($path)) {
            throw new ChallengeTargetMissingException("Target file '{$sourcePath}' does not exist.");
        }
        $this->assertRegularFile($path, "target '{$sourcePath}'");
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Target file '{$sourcePath}' could not be read.");
        }

        return $contents;
    }

    private function assertAllowedSourcePath(string $path): void
    {
        if (preg_match(
            '~\A(?:framework/Core/[A-Za-z][A-Za-z0-9]*(?:/[A-Za-z][A-Za-z0-9]*)*\.php|routes/routes\.php|Http/controllers/[a-z0-9][a-z0-9-]*(?:/[a-z0-9][a-z0-9-]*)*\.php|database/migrations/[0-9][A-Za-z0-9_-]*\.sql|Dockerfile|docker-compose\.yml|nginx/[a-z0-9][a-z0-9._-]*\.conf)\z~D',
            $path,
        ) !== 1) {
            throw new RuntimeException("Verification target '{$path}' is outside the challenge allowlist.");
        }
    }

    private function assertSpecificationSource(string $path): void
    {
        $source = $this->challengeDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $this->assertWithin($source, $this->challengeDirectory);
        $this->assertRegularFile($source, "specification source '{$path}'");
    }

    private function assertWithin(string $path, string $root): void
    {
        $root = rtrim($root, '/\\');
        if ($path === $root || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Verification target escapes its root.');
        }
        $parent = dirname($path);
        while ($parent !== $root) {
            if (is_link($parent)) {
                throw new RuntimeException('Verification targets cannot use symbolic-link directories.');
            }
            $parent = dirname($parent);
        }
    }

    private function assertRegularFile(string $path, string $label, bool $directory = false): void
    {
        if (is_link($path) || ($directory ? !is_dir($path) : !is_file($path))) {
            throw new RuntimeException("The {$label} must be a regular " . ($directory ? 'directory.' : 'file.'));
        }
        $status = lstat($path);
        if ($status === false || (!$directory && ($status['nlink'] ?? 1) > 1)) {
            throw new RuntimeException("The {$label} has an unsafe link count.");
        }
    }

    private function absolute(string $relative): string
    {
        return $this->projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function decodePhpString(string $literal): string
    {
        $quote = $literal[0];
        $value = substr($literal, 1, -1);

        return $quote === "'"
            ? str_replace(["\\\\", "\\'"], ["\\", "'"], $value)
            : stripcslashes($value);
    }

    /** @param list<array{name: string, passed: bool, message: string, hint: string}> $results */
    private function firstHint(array $results): string
    {
        foreach ($results as $result) {
            if (!$result['passed'] && $result['hint'] !== '') {
                return $result['hint'];
            }
        }

        return '';
    }

    /** @param list<array{name: string, passed: bool, message: string, hint: string}> $results */
    private function errorResult(string $message, array $results = []): array
    {
        $passed = count(array_filter($results, static fn (array $result): bool => $result['passed']));

        return [
            'status' => 'error',
            'message' => 'Verification configuration error: ' . $message,
            'hint' => 'The challenge verification specification needs maintainer attention.',
            'passed' => $passed,
            'failed' => count($results) - $passed,
            'total' => count($results),
            'results' => $results,
        ];
    }

    /** @param array{status?: mixed, passed?: mixed, total?: mixed} $result */
    public static function logResult(string $challenge, array $result): void
    {
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $challenge) !== 1
            || !is_string($result['status'] ?? null)
            || !in_array($result['status'], ['error', 'fail', 'pass'], true)
            || !is_int($result['passed'] ?? null)
            || !is_int($result['total'] ?? null)
            || $result['passed'] < 0
            || $result['total'] < $result['passed']
        ) {
            throw new RuntimeException('Cannot log an invalid challenge verification result.');
        }

        $logDir = base_path('storage/logs');
        if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
            throw new RuntimeException('Unable to create the challenge log directory.');
        }
        if (is_link($logDir)) {
            throw new RuntimeException('Challenge logs cannot use a symbolic-link directory.');
        }

        $logFile = $logDir . '/challenges.log';
        if (is_link($logFile) || (file_exists($logFile) && !is_file($logFile))) {
            throw new RuntimeException('The challenge verification log must be a regular file.');
        }
        if (is_file($logFile)) {
            $status = lstat($logFile);
            if ($status === false || ($status['nlink'] ?? 1) > 1) {
                throw new RuntimeException('The challenge verification log has an unsafe link count.');
            }
        }

        $entry = sprintf(
            "[%s] %s - %s (%d/%d)\n",
            date('Y-m-d H:i:s'),
            $challenge,
            $result['status'],
            $result['passed'],
            $result['total'],
        );
        $written = file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        if ($written !== strlen($entry)) {
            throw new RuntimeException('Unable to write the challenge verification log.');
        }
    }
}

final class ChallengeTargetMissingException extends RuntimeException
{
}
