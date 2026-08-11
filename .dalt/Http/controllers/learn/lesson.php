<?php

declare(strict_types=1);

use Core\App;
use Core\CourseLoader;
use Core\Request;

$lessonId = App::resolve(Request::class)->route('lesson');

if (!is_string($lessonId) || ($lesson = CourseLoader::getLesson($lessonId)) === null) {
    abort(404);
}

$readmePath = base_path(".dalt/course/lessons/{$lessonId}/README.md");
if (!file_exists($readmePath)) {
    abort(404);
}
$content = file_get_contents($readmePath);

// Find related challenge(s) - first one that links to this lesson
$relatedChallenges = array_values(CourseLoader::getChallengesForLesson($lessonId));
$relatedChallengeId = !empty($relatedChallenges) ? $relatedChallenges[0]['id'] : null;
$lessonsById = array_column(CourseLoader::getLessons(), null, 'id');
$prerequisites = array_values(array_intersect_key(
    $lessonsById,
    array_flip($lesson['prerequisites']),
));

return view('learn/lesson.view.php', [
    'lessonId' => $lessonId,
    'lesson' => $lesson,
    'content' => $content,
    'relatedChallengeId' => $relatedChallengeId,
    'prerequisites' => $prerequisites,
]);
