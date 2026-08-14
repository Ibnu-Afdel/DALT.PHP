<?php

declare(strict_types=1);

use Core\CourseLoader;
use Core\FullstackTrack;
use Core\ProgressManager;

$track = FullstackTrack::load();
$lessons = array_values(array_filter(
    CourseLoader::getLessons(),
    static fn (array $lesson): bool => $lesson['section'] === 'fullstack',
));
$lessonsById = array_column($lessons, null, 'id');
$challenges = CourseLoader::getChallenges();
$completedLessonIds = ProgressManager::completedLessonIds($challenges);
$completedMilestoneIds = ProgressManager::completedMilestoneIds();
$parts = $track['parts'];
foreach ($parts as &$part) {
    $part['is_complete'] = $part['lessons'] !== []
        && count(array_diff($part['lessons'], array_keys($completedLessonIds))) === 0
        && count(array_filter($part['milestones'], static fn (array $milestone): bool => !isset($completedMilestoneIds[$milestone['id']]))) === 0;
    foreach ($part['milestones'] as &$milestone) {
        $milestone['available'] = isset($milestone['route'])
            && count(array_diff($milestone['prerequisites'] ?? [], array_keys($completedLessonIds))) === 0;
        $milestone['completed'] = isset($completedMilestoneIds[$milestone['id']]);
    }
    unset($milestone);
}
unset($part);
$previousPartComplete = true;
foreach ($parts as &$part) {
    // A later published part becomes available only after the preceding Part's
    // lesson and Build work is complete. This keeps the journey honest while
    // leaving future, unimplemented lessons visibly planned.
    $part['lesson_available'] = $previousPartComplete;
    $previousPartComplete = $part['is_complete'];
}
unset($part);
$track['parts'] = $parts;

$availableLessons = [];
foreach ($parts as &$part) {
    $part['available_lesson_ids'] = $part['lesson_available']
        ? array_values(array_filter(
            $part['lessons'],
            static fn (string $lessonId): bool => count(array_diff(
                $lessonsById[$lessonId]['prerequisites'],
                array_keys($completedLessonIds),
            )) === 0,
        ))
        : [];
    if ($part['lesson_available']) {
        foreach ($part['available_lesson_ids'] as $lessonId) {
            $availableLessons[] = $lessonsById[$lessonId];
        }
    }
}
unset($part);
$track['parts'] = $parts;

return view('learn/fullstack.view.php', [
    'track' => $track,
    'lessonsById' => $lessonsById,
    'completedLessonIds' => $completedLessonIds,
    'completedMilestoneIds' => $completedMilestoneIds,
    'availableLessonIds' => array_column($availableLessons, 'id'),
    'continuation' => ProgressManager::continuation($availableLessons, $completedLessonIds),
]);
