<?php

header('Content-Type: application/json; charset=utf-8');

/**
 * Always respond with valid JSON for this API endpoint.
 */
function verify_json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Prevent PHP warnings/notices from leaking HTML into JSON responses.
ini_set('display_errors', '0');
ob_start();
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

$challengeId = $_GET['challenge'] ?? '';

try {
    // Validate challenge exists
    $challengePath = base_path(".dalt/course/challenges/{$challengeId}");
    if (!is_dir($challengePath)) {
        verify_json_response(['error' => 'Challenge not found'], 404);
    }

    // CRITICAL: Web verification only makes sense when this challenge is loaded in the app.
    // If the user hasn't run "challenge:start {id}", the base app has clean/correct code
    // and tests would falsely PASS (e.g. broken-middleware passes because base Csrf is correct).
    $activeChallenge = \Core\ChallengeManager::getActiveChallenge();

    if ($activeChallenge !== $challengeId) {
        verify_json_response([
            'success' => false,
            'status' => 'not_loaded',
            'message' => "This challenge isn't loaded. Run: php artisan challenge:start {$challengeId}",
            'tests' => [],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    // Run verification against the base app (which has the challenge files)
    $verifier = new \Core\ChallengeVerifier(".dalt/course/challenges/{$challengeId}", true);
    $result = $verifier->verify();
    \Core\ChallengeVerifier::logResult($challengeId, $result);

    if (ob_get_length()) {
        ob_clean();
    }

    restore_error_handler();
    verify_json_response([
        'success' => $result['status'] === 'pass',
        'status' => $result['status'],
        'message' => $result['message'],
        'tests' => $result['results'] ?? [],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (\Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    restore_error_handler();

    verify_json_response([
        'success' => false,
        'status' => 'error',
        'message' => 'Verification failed: ' . $e->getMessage(),
        'tests' => [],
        'timestamp' => date('Y-m-d H:i:s')
    ], 500);
}
