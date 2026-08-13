<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;
use Throwable;

final class ChallengeVerifier
{
    private const TEST_TYPES = [
        'class_contract',
        'compose_config',
        'file_contains',
        'file_not_contains',
        'function_call',
        'handler_result',
        'route_exists',
        'route_order',
        'session_key',
    ];

    /** handler_result executes a controller, so only controllers are eligible. */
    private const HANDLER_RESULT_PATH = '~\AHttp/controllers/[a-z0-9][a-z0-9-]*(?:/[a-z0-9][a-z0-9-]*)*\.php\z~D';

    /**
     * class_contract loads learner code, so it is restricted to declaration-only
     * files. Controllers execute on require and are not eligible.
     */
    private const CLASS_CONTRACT_PATH = '~\Aframework/Core/[A-Za-z][A-Za-z0-9]*(?:/[A-Za-z][A-Za-z0-9]*)*\.php\z~D';

    /** compose_config shells out to `docker compose config`, so only the compose file is eligible. */
    private const COMPOSE_CONFIG_PATH = '~\Adocker-compose\.yml\z~D';

    /** Dotted path into the normalized compose config, e.g. "services.app.depends_on.db.condition". */
    private const COMPOSE_CONFIG_KEY_PATH = '~\A[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*\z~D';

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

        if (in_array($type, ['class_contract', 'compose_config', 'file_contains', 'file_not_contains', 'function_call', 'handler_result', 'session_key'], true)) {
            $this->requireString($config, 'file', $name);
            $this->assertAllowedSourcePath($config['file']);
            $this->assertSpecificationSource($config['file']);
        }
        if ($type === 'class_contract') {
            if (preg_match(self::CLASS_CONTRACT_PATH, $config['file']) !== 1) {
                throw new RuntimeException(
                    "Check '{$name}' may only inspect a framework/Core class file; requiring anything else executes learner code.",
                );
            }
            $this->requireString($config, 'class', $name);
            $config['class'] = $this->validClassName($config['class'], $name, 'class');
            $config['implements'] = $this->validClassList($config, 'implements', $name);
            $config['methods'] = $this->validMethodList($config, $name);

            if ($config['implements'] === [] && $config['methods'] === []) {
                throw new RuntimeException("Check '{$name}' must assert at least one interface or method.");
            }
        }
        if ($type === 'compose_config') {
            if (preg_match(self::COMPOSE_CONFIG_PATH, $config['file']) !== 1) {
                throw new RuntimeException("Check '{$name}' may only inspect docker-compose.yml.");
            }
            $this->requireString($config, 'path', $name);
            if (preg_match(self::COMPOSE_CONFIG_KEY_PATH, $config['path']) !== 1) {
                throw new RuntimeException("Check '{$name}' has an invalid 'path'.");
            }
            $config = $this->validComposeAssertion($config, $name);
        }
        if ($type === 'handler_result') {
            if (preg_match(self::HANDLER_RESULT_PATH, $config['file']) !== 1) {
                throw new RuntimeException("Check '{$name}' may only execute a controller under Http/controllers.");
            }
            $config['driver'] = $this->validDriver($config, $name);
            $config['seed'] = $this->validSqlList($config, $name, $config['driver']);
            $config['query'] = $this->validScalarMap($config, 'query', $name);
            $config['input'] = $this->validScalarMap($config, 'input', $name);
            $config['route'] = $this->validScalarMap($config, 'route', $name);
            $config['session'] = $this->validSessionSeed($config, $name);
            $config['inspect'] = $this->validOptionalSql($config, 'inspect', $name);
            $config['expect'] = $this->validExpectation($config, $name);
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
        } elseif ($type === 'route_order') {
            $this->assertSpecificationSource('routes/routes.php');
            $this->requireString($config, 'specific', $name);
            $this->requireString($config, 'generic', $name);
        }

        return $config;
    }

    private function validClassName(string $value, string $name, string $field): string
    {
        $normalized = ltrim(trim($value), '\\');

        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*\z/D', $normalized) !== 1) {
            throw new RuntimeException("Check '{$name}' has an invalid '{$field}' class name.");
        }

        return $normalized;
    }

    /** @param array<string, mixed> $config @return list<string> */
    private function validClassList(array $config, string $field, string $name): array
    {
        $values = $config[$field] ?? [];

        if (!is_array($values)) {
            throw new RuntimeException("Check '{$name}' requires '{$field}' to be a list of class names.");
        }

        $result = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new RuntimeException("Check '{$name}' requires '{$field}' to contain only strings.");
            }
            $result[] = $this->validClassName($value, $name, $field);
        }

        return $result;
    }

    /** @param array<string, mixed> $config @return list<string> */
    private function validMethodList(array $config, string $name): array
    {
        $values = $config['methods'] ?? [];

        if (!is_array($values)) {
            throw new RuntimeException("Check '{$name}' requires 'methods' to be a list of method names.");
        }

        $result = [];
        foreach ($values as $value) {
            if (!is_string($value) || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $value) !== 1) {
                throw new RuntimeException("Check '{$name}' has an invalid method name in 'methods'.");
            }
            $result[] = $value;
        }

        return $result;
    }

    /**
     * sqlite handler_result checks build a database from nothing but 'seed', so an
     * empty list can never be reproducible. pgsql checks connect to schema and data
     * the learner already built by hand (see DECISIONS.md D-09) and often assert on
     * that live state directly — e.g. db-slow-queries reads pg_indexes left behind
     * by the learner's own `php artisan migrate`, with nothing to seed at all.
     *
     * @param array<string, mixed> $config @return list<string>
     */
    private function validSqlList(array $config, string $name, string $driver): array
    {
        $values = $config['seed'] ?? [];

        if (!is_array($values)) {
            throw new RuntimeException("Check '{$name}' requires 'seed' to be a list of SQL strings.");
        }
        if ($values === [] && $driver === 'sqlite') {
            throw new RuntimeException("Check '{$name}' requires a non-empty 'seed' list so the result is reproducible.");
        }

        $result = [];
        foreach ($values as $value) {
            if (!is_string($value) || trim($value) === '') {
                throw new RuntimeException("Check '{$name}' requires 'seed' to contain non-empty SQL strings.");
            }
            $result[] = $value;
        }

        return $result;
    }

    /** @param array<string, mixed> $config */
    private function validDriver(array $config, string $name): string
    {
        $driver = $config['driver'] ?? 'sqlite';
        if (!is_string($driver) || !in_array($driver, ['sqlite', 'pgsql'], true)) {
            throw new RuntimeException("Check '{$name}' has an unsupported 'driver'.");
        }

        return $driver;
    }

    /** @param array<string, mixed> $config @return array<string, string> */
    private function validScalarMap(array $config, string $field, string $name): array
    {
        $values = $config[$field] ?? [];

        if (!is_array($values)) {
            throw new RuntimeException("Check '{$name}' requires '{$field}' to be a map of scalar values.");
        }

        $result = [];
        foreach ($values as $key => $value) {
            if (!is_string($key) || $key === '' || !is_scalar($value)) {
                throw new RuntimeException("Check '{$name}' has an invalid entry in '{$field}'.");
            }
            $result[$key] = (string) $value;
        }

        return $result;
    }

    /**
     * Pre-populates $_SESSION before the handler runs. Unlike 'seed' this is not
     * SQL, so it accepts arbitrary nested arrays (flash data is a nested bag),
     * not just a flat scalar map.
     *
     * @param array<string, mixed> $config @return array<string, mixed>
     */
    private function validSessionSeed(array $config, string $name): array
    {
        $value = $config['session'] ?? [];
        if (!is_array($value)) {
            throw new RuntimeException("Check '{$name}' requires 'session' to be an array.");
        }

        return $value;
    }

    /** @param array<string, mixed> $config */
    private function validOptionalSql(array $config, string $field, string $name): ?string
    {
        $value = $config[$field] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException("Check '{$name}' requires '{$field}' to be a non-empty SQL string when set.");
        }

        return $value;
    }

    /**
     * A compose_config check names exactly one assertion against the value at
     * 'path'. Resist growing this into a query language: three flat modes
     * cover every case Phase 01 needs.
     *
     * @param array<string, mixed> $config @return array<string, mixed>
     */
    private function validComposeAssertion(array $config, string $name): array
    {
        $modes = array_intersect_key($config, ['equals' => true, 'exists' => true, 'contains' => true]);
        if (count($modes) !== 1) {
            throw new RuntimeException("Check '{$name}' must set exactly one of 'equals', 'exists', or 'contains'.");
        }

        if (array_key_exists('equals', $modes)) {
            if (!is_scalar($config['equals'])) {
                throw new RuntimeException("Check '{$name}' requires 'equals' to be a scalar value.");
            }
            $config['equals'] = (string) $config['equals'];
        } elseif (array_key_exists('exists', $modes)) {
            if (!is_bool($config['exists'])) {
                throw new RuntimeException("Check '{$name}' requires 'exists' to be a boolean.");
            }
        } else {
            $this->requireString($config, 'contains', $name);
        }

        return $config;
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private function validExpectation(array $config, string $name): array
    {
        $expect = $config['expect'] ?? null;

        if (!is_array($expect) || $expect === []) {
            throw new RuntimeException("Check '{$name}' requires an 'expect' block describing the response.");
        }

        $allowed = ['status', 'count', 'count_key', 'contains', 'not_contains', 'before', 'after', 'source'];
        foreach (array_keys($expect) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new RuntimeException("Check '{$name}' has an unsupported expectation '{$key}'.");
            }
        }
        if (isset($expect['status']) && (!is_int($expect['status']) || $expect['status'] < 100 || $expect['status'] > 599)) {
            throw new RuntimeException("Check '{$name}' has an invalid expected status.");
        }
        if (isset($expect['count']) && (!is_int($expect['count']) || $expect['count'] < 0)) {
            throw new RuntimeException("Check '{$name}' has an invalid expected count.");
        }
        foreach (['count_key', 'contains', 'not_contains', 'before', 'after'] as $field) {
            if (isset($expect[$field]) && (!is_string($expect[$field]) || $expect[$field] === '')) {
                throw new RuntimeException("Check '{$name}' has an invalid '{$field}' expectation.");
            }
        }
        if (isset($expect['count_key']) && !isset($expect['count'])) {
            throw new RuntimeException("Check '{$name}' sets 'count_key' without a 'count'.");
        }
        if (isset($expect['before']) !== isset($expect['after'])) {
            throw new RuntimeException("Check '{$name}' must set 'before' and 'after' together — one names what must appear earlier in the response than the other.");
        }
        if (isset($expect['source'])) {
            if (!in_array($expect['source'], ['body', 'session', 'inspect'], true)) {
                throw new RuntimeException("Check '{$name}' has an unsupported expectation 'source'.");
            }
            if ($expect['source'] === 'inspect' && $config['inspect'] === null) {
                throw new RuntimeException("Check '{$name}' expects from 'inspect' but sets no 'inspect' query.");
            }
        }

        return $expect;
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
            'class_contract' => $this->testClassContract($config),
            'compose_config' => $this->testComposeConfig($config),
            'handler_result' => $this->testHandlerResult($config),
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

    /**
     * Executes the learner's class in a separate process and reports what it
     * actually declares. A parse error or a missing dependency is a fatal that
     * cannot be caught in-process, which is precisely the failure this check
     * exists to surface, so it must not run here.
     *
     * @param array<string, mixed> $config
     * @return array{passed: bool, message: string}
     */
    private function testClassContract(array $config): array
    {
        $path = $this->resolveTargetPath($config['file']);
        $probe = __DIR__ . DIRECTORY_SEPARATOR . 'probe-class-contract.php';
        $this->assertRegularFile($probe, 'class contract probe');

        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($probe)
            . ' ' . escapeshellarg($this->projectRoot)
            . ' ' . escapeshellarg($path)
            . ' ' . escapeshellarg($config['class'])
            . ' 2>/dev/null';

        $output = shell_exec($command);
        $report = is_string($output) ? json_decode(trim($output), true) : null;

        if (!is_array($report) || !isset($report['ok'])) {
            return [
                'passed' => false,
                'message' => "Loading {$config['file']} failed outright, so {$config['class']} could not be inspected. "
                    . 'Check the file parses and that every class it references exists.',
            ];
        }

        if ($report['ok'] !== true) {
            return [
                'passed' => false,
                'message' => is_string($report['error'] ?? null)
                    ? $report['error']
                    : "Class {$config['class']} could not be inspected.",
            ];
        }

        $missingInterfaces = array_values(array_udiff(
            $config['implements'],
            $report['implements'] ?? [],
            static fn (string $a, string $b): int => strcasecmp($a, $b),
        ));

        if ($missingInterfaces !== []) {
            return [
                'passed' => false,
                'message' => "{$config['class']} does not implement " . implode(', ', $missingInterfaces) . '.',
            ];
        }

        $missingMethods = array_values(array_udiff(
            $config['methods'],
            $report['methods'] ?? [],
            static fn (string $a, string $b): int => strcasecmp($a, $b),
        ));

        if ($missingMethods !== []) {
            return [
                'passed' => false,
                'message' => "{$config['class']} is missing the method(s): " . implode(', ', $missingMethods) . '.',
            ];
        }

        return ['passed' => true, 'message' => "{$config['class']} satisfies the expected contract."];
    }

    /**
     * Normalizes the compose file with `docker compose config` and asserts on
     * the parsed structure at 'path'. This is what distinguishes "the magic
     * word is present" from "the file structurally means what it needs to
     * mean": a keyword sitting under the wrong service, or inside a comment,
     * cannot satisfy this the way it could satisfy a file_contains check.
     *
     * @param array<string, mixed> $config
     * @return array{passed: bool, message: string}
     */
    private function testComposeConfig(array $config): array
    {
        $path = $this->resolveTargetPath($config['file']);
        $probe = __DIR__ . DIRECTORY_SEPARATOR . 'probe-compose-config.php';
        $this->assertRegularFile($probe, 'compose config probe');

        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($probe)
            . ' ' . escapeshellarg($path)
            . ' 2>/dev/null';

        $output = shell_exec($command);
        $report = is_string($output) ? json_decode(trim($output), true) : null;

        if (!is_array($report) || !isset($report['ok'])) {
            return [
                'passed' => false,
                'message' => "Running `docker compose config` on {$config['file']} produced no usable result.",
            ];
        }
        if ($report['ok'] !== true) {
            return [
                'passed' => false,
                'message' => is_string($report['error'] ?? null) ? $report['error'] : 'Docker Compose could not evaluate the file.',
            ];
        }

        [$found, $value] = $this->composeConfigLookup(is_array($report['config'] ?? null) ? $report['config'] : [], $config['path']);

        if (isset($config['exists'])) {
            $passed = $config['exists'] ? $found : !$found;

            return [
                'passed' => $passed,
                'message' => $passed
                    ? ($config['exists'] ? "{$config['path']} is present." : "{$config['path']} is absent, as expected.")
                    : ($config['exists'] ? "{$config['path']} is missing from the compose configuration." : "{$config['path']} should not be set, but it is."),
            ];
        }

        if (!$found) {
            return ['passed' => false, 'message' => "{$config['path']} is missing from the compose configuration."];
        }

        if (isset($config['equals'])) {
            $passed = is_scalar($value) && (string) $value === $config['equals'];

            return [
                'passed' => $passed,
                'message' => $passed
                    ? "{$config['path']} equals '{$config['equals']}'."
                    : "{$config['path']} is " . $this->preview((string) json_encode($value, JSON_UNESCAPED_SLASHES)) . ", expected '{$config['equals']}'.",
            ];
        }

        $haystack = is_string($value) ? $value : (string) json_encode($value, JSON_UNESCAPED_SLASHES);
        $passed = str_contains($haystack, $config['contains']);

        return [
            'passed' => $passed,
            'message' => $passed
                ? "{$config['path']} contains '{$config['contains']}'."
                : "{$config['path']} does not contain '{$config['contains']}'. It is: " . $this->preview($haystack),
        ];
    }

    /**
     * Walks a dotted path through the normalized compose config.
     *
     * @param array<string, mixed> $config
     * @return array{0: bool, 1: mixed}
     */
    private function composeConfigLookup(array $config, string $path): array
    {
        $value = $config;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return [false, null];
            }
            $value = $value[$segment];
        }

        return [true, $value];
    }

    /**
     * Runs the controller against a seeded throwaway database and checks the
     * response it actually produced. A fix written into dead code changes the
     * source and leaves this unchanged, which is the whole point.
     *
     * @param array<string, mixed> $config
     * @return array{passed: bool, message: string}
     */
    private function testHandlerResult(array $config): array
    {
        $path = $this->resolveTargetPath($config['file']);
        $probe = __DIR__ . DIRECTORY_SEPARATOR . 'probe-handler-result.php';
        $this->assertRegularFile($probe, 'handler result probe');

        $spec = json_encode([
            'seed' => $config['seed'],
            'query' => $config['query'],
            'input' => $config['input'],
            'route' => $config['route'],
            'session' => $config['session'],
            'inspect' => $config['inspect'],
            'driver' => $config['driver'],
        ]);

        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($probe)
            . ' ' . escapeshellarg($this->projectRoot)
            . ' ' . escapeshellarg($path)
            . ' ' . escapeshellarg((string) $spec)
            . ' 2>/dev/null';

        $output = shell_exec($command);
        $report = is_string($output) ? json_decode(trim($output), true) : null;

        if (!is_array($report) || !isset($report['ok'])) {
            return [
                'passed' => false,
                'message' => "Running {$config['file']} produced no usable result. It may have exited, printed something unexpected, or failed fatally.",
            ];
        }
        if ($report['ok'] !== true) {
            return [
                'passed' => false,
                'message' => is_string($report['error'] ?? null) ? $report['error'] : 'The handler could not be executed.',
            ];
        }

        return $this->compareExpectation(
            $config['expect'],
            (int) $report['status'],
            (string) $report['body'],
            is_array($report['session'] ?? null) ? $report['session'] : [],
            is_array($report['inspect'] ?? null) ? $report['inspect'] : null,
        );
    }

    /**
     * Compares the probe's report against the check's 'expect' block. 'source'
     * picks which part of the report 'contains'/'not_contains'/'count' read from
     * — 'body' (default) is the HTTP response; 'session' and 'inspect' exist
     * because some bugs (stale flash data, a transaction that partially
     * commits) never appear in the response at all. 'status' always reads the
     * HTTP status regardless of 'source'; it is a property of the response,
     * not of what the check is inspecting.
     *
     * 'before'/'after' assert relative order rather than mere presence — added
     * for db-broken-fts's "ordered by relevance, not recency" criterion, where
     * substring presence alone can't distinguish a ranked result set from one
     * sorted by an unrelated column.
     *
     * @param array<string, mixed> $expect
     * @param array<string, mixed> $session
     * @param list<array<string, mixed>>|null $inspect
     * @return array{passed: bool, message: string}
     */
    private function compareExpectation(array $expect, int $status, string $body, array $session, ?array $inspect): array
    {
        if (isset($expect['status']) && $status !== $expect['status']) {
            return ['passed' => false, 'message' => "Expected status {$expect['status']}, the handler returned {$status}."];
        }

        $source = $expect['source'] ?? 'body';
        $haystack = match ($source) {
            'body' => $body,
            'session' => (string) json_encode($session, JSON_UNESCAPED_SLASHES),
            'inspect' => (string) json_encode($inspect, JSON_UNESCAPED_SLASHES),
        };
        $label = match ($source) {
            'body' => 'response',
            'session' => 'session state',
            'inspect' => 'inspection query',
        };

        if (isset($expect['contains']) && !str_contains($haystack, $expect['contains'])) {
            return ['passed' => false, 'message' => "The {$label} is missing '{$expect['contains']}'. It was: " . $this->preview($haystack)];
        }
        if (isset($expect['not_contains']) && str_contains($haystack, $expect['not_contains'])) {
            return ['passed' => false, 'message' => "The {$label} should not contain '{$expect['not_contains']}', but it does."];
        }
        if (isset($expect['before'])) {
            $beforePosition = strpos($haystack, $expect['before']);
            $afterPosition = strpos($haystack, $expect['after']);
            if ($beforePosition === false || $afterPosition === false) {
                return ['passed' => false, 'message' => "The {$label} must contain both '{$expect['before']}' and '{$expect['after']}'. It was: " . $this->preview($haystack)];
            }
            if ($beforePosition >= $afterPosition) {
                return ['passed' => false, 'message' => "The {$label} has '{$expect['after']}' before '{$expect['before']}', not after."];
            }
        }
        if (isset($expect['count'])) {
            $decoded = match ($source) {
                'body' => json_decode($body, true),
                'session' => $session,
                'inspect' => $inspect,
            };
            $rows = isset($expect['count_key']) && is_array($decoded) ? ($decoded[$expect['count_key']] ?? null) : $decoded;

            if (!is_array($rows)) {
                $where = isset($expect['count_key'])
                    ? "a list under the key '{$expect['count_key']}'"
                    : 'a list';

                return ['passed' => false, 'message' => "Expected {$where} in the {$label}. It was: " . $this->preview($haystack)];
            }
            if (count($rows) !== $expect['count']) {
                return [
                    'passed' => false,
                    'message' => "Expected {$expect['count']} row(s) in the {$label}, found " . count($rows) . '.',
                ];
            }
        }

        return ['passed' => true, 'message' => 'The handler produced the expected response.'];
    }

    private function preview(string $body): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $body) ?? $body);

        return mb_strlen($normalized) > 120 ? mb_substr($normalized, 0, 117) . '...' : $normalized;
    }

    private function resolveTargetPath(string $sourcePath): string
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

        return $path;
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
