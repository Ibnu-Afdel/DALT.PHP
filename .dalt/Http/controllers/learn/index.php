<?php

declare(strict_types=1);

$lessons = \Core\CourseLoader::getLessons();
$sections = \Core\CourseLoader::getSections();
$challenges = \Core\CourseLoader::getChallenges();
$activeChallenge = \Core\ChallengeManager::getActiveChallenge();

$completedLessonIds = \Core\ProgressManager::completedLessonIds($challenges);
$verifiedLessonIds = \Core\ProgressManager::verifiedLessonIds($challenges);
$nextLesson = \Core\ProgressManager::continuation($lessons, $completedLessonIds);

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
    'verifiedLessonIds' => $verifiedLessonIds,
    'nextLesson' => $nextLesson,
    'currentChallenge' => $currentChallenge,
    'sections' => $sections,
]);
