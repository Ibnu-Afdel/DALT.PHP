<?php

declare(strict_types=1);

use Core\App;
use Core\Request;

$lessons = \Core\CourseLoader::getLessons();
$sections = \Core\CourseLoader::getSections();
$challenges = \Core\CourseLoader::getChallenges();
$activeChallenge = \Core\ChallengeManager::getActiveChallenge();
$section = App::resolve(Request::class)->query('section');
$section = is_string($section) ? $section : null;

if ($section !== null && isset($sections[$section])) {
    $lessons = array_values(array_filter(
        $lessons,
        static fn (array $lesson): bool => $lesson['section'] === $section,
    ));
    $lessonIds = array_column($lessons, 'id');
    $challenges = array_values(array_filter(
        $challenges,
        static fn (array $challenge): bool => in_array($challenge['lesson'], $lessonIds, true),
    ));
} else {
    $section = null;
}

return view('learn/resources.view.php', [
    'lessons' => $lessons,
    'challenges' => $challenges,
    'activeChallenge' => $activeChallenge,
    'section' => $section,
    'sections' => $sections,
]);
