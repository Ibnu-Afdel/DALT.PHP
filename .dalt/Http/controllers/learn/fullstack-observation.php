<?php

declare(strict_types=1);

use Core\App;
use Core\Request;
use Core\Response;

$request = App::resolve(Request::class);
$path = $request->path();

if ($path === '/learn/fullstack/observe/forms/traditional') {
    return Response::redirect('/learn/fullstack/observe/forms?submitted=traditional', 303);
}

if ($path === '/learn/fullstack/observe/forms/json') {
    return Response::json([
        'accepted' => true,
        'message' => 'The server received the preview request.',
    ]);
}

if ($path === '/learn/fullstack/observe/async/issue-preview') {
    return Response::json([
        'issue' => ['id' => 17, 'title' => 'Broken search', 'status' => 'open'],
    ]);
}

if ($path === '/learn/fullstack/observe/async/missing-issue') {
    return Response::json([
        'error' => 'Issue preview not found.',
    ], 404);
}

if ($path === '/learn/fullstack/observe/async/invalid-json') {
    return Response::text('This course fixture intentionally is not JSON.', 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
}

return view('learn/fullstack-observation.view.php', [
    'submittedTraditional' => $request->query('submitted') === 'traditional',
]);
