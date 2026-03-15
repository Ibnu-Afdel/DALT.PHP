<?php

$lessonId = $_GET['lesson'] ?? '';

// Load lesson from meta.json
$lesson = \Core\CourseLoader::getLesson($lessonId);
if (!$lesson) {
    abort(404);
}

$readmePath = base_path(".dalt/course/lessons/{$lessonId}/README.md");
if (!file_exists($readmePath)) {
    abort(404);
}
$content = file_get_contents($readmePath);

// Find related challenge(s) - first one that links to this lesson
$relatedChallenges = \Core\CourseLoader::getChallengesForLesson($lessonId);
$relatedChallengeId = !empty($relatedChallenges) ? $relatedChallenges[0]['id'] : null;

view('learn/lesson.view.php', [
    'lessonId' => $lessonId,
    'lesson' => $lesson,
    'content' => $content,
    'relatedChallengeId' => $relatedChallengeId
]);
