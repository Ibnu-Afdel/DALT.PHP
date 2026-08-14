<?php

declare(strict_types=1);

use Core\App;
use Core\CourseLoader;
use Core\FullstackTrack;
use Core\ProgressManager;
use Core\Request;
use Core\Response;

$request = App::resolve(Request::class);
$lessonId = $request->route('lesson');
if (!is_string($lessonId) || ($lesson = CourseLoader::getLesson($lessonId)) === null) {
    abort(404);
}

ProgressManager::markLessonCompleted($lessonId);
$completed = ProgressManager::completedLessonIds(CourseLoader::getChallenges());
$next = ProgressManager::nextInSection($lesson, CourseLoader::getLessons(), $completed);

if ($request->input('continue') === '1') {
    $returnPath = $lesson['section'] === 'fullstack' ? '/learn/fullstack' : '/learn/tracks/' . $lesson['section'];
    if ($lesson['section'] === 'fullstack') {
        // A lesson may continue within its published Part, but crossing a Part
        // boundary returns to the journey so its Build gate remains visible.
        $currentPart = null;
        foreach (FullstackTrack::load()['parts'] as $part) {
            if (in_array($lessonId, $part['lessons'], true)) {
                $currentPart = $part;
                break;
            }
        }
        if ($currentPart === null || $next === null || !in_array($next['id'], $currentPart['lessons'], true)) {
            return Response::redirect($returnPath, 303);
        }
    }
    return Response::redirect($next !== null ? '/learn/lessons/' . $next['id'] : $returnPath, 303);
}

return Response::redirect('/learn/lessons/' . $lessonId, 303);
