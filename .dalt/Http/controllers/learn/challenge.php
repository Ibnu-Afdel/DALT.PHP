<?php

declare(strict_types=1);

use Core\App;
use Core\ChallengeManager;
use Core\CourseLoader;
use Core\Request;

$challengeId = App::resolve(Request::class)->route('challenge');

if (!is_string($challengeId) || ($challenge = CourseLoader::getChallenge($challengeId)) === null) {
    abort(404);
}

$readmePath = base_path(".dalt/course/challenges/{$challengeId}/README.md");
if (!file_exists($readmePath)) {
    abort(404);
}
$content = file_get_contents($readmePath);

$activeChallenge = ChallengeManager::getActiveChallenge();

return view('learn/challenge.view.php', [
    'challengeId' => $challengeId,
    'challenge' => $challenge,
    'content' => $content,
    'isActive' => $activeChallenge === $challengeId,
    'activeChallenge' => $activeChallenge,
]);
