<?php

declare(strict_types=1);

use Core\App;
use Core\CourseLoader;
use Core\Request;
use Core\ProgressManager;

$sectionId = App::resolve(Request::class)->route('section');
$sections = CourseLoader::getSections();
if (!is_string($sectionId) || !isset($sections[$sectionId])) {
    abort(404);
}

$lessons = array_values(array_filter(
    CourseLoader::getLessons(),
    static fn (array $lesson): bool => $lesson['section'] === $sectionId,
));
usort($lessons, static fn (array $left, array $right): int => $left['section_order'] <=> $right['section_order']);

$challenges = CourseLoader::getChallenges();
$completedLessonIds = ProgressManager::completedLessonIds($challenges);
$verifiedLessonIds = ProgressManager::verifiedLessonIds($challenges);
$lastVisitedLesson = ProgressManager::lastVisitedLesson();

$nextLesson = null;
foreach ($lessons as $lesson) {
    if (!isset($completedLessonIds[$lesson['id']])) {
        $nextLesson = $lesson;
        break;
    }
}

$sectionLessonIds = array_flip(array_column($lessons, 'id'));
$recommendedKnowledge = [];
foreach ($lessons as $lesson) {
    foreach ($lesson['prerequisites'] as $prerequisiteId) {
        if (isset($sectionLessonIds[$prerequisiteId])) {
            continue;
        }
        $recommendedKnowledge[$prerequisiteId] = true;
    }
}
$allLessons = array_column(CourseLoader::getLessons(), null, 'id');
$recommendedKnowledge = array_values(array_map(
    static fn (string $id): array => $allLessons[$id],
    array_keys($recommendedKnowledge),
));

return view('learn/track.view.php', [
    'section' => $sections[$sectionId],
    'lessons' => $lessons,
    'completedLessonIds' => $completedLessonIds,
    'verifiedLessonIds' => $verifiedLessonIds,
    'lastVisitedLesson' => $lastVisitedLesson,
    'nextLesson' => $nextLesson,
    'recommendedKnowledge' => $recommendedKnowledge,
]);
