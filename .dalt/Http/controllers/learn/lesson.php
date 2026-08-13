<?php

declare(strict_types=1);

use Core\App;
use Core\CourseLoader;
use Core\MarkdownRenderer;
use Core\Request;
use Core\ProgressManager;

$lessonId = App::resolve(Request::class)->route('lesson');

if (!is_string($lessonId) || ($lesson = CourseLoader::getLesson($lessonId)) === null) {
    abort(404);
}
ProgressManager::visitLesson($lessonId);

$readmePath = base_path(".dalt/course/lessons/{$lessonId}/README.md");
if (!file_exists($readmePath)) {
    abort(404);
}
$content = file_get_contents($readmePath);
if ($content === false) {
    abort(500, 'The lesson content could not be read.');
}
$renderedContent = (new MarkdownRenderer())->render($content);

// Find related challenge(s) - first one that links to this lesson
$relatedChallenges = array_values(CourseLoader::getChallengesForLesson($lessonId));
$relatedChallengeId = !empty($relatedChallenges) ? $relatedChallenges[0]['id'] : null;
$lessonsById = array_column(CourseLoader::getLessons(), null, 'id');
$sections = CourseLoader::getSections();
$sectionLessonCount = count(array_filter($lessonsById, static fn (array $candidate): bool => $candidate['section'] === $lesson['section']));
$prerequisites = array_values(array_intersect_key(
    $lessonsById,
    array_flip($lesson['prerequisites']),
));

// Linear "previous · next" pager, strictly by `order` — see DESIGN_SYSTEM.md →
// "Lesson / challenge pager". CourseLoader::getLesson() already resolves the
// neighboring IDs; this just attaches their titles for the pager labels.
$previousLesson = $lesson['prev'] !== null ? $lessonsById[$lesson['prev']] : null;
$nextLesson = $lesson['next'] !== null ? $lessonsById[$lesson['next']] : null;
$allChallenges = CourseLoader::getChallenges();
$completedLessonIds = ProgressManager::completedLessonIds($allChallenges);
$verifiedLessonIds = ProgressManager::verifiedLessonIds($allChallenges);

return view('learn/lesson.view.php', [
    'lessonId' => $lessonId,
    'lesson' => $lesson,
    'renderedContent' => $renderedContent,
    'relatedChallengeId' => $relatedChallengeId,
    'relatedChallenges' => $relatedChallenges,
    'isCompleted' => isset($completedLessonIds[$lessonId]),
    'isVerified' => isset($verifiedLessonIds[$lessonId]),
    'prerequisites' => $prerequisites,
    'previousLesson' => $previousLesson,
    'nextLesson' => $nextLesson,
    'sections' => $sections,
    'sectionLessonCount' => $sectionLessonCount,
    'goDeeperLinks' => \Core\ResourceCatalog::forLesson($lessonId),
]);
