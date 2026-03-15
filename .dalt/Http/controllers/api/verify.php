<?php

header('Content-Type: application/json');

$challengeId = $_GET['challenge'] ?? '';

// Validate challenge exists
$challengePath = base_path("course/challenges/{$challengeId}");
if (!is_dir($challengePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Challenge not found']);
    exit;
}

// Run verification
require_once base_path('framework/Core/ChallengeVerifier.php');

$verifier = new \Core\ChallengeVerifier("course/challenges/{$challengeId}");
$result = $verifier->verify();

// Return result
echo json_encode([
    'success' => $result['status'] === 'pass',
    'status' => $result['status'],
    'message' => $result['message'],
    'tests' => $result['results'] ?? [],
    'timestamp' => date('Y-m-d H:i:s')
]);
