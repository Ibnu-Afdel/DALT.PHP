<?php

$lessons = \Core\CourseLoader::getLessons();
$challenges = \Core\CourseLoader::getChallenges();

view('learn/index.view.php', [
    'lessons' => $lessons,
    'challenges' => $challenges
]);
