<?php

namespace Core;

class ChallengeVerifier
{
    private string $challengePath;
    private bool $verifyAgainstBase;
    private array $tests = [];
    private array $results = [];

    /**
     * @param string $challengePath Path to challenge (e.g. .dalt/course/challenges/broken-auth)
     * @param bool $verifyAgainstBase When true, check files in the base app instead of challenge folder
     */
    public function __construct(string $challengePath, bool $verifyAgainstBase = false)
    {
        $this->challengePath = rtrim($challengePath, '/');
        $this->verifyAgainstBase = $verifyAgainstBase;
    }

    /**
     * Resolve file path for testing. When verifyAgainstBase, maps Http/controllers -> app/Http/controllers.
     */
    private function resolveFilePath(string $relativePath): string
    {
        if ($this->verifyAgainstBase) {
            $mapped = str_starts_with($relativePath, 'Http/controllers/')
                ? 'app/' . $relativePath
                : $relativePath;
            return base_path($mapped);
        }
        return base_path($this->challengePath . '/' . $relativePath);
    }

    private function resolveRoutesFile(): string
    {
        if ($this->verifyAgainstBase) {
            return base_path('routes/routes.php');
        }
        return base_path($this->challengePath . '/routes/routes.php');
    }

    /**
     * Verify a challenge by running its test suite
     */
    public function verify(): array
    {
        // Load test specification
        $testsFile = base_path($this->challengePath . '/tests.php');
        
        if (!file_exists($testsFile)) {
            return [
                'status' => 'error',
                'message' => 'No tests.php found for this challenge',
                'hint' => 'Challenge verification not configured',
                'passed' => 0,
                'failed' => 0,
                'total' => 0
            ];
        }

        // Load tests safely
        $this->tests = require $testsFile;

        // Run all tests
        foreach ($this->tests as $testName => $testConfig) {
            $this->runTest($testName, $testConfig);
        }

        // Calculate results
        $passed = count(array_filter($this->results, fn($r) => $r['passed']));
        $failed = count($this->results) - $passed;
        $total = count($this->results);

        $status = $failed === 0 ? 'pass' : 'fail';
        $message = $failed === 0 
            ? "✅ All tests passed! Challenge completed successfully."
            : "❌ {$failed} of {$total} tests failed. Keep debugging!";

        return [
            'status' => $status,
            'message' => $message,
            'hint' => $this->getHint($status),
            'passed' => $passed,
            'failed' => $failed,
            'total' => $total,
            'results' => $this->results
        ];
    }

    /**
     * Run a single test
     */
    private function runTest(string $name, array $config): void
    {
        $type = $config['type'] ?? 'unknown';
        $passed = false;
        $message = '';
        $hint = '';

        try {
            switch ($type) {
                case 'route_exists':
                    $result = $this->testRouteExists($config);
                    break;
                    
                case 'route_order':
                    $result = $this->testRouteOrder($config);
                    break;
                    
                case 'file_contains':
                    $result = $this->testFileContains($config);
                    break;
                    
                case 'file_not_contains':
                    $result = $this->testFileNotContains($config);
                    break;
                    
                case 'session_key':
                    $result = $this->testSessionKey($config);
                    break;
                    
                case 'function_call':
                    $result = $this->testFunctionCall($config);
                    break;
                    
                default:
                    $result = [
                        'passed' => false,
                        'message' => "Unknown test type: {$type}",
                        'hint' => ''
                    ];
            }

            $passed = $result['passed'];
            $message = $result['message'];
            $hint = $result['hint'] ?? '';

        } catch (\Exception $e) {
            $passed = false;
            $message = "Test error: " . $e->getMessage();
            $hint = 'Check if all required files exist';
        }

        $this->results[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message,
            'hint' => $hint
        ];
    }

    /**
     * Test if a route exists in routes file
     */
    private function testRouteExists(array $config): array
    {
        $routesFile = $this->resolveRoutesFile();
        $route = $config['route'] ?? '';
        $method = strtolower($config['method'] ?? 'get');

        if (!file_exists($routesFile)) {
            return [
                'passed' => false,
                'message' => "routes/routes.php not found",
                'hint' => 'Make sure routes file exists'
            ];
        }

        $content = file_get_contents($routesFile);
        
        // Check if route is registered
        $pattern = "/^\s*\\\$router->{$method}\s*\(\s*['\"]" . preg_quote($route, '/') . "['\"]/m";
        
        if (preg_match($pattern, $content)) {
            return [
                'passed' => true,
                'message' => "✓ Route {$method} {$route} exists",
                'hint' => ''
            ];
        }

        return [
            'passed' => false,
            'message' => "✗ Route {$method} {$route} not found",
            'hint' => $config['hint'] ?? "Add the route to routes/routes.php"
        ];
    }

    /**
     * Test route order (specific before generic)
     */
    private function testRouteOrder(array $config): array
    {
        $routesFile = $this->resolveRoutesFile();
        $specificRoute = $config['specific'] ?? '';
        $genericRoute = $config['generic'] ?? '';

        if (!file_exists($routesFile)) {
            return [
                'passed' => false,
                'message' => "routes/routes.php not found",
                'hint' => ''
            ];
        }

        $content = file_get_contents($routesFile);
        
        $specificPos = strpos($content, $specificRoute);
        $genericPos = strpos($content, $genericRoute);

        if ($specificPos === false) {
            return [
                'passed' => false,
                'message' => "✗ Specific route {$specificRoute} not found",
                'hint' => $config['hint'] ?? ''
            ];
        }

        if ($genericPos === false) {
            return [
                'passed' => false,
                'message' => "✗ Generic route {$genericRoute} not found",
                'hint' => $config['hint'] ?? ''
            ];
        }

        if ($specificPos < $genericPos) {
            return [
                'passed' => true,
                'message' => "✓ Route order correct: specific before generic",
                'hint' => ''
            ];
        }

        return [
            'passed' => false,
            'message' => "✗ Route order wrong: {$specificRoute} should come before {$genericRoute}",
            'hint' => $config['hint'] ?? 'Move specific routes before generic routes with parameters'
        ];
    }

    /**
     * Test if file contains specific text
     */
    private function testFileContains(array $config): array
    {
        $file = $this->resolveFilePath($config['file'] ?? '');
        $search = $config['search'] ?? '';

        if (!file_exists($file)) {
            return [
                'passed' => false,
                'message' => "✗ File not found: {$config['file']}",
                'hint' => $config['hint'] ?? ''
            ];
        }

        $content = file_get_contents($file);

        $contentWithoutComments = preg_replace('!//.*!', '', $content);
        if (strpos($contentWithoutComments, $search) !== false) {
            return [
                'passed' => true,
                'message' => "✓ File contains expected code",
                'hint' => ''
            ];
        }

        return [
            'passed' => false,
            'message' => "✗ File missing expected code: {$search}",
            'hint' => $config['hint'] ?? ''
        ];
    }

    /**
     * Test if file does NOT contain specific text (e.g., commented code)
     */
    private function testFileNotContains(array $config): array
    {
        $file = $this->resolveFilePath($config['file'] ?? '');
        $search = $config['search'] ?? '';

        if (!file_exists($file)) {
            return [
                'passed' => false,
                'message' => "✗ File not found: {$config['file']}",
                'hint' => $config['hint'] ?? ''
            ];
        }

        $content = file_get_contents($file);

        if (strpos($content, $search) === false) {
            return [
                'passed' => true,
                'message' => "✓ File does not contain problematic code",
                'hint' => ''
            ];
        }

        return [
            'passed' => false,
            'message' => "✗ File still contains: {$search}",
            'hint' => $config['hint'] ?? ''
        ];
    }

    /**
     * Test session key usage
     */
    private function testSessionKey(array $config): array
    {
        $file = $this->resolveFilePath($config['file'] ?? '');
        $key = $config['key'] ?? '';

        if (!file_exists($file)) {
            return [
                'passed' => false,
                'message' => "✗ File not found: {$config['file']}",
                'hint' => $config['hint'] ?? ''
            ];
        }

        $content = file_get_contents($file);

        // Check for session key usage
        $pattern = "/\\\$_SESSION\s*\[\s*['\"]" . preg_quote($key, '/') . "['\"]\s*\]/";

        if (preg_match($pattern, $content)) {
            return [
                'passed' => true,
                'message' => "✓ Correct session key used: {$key}",
                'hint' => ''
            ];
        }

        return [
            'passed' => false,
            'message' => "✗ Session key '{$key}' not found in file",
            'hint' => $config['hint'] ?? "Use \$_SESSION['{$key}'] instead"
        ];
    }

    /**
     * Test if specific function is called
     */
    private function testFunctionCall(array $config): array
    {
        $file = $this->resolveFilePath($config['file'] ?? '');
        $function = $config['function'] ?? '';

        if (!file_exists($file)) {
            return [
                'passed' => false,
                'message' => "✗ File not found: {$config['file']}",
                'hint' => $config['hint'] ?? ''
            ];
        }

        $content = file_get_contents($file);

        if (strpos($content, $function) !== false) {
            return [
                'passed' => true,
                'message' => "✓ Function {$function}() is used",
                'hint' => ''
            ];
        }

        return [
            'passed' => false,
            'message' => "✗ Function {$function}() not found",
            'hint' => $config['hint'] ?? "Use {$function}() in this file"
        ];
    }

    /**
     * Get hint based on status
     */
    private function getHint(string $status): string
    {
        if ($status === 'pass') {
            return '';
        }

        // Return first failed test's hint
        foreach ($this->results as $result) {
            if (!$result['passed'] && !empty($result['hint'])) {
                return $result['hint'];
            }
        }

        return 'Check the challenge README for debugging hints';
    }

    /**
     * Log verification result
     */
    public static function logResult(string $challenge, array $result): void
    {
        $logDir = base_path('storage/logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/challenges.log';
        $timestamp = date('Y-m-d H:i:s');
        $status = $result['status'];
        $score = "{$result['passed']}/{$result['total']}";

        $entry = "[{$timestamp}] {$challenge} - {$status} ({$score})\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
