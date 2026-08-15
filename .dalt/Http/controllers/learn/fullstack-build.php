<?php

declare(strict_types=1);

use Core\App;
use Core\BuildMilestone;
use Core\CourseLoader;
use Core\FullstackTrack;
use Core\MarkdownRenderer;
use Core\ProgressManager;
use Core\Request;
use Core\Response;

// One controller for every Build milestone. The milestone ID comes from the route;
// its title, part and prerequisites come from the track manifest; its content comes
// from .dalt/course/build/<ID>-<slug>/README.md. Adding B04 is a manifest entry and
// a Markdown file — no new controller, no new view, no new route.

$request = App::resolve(Request::class);
$requestedId = $request->route('milestone');
if (!is_string($requestedId) || preg_match('/\A[bcBC][0-9]{2}\z/D', $requestedId) !== 1) {
    abort(404);
}
$milestoneId = strtoupper($requestedId);

$track = FullstackTrack::load();
$milestone = null;
$partNumber = null;
foreach ($track['parts'] as $number => $part) {
    foreach ($part['milestones'] as $candidate) {
        if ($candidate['id'] === $milestoneId) {
            $milestone = $candidate;
            $partNumber = (string) $number;
            break 2;
        }
    }
}
if ($milestone === null || BuildMilestone::find($milestoneId) === null) {
    abort(404);
}

// Same gate the lessons use: you reach a milestone by finishing the work that
// leads to it, not by typing its URL.
$completedLessons = ProgressManager::completedLessonIds(CourseLoader::getChallenges());
if (array_diff($milestone['prerequisites'] ?? [], array_keys($completedLessons)) !== []) {
    return Response::redirect('/learn/fullstack', 303);
}

if ($request->method() === 'POST') {
    ProgressManager::markMilestoneCompleted($milestoneId);

    return Response::redirect('/learn/fullstack', 303);
}

return view('learn/fullstack-build.view.php', [
    'milestoneId' => $milestoneId,
    'title' => $milestone['title'],
    'partNumber' => str_pad((string) $partNumber, 2, '0', STR_PAD_LEFT),
    'partTitle' => $track['parts'][$partNumber]['title'],
    'renderedContent' => (new MarkdownRenderer())->render(BuildMilestone::specification($milestoneId)),
    'completeAction' => BuildMilestone::routeFor($milestoneId) . '/complete',
    'isCompleted' => isset(ProgressManager::completedMilestoneIds()[$milestoneId]),
]);
