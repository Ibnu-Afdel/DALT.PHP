<?php

declare(strict_types=1);

use Core\App;
use Core\CourseLoader;
use Core\FullstackTrack;
use Core\ProgressManager;
use Core\Request;
use Core\Response;

$track = FullstackTrack::load();
$milestone = $track['parts']['02']['milestones'][0] ?? null;
if (!is_array($milestone) || $milestone['id'] !== 'B02') {
    abort(404);
}

$completedLessons = ProgressManager::completedLessonIds(CourseLoader::getChallenges());
if (count(array_diff($milestone['prerequisites'] ?? [], array_keys($completedLessons))) !== 0) {
    return Response::redirect('/learn/fullstack', 303);
}

if (App::resolve(Request::class)->method() === 'POST') {
    ProgressManager::markMilestoneCompleted('B02');
    return Response::redirect('/learn/fullstack', 303);
}

return view('learn/fullstack-typescript-build.view.php', [
    'isCompleted' => isset(ProgressManager::completedMilestoneIds()['B02']),
]);
