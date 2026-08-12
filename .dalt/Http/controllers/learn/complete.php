<?php

declare(strict_types=1);

use Core\App;
use Core\CourseLoader;
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
    return Response::redirect($next !== null ? '/learn/lessons/' . $next['id'] : '/learn/tracks/' . $lesson['section'], 303);
}

return Response::redirect('/learn/lessons/' . $lessonId, 303);
