<?php

header('Content-Type: application/json');

$challengeId = $_GET['challenge'] ?? '';

// Validate challenge exists
$challengePath = base_path(".dalt/course/challenges/{$challengeId}");
if (!is_dir($challengePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Challenge not found']);
    exit;
}

// CRITICAL: Web verification only makes sense when this challenge is loaded in the app.
// If the user hasn't run "challenge:start {id}", the base app has clean/correct code
// and tests would falsely PASS (e.g. broken-middleware passes because base Csrf is correct).
require_once base_path('framework/Core/ChallengeManager.php');
$activeChallenge = \Core\ChallengeManager::getActiveChallenge();

if ($activeChallenge !== $challengeId) {
    echo json_encode([
        'success' => false,
        'status' => 'not_loaded',
        'message' => "This challenge isn't loaded. Run: php artisan challenge:start {$challengeId}",
        'tests' => [],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Run verification against the base app (which has the challenge files)
require_once base_path('framework/Core/ChallengeVerifier.php');
$verifier = new \Core\ChallengeVerifier(".dalt/course/challenges/{$challengeId}", true);
$result = $verifier->verify();

echo json_encode([
    'success' => $result['status'] === 'pass',
    'status' => $result['status'],
    'message' => $result['message'],
    'tests' => $result['results'] ?? [],
    'timestamp' => date('Y-m-d H:i:s')
]);
