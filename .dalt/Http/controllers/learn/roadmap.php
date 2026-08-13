<?php

declare(strict_types=1);

use Core\CourseLoader;
use Core\ProgressManager;

$sections = CourseLoader::getSections();
$lessons = CourseLoader::getLessons();
$challenges = CourseLoader::getChallenges();
$completedLessonIds = ProgressManager::completedLessonIds($challenges);
$verifiedLessonIds = ProgressManager::verifiedLessonIds($challenges);
$lastVisitedLesson = ProgressManager::lastVisitedLesson();
$lessonsById = array_column($lessons, null, 'id');

$paths = array_map(static fn (array $section): array => [
    'section' => $section,
    'lessons' => [],
], $sections);

foreach ($lessons as $lesson) {
    $paths[$lesson['section']]['lessons'][] = $lesson;
}

foreach ($paths as &$path) {
    usort($path['lessons'], static fn (array $left, array $right): int => $left['section_order'] <=> $right['section_order']);

    foreach ($path['lessons'] as &$lesson) {
        $lesson['recommended_first'] = array_values(array_filter(
            array_map(static fn (string $id): ?array => $lessonsById[$id] ?? null, $lesson['prerequisites']),
            static fn (?array $prerequisite): bool => $prerequisite !== null && $prerequisite['section'] !== $lesson['section'],
        ));
    }
    unset($lesson);

    $path['completed_count'] = count(array_filter(
        $path['lessons'],
        static fn (array $lesson): bool => isset($completedLessonIds[$lesson['id']]),
    ));
}
unset($path);

$continuation = ProgressManager::continuation($lessons, $completedLessonIds);

return view('learn/roadmap.view.php', [
    'paths' => $paths,
    'lessons' => $lessons,
    'completedLessonIds' => $completedLessonIds,
    'verifiedLessonIds' => $verifiedLessonIds,
    'lastVisitedLesson' => $lastVisitedLesson,
    'continuation' => $continuation,
]);
