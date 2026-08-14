<?php

declare(strict_types=1);

use Core\App;
use Core\CourseLoader;
use Core\FullstackTrack;
use Core\MarkdownRenderer;
use Core\Request;
use Core\ProgressManager;

$lessonId = App::resolve(Request::class)->route('lesson');

if (!is_string($lessonId) || ($lesson = CourseLoader::getLesson($lessonId)) === null) {
    abort(404);
}
$readmePath = base_path(".dalt/course/lessons/{$lessonId}/README.md");
if (!file_exists($readmePath)) {
    abort(404);
}
$content = file_get_contents($readmePath);
if ($content === false) {
    abort(500, 'The lesson content could not be read.');
}
$sections = CourseLoader::getSections();
$isFullstack = $sections[$lesson['section']]['track'] === 'fullstack';
if ($isFullstack) {
    $completedForAvailability = ProgressManager::completedLessonIds(CourseLoader::getChallenges());
    $completedMilestonesForAvailability = ProgressManager::completedMilestoneIds();
    $previousPartComplete = true;
    foreach (FullstackTrack::load()['parts'] as $part) {
        if (in_array($lessonId, $part['lessons'], true)) {
            if (!$previousPartComplete) {
                return \Core\Response::redirect('/learn/fullstack', 303);
            }
            break;
        }
        $previousPartComplete = $part['lessons'] !== []
            && count(array_diff($part['lessons'], array_keys($completedForAvailability))) === 0
            && count(array_filter($part['milestones'], static fn (array $milestone): bool => !isset($completedMilestonesForAvailability[$milestone['id']]))) === 0;
    }
    if (count(array_diff($lesson['prerequisites'], array_keys($completedForAvailability))) !== 0) {
        return \Core\Response::redirect('/learn/fullstack', 303);
    }
}
ProgressManager::visitLesson($lessonId);
$lessonHeaderMeta = [];
if ($isFullstack) {
    foreach (['Estimated effort' => 'effort', 'Difficulty' => 'difficulty'] as $label => $key) {
        if (preg_match('/^' . preg_quote($label, '/') . ':\s*(.+?)\s*$/m', $content, $match) === 1) {
            $lessonHeaderMeta[$key] = $match[1];
        }
    }
    // Fullstack lesson source retains its maintainer metadata, while the
    // learner view starts at the first teaching section.
    $content = preg_replace('/\A# [^\n]+\R\R.*?\R\R(?=## )/s', '', $content, 1) ?? $content;
}
$renderedContent = (new MarkdownRenderer())->render($content);

// Find related challenge(s) - first one that links to this lesson
$relatedChallenges = array_values(CourseLoader::getChallengesForLesson($lessonId));
$relatedChallengeId = !empty($relatedChallenges) ? $relatedChallenges[0]['id'] : null;
$lessonsById = array_column(CourseLoader::getLessons(), null, 'id');
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
    'isFullstack' => $isFullstack,
    'lessonHeaderMeta' => $lessonHeaderMeta,
    'backHref' => $isFullstack ? '/learn/fullstack' : '/learn/resources',
    'backLabel' => $isFullstack ? 'DALT Fullstack' : 'All resources',
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
