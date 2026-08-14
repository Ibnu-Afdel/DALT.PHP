<?php

declare(strict_types=1);

use Core\App;
use Core\CourseLoader;
use Core\FullstackTrack;
use Core\ProgressManager;
use Core\Request;
use Core\Response;

$track = FullstackTrack::load();
$milestone = $track['parts']['01']['milestones'][0] ?? null;
if (!is_array($milestone) || $milestone['id'] !== 'B01') {
    abort(404);
}

$completedLessons = ProgressManager::completedLessonIds(CourseLoader::getChallenges());
$available = count(array_diff($milestone['prerequisites'] ?? [], array_keys($completedLessons))) === 0;
if (!$available) {
    return Response::redirect('/learn/fullstack', 303);
}

if (App::resolve(Request::class)->method() === 'POST') {
    ProgressManager::markMilestoneCompleted('B01');
    return Response::redirect('/learn/fullstack', 303);
}

return view('learn/fullstack-javascript-build.view.php', [
    'isCompleted' => isset(ProgressManager::completedMilestoneIds()['B01']),
]);
