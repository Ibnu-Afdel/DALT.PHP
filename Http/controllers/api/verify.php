<?php

header('Content-Type: application/json');

$challengeId = $_GET['challenge'] ?? '';

// Validate challenge exists
$challengePath = base_path("challenges/{$challengeId}");
if (!is_dir($challengePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Challenge not found']);
    exit;
}

// Run verification
require_once base_path('framework/Core/ChallengeVerifier.php');

$verifier = new \Core\ChallengeVerifier();
$result = $verifier->verify($challengeId);

// Return result
echo json_encode([
    'success' => $result['status'] === 'pass',
    'status' => $result['status'],
    'message' => $result['message'],
    'tests' => $result['tests'] ?? [],
    'timestamp' => date('Y-m-d H:i:s')
]);
