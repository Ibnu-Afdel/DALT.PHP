<?php

declare(strict_types=1);

use Core\App;
use Core\ChallengeManager;
use Core\ChallengeVerifier;
use Core\CourseLoader;
use Core\Request;
use Core\Response;

try {
    $request = App::resolve(Request::class);
    $challengeId = $request->route('challenge');

    if (!is_string($challengeId) || CourseLoader::getChallenge($challengeId) === null) {
        return Response::json([
            'success' => false,
            'status' => 'not_found',
            'message' => 'Challenge not found.',
            'tests' => [],
        ], 404);
    }

    // Verifying a clean application would create false success, so the active
    // transaction is part of the HTTP precondition as well as the CLI contract.
    if (ChallengeManager::getActiveChallenge() !== $challengeId) {
        return Response::json([
            'success' => false,
            'status' => 'not_loaded',
            'message' => "Load this challenge first: php artisan challenge:start {$challengeId}",
            'tests' => [],
        ], 409);
    }

    $result = (new ChallengeVerifier(".dalt/course/challenges/{$challengeId}", true))->verify();
    ChallengeVerifier::logResult($challengeId, $result);

    if ($result['status'] === 'pass') {
        ChallengeManager::markPassed($challengeId);
    }

    return Response::json([
        'success' => $result['status'] === 'pass',
        'status' => $result['status'],
        'message' => $result['message'],
        'tests' => $result['results'],
        'timestamp' => gmdate('c'),
    ]);
} catch (Throwable $exception) {
    app_log('Challenge verification request failed: ' . $exception->getMessage());

    return Response::json([
        'success' => false,
        'status' => 'error',
        'message' => 'Verification could not be completed. Check the application log and try again.',
        'tests' => [],
    ], 500);
}
