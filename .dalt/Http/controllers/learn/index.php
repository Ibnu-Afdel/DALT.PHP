<?php

declare(strict_types=1);

$lessons = \Core\CourseLoader::getLessons();
$challenges = \Core\CourseLoader::getChallenges();
$activeChallenge = \Core\ChallengeManager::getActiveChallenge();

$completedLessonIds = [];
foreach ($challenges as $challenge) {
    if ($challenge['passed']) {
        $completedLessonIds[$challenge['lesson']] = true;
    }
}

$nextLesson = null;
foreach ($lessons as $lesson) {
    if (!isset($completedLessonIds[$lesson['id']])) {
        $nextLesson = $lesson;
        break;
    }
}
$nextLesson ??= $lessons[0] ?? null;

$currentChallenge = null;
foreach ($challenges as $challenge) {
    if ($challenge['id'] === $activeChallenge) {
        $currentChallenge = $challenge;
        break;
    }
}

return view('learn/index.view.php', [
    'lessons' => $lessons,
    'challenges' => $challenges,
    'activeChallenge' => $activeChallenge,
    'completedLessonIds' => $completedLessonIds,
    'nextLesson' => $nextLesson,
    'currentChallenge' => $currentChallenge,
]);
