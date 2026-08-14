<?php

declare(strict_types=1);

use Core\App;
use Core\FullstackTrack;
use Core\ProgressManager;
use Core\Request;
use Core\Response;

$track = FullstackTrack::load();
$milestone = $track['parts']['00']['milestones'][0] ?? null;
if (!is_array($milestone) || $milestone['id'] !== 'B00') {
    abort(404);
}

$completedLessons = ProgressManager::completedLessonIds(\Core\CourseLoader::getChallenges());
$available = count(array_diff($milestone['prerequisites'] ?? [], array_keys($completedLessons))) === 0;
if (!$available) {
    return Response::redirect('/learn/fullstack', 303);
}

$request = App::resolve(Request::class);
if ($request->method() === 'POST') {
    ProgressManager::markMilestoneCompleted('B00');
    return Response::redirect('/learn/fullstack', 303);
}

return view('learn/fullstack-build.view.php', [
    'isCompleted' => isset(ProgressManager::completedMilestoneIds()['B00']),
]);
