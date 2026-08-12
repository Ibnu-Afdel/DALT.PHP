<?php

declare(strict_types=1);

use Core\App;
use Core\Request;

$lessons = \Core\CourseLoader::getLessons();
$challenges = \Core\CourseLoader::getChallenges();
$activeChallenge = \Core\ChallengeManager::getActiveChallenge();
$section = App::resolve(Request::class)->query('section');
$section = is_string($section) ? $section : null;
$sectionLabels = [
    'foundation' => 'Foundation',
    'docker' => 'Docker',
    'postgres' => 'PostgreSQL',
    'operations' => 'Operations',
];

if ($section !== null && isset($sectionLabels[$section])) {
    $lessons = array_values(array_filter(
        $lessons,
        static fn (array $lesson): bool => \Core\CourseLoader::inferSection($lesson['id']) === $section,
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
    'sectionLabels' => $sectionLabels,
]);
