<?php

$lessonId = $_GET['lesson'] ?? '';

// Validate lesson exists
$lessonPath = base_path("course/lessons/{$lessonId}/README.md");
if (!file_exists($lessonPath)) {
    abort(404);
}

// Read lesson content
$content = file_get_contents($lessonPath);

$iconLifecycle = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
$iconRouting = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>';
$iconMiddleware = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>';
$iconAuth = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>';
$iconDatabase = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>';

// Parse lesson metadata
$lessons = [
    '01-request-lifecycle' => [
        'title' => 'Request Lifecycle',
        'icon' => $iconLifecycle,
        'next' => '02-routing'
    ],
    '02-routing' => [
        'title' => 'Routing',
        'icon' => $iconRouting,
        'prev' => '01-request-lifecycle',
        'next' => '03-middleware'
    ],
    '03-middleware' => [
        'title' => 'Middleware',
        'icon' => $iconMiddleware,
        'prev' => '02-routing',
        'next' => '04-authentication'
    ],
    '04-authentication' => [
        'title' => 'Authentication',
        'icon' => $iconAuth,
        'prev' => '03-middleware',
        'next' => '05-database'
    ],
    '05-database' => [
        'title' => 'Database',
        'icon' => $iconDatabase,
        'prev' => '04-authentication'
    ]
];

$lesson = $lessons[$lessonId] ?? null;
if (!$lesson) {
    abort(404);
}

view('learn/lesson.view.php', [
    'lessonId' => $lessonId,
    'lesson' => $lesson,
    'content' => $content
]);
