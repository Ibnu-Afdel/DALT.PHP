<?php

$lessonId = $_GET['lesson'] ?? '';

// Validate lesson exists
$lessonPath = base_path("course/lessons/{$lessonId}/README.md");
if (!file_exists($lessonPath)) {
    http_response_code(404);
    view('status/404.view.php');
    exit;
}

// Read lesson content
$content = file_get_contents($lessonPath);

// Parse lesson metadata
$lessons = [
    'lesson-01-request-lifecycle' => [
        'title' => 'Request Lifecycle',
        'icon' => '🔄',
        'next' => 'lesson-02-routing'
    ],
    'lesson-02-routing' => [
        'title' => 'Routing',
        'icon' => '🗺️',
        'prev' => 'lesson-01-request-lifecycle',
        'next' => 'lesson-03-middleware'
    ],
    'lesson-03-middleware' => [
        'title' => 'Middleware',
        'icon' => '🛡️',
        'prev' => 'lesson-02-routing',
        'next' => 'lesson-04-authentication'
    ],
    'lesson-04-authentication' => [
        'title' => 'Authentication',
        'icon' => '🔐',
        'prev' => 'lesson-03-middleware',
        'next' => 'lesson-05-database'
    ],
    'lesson-05-database' => [
        'title' => 'Database',
        'icon' => '💾',
        'prev' => 'lesson-04-authentication'
    ]
];

$lesson = $lessons[$lessonId] ?? null;
if (!$lesson) {
    http_response_code(404);
    view('status/404.view.php');
    exit;
}

view('learn/lesson.view.php', [
    'lessonId' => $lessonId,
    'lesson' => $lesson,
    'content' => $content
]);
