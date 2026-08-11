<?php

declare(strict_types=1);

$lessons = \Core\CourseLoader::getLessons();
$challenges = \Core\CourseLoader::getChallenges();
$activeChallenge = \Core\ChallengeManager::getActiveChallenge();

return view('learn/index.view.php', [
    'lessons' => $lessons,
    'challenges' => $challenges,
    'activeChallenge' => $activeChallenge,
]);
