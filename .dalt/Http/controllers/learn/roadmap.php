<?php

declare(strict_types=1);

use Core\ChallengeManager;
use Core\CourseLoader;

$roadmapPath = base_path('documentation/competency-roadmap.md');

if (!is_file($roadmapPath) || !is_readable($roadmapPath)) {
    abort(404, 'The competency roadmap is not available.');
}

$content = file_get_contents($roadmapPath);

if ($content === false) {
    abort(500, 'The competency roadmap could not be read.');
}

// The page header owns the title; keep the Markdown body as the canonical content.
$content = preg_replace('/\A# DALT\.PHP Competency Roadmap\R+/', '', $content, 1) ?? $content;

// The graph is a visualization over the lessons themselves — the same prerequisite
// data and IDs CourseLoader already validates — rather than a separately authored
// dataset. This keeps the graph and the lesson content from drifting apart.
$lessons = CourseLoader::getLessons();
$passedChallenges = ChallengeManager::getPassedChallenges();

// Groups a lesson under a coarse branch for the graph, mirroring the branches the
// written roadmap doc already describes (foundation / docker / postgres / operations).
// A closure, not a top-level function: this file is require()'d per request, and a
// named function declaration would fatal with "cannot redeclare" the second time.
$inferLessonSection = static function (string $lessonId): string {
    if (str_contains($lessonId, 'docker')) {
        return 'docker';
    }
    if (str_contains($lessonId, 'postgres')) {
        return 'postgres';
    }
    if ($lessonId === '17-observability') {
        return 'operations';
    }

    return 'foundation';
};

$nodes = array_map(static function (array $lesson) use ($passedChallenges, $inferLessonSection): array {
    $challenge = CourseLoader::getChallengesForLesson($lesson['id'])[0] ?? null;

    return [
        'id' => $lesson['id'],
        'order' => $lesson['order'],
        'title' => $lesson['title'],
        'description' => $lesson['description'],
        'section' => $inferLessonSection($lesson['id']),
        'prerequisites' => $lesson['prerequisites'],
        'challenge_id' => $challenge['id'] ?? null,
        'challenge_title' => $challenge['title'] ?? null,
        'verified' => $challenge !== null && in_array($challenge['id'], $passedChallenges, true),
    ];
}, $lessons);

return view('learn/roadmap.view.php', [
    'content' => $content,
    'nodes' => $nodes,
]);
