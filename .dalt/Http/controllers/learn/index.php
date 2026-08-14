<?php

declare(strict_types=1);

$lessons = \Core\CourseLoader::getLessons();
$sections = \Core\CourseLoader::getSections();
$challenges = \Core\CourseLoader::getChallenges();
$activeChallenge = \Core\ChallengeManager::getActiveChallenge();

$completedLessonIds = \Core\ProgressManager::completedLessonIds($challenges);
$verifiedLessonIds = \Core\ProgressManager::verifiedLessonIds($challenges);
$tracks = [];
foreach (['core', 'fullstack'] as $trackId) {
    $trackLessons = array_values(array_filter($lessons, fn (array $lesson): bool => $sections[$lesson['section']]['track'] === $trackId));
    $tracks[$trackId] = [
        'lessons' => $trackLessons,
        'continuation' => \Core\ProgressManager::continuation($trackLessons, $completedLessonIds),
        'completed_count' => count(array_filter($trackLessons, static fn (array $lesson): bool => isset($completedLessonIds[$lesson['id']]))),
    ];
}

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
    'tracks' => $tracks,
    'currentChallenge' => $currentChallenge,
    'sections' => $sections,
]);
