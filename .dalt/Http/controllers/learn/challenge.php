<?php

declare(strict_types=1);

use Core\App;
use Core\ChallengeManager;
use Core\CourseLoader;
use Core\MarkdownRenderer;
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
if ($content === false) {
    abort(500, 'The challenge content could not be read.');
}
$renderedContent = (new MarkdownRenderer())->render($content);

$activeChallenge = ChallengeManager::getActiveChallenge();

// Linear "previous · next" pager, strictly by `order` — see DESIGN_SYSTEM.md →
// "Lesson / challenge pager". CourseLoader::getChallenges() is already sorted by
// order; find this challenge's position and take its immediate neighbors.
$allChallenges = CourseLoader::getChallenges();
$challengeIndex = array_search($challengeId, array_column($allChallenges, 'id'), true);
$previousChallenge = $challengeIndex > 0 ? $allChallenges[$challengeIndex - 1] : null;
$nextChallenge = $challengeIndex < count($allChallenges) - 1 ? $allChallenges[$challengeIndex + 1] : null;
$relatedLesson = CourseLoader::getLesson($challenge['lesson']);
$nextLesson = null;
if ($relatedLesson !== null) {
    $completed = \Core\ProgressManager::completedLessonIds($allChallenges);
    $nextLesson = \Core\ProgressManager::nextInSection($relatedLesson, CourseLoader::getLessons(), $completed);
}

return view('learn/challenge.view.php', [
    'challengeId' => $challengeId,
    'challenge' => $challenge,
    'renderedContent' => $renderedContent,
    'isActive' => $activeChallenge === $challengeId,
    'activeChallenge' => $activeChallenge,
    'previousChallenge' => $previousChallenge,
    'nextChallenge' => $nextChallenge,
    'relatedLesson' => $relatedLesson,
    'nextLesson' => $nextLesson,
]);
