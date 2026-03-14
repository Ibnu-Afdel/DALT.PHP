<?php

$challengeId = $_GET['challenge'] ?? '';

// Validate challenge exists
$challengePath = base_path("challenges/{$challengeId}/README.md");
if (!file_exists($challengePath)) {
    http_response_code(404);
    view('status/404.view.php');
    exit;
}

// Read challenge content
$content = file_get_contents($challengePath);

// Challenge metadata
$challenges = [
    'broken-routing' => [
        'title' => 'Broken Routing',
        'icon' => '🗺️',
        'difficulty' => 'Easy',
        'bugs' => 2,
        'lesson' => 'lesson-02-routing'
    ],
    'broken-middleware' => [
        'title' => 'Broken Middleware',
        'icon' => '🛡️',
        'difficulty' => 'Medium',
        'bugs' => 2,
        'lesson' => 'lesson-03-middleware'
    ],
    'broken-auth' => [
        'title' => 'Broken Authentication',
        'icon' => '🔐',
        'difficulty' => 'Easy',
        'bugs' => 1,
        'lesson' => 'lesson-04-authentication'
    ],
    'broken-database' => [
        'title' => 'Broken Database',
        'icon' => '💾',
        'difficulty' => 'Medium',
        'bugs' => 2,
        'lesson' => 'lesson-05-database'
    ],
    'broken-session' => [
        'title' => 'Broken Session',
        'icon' => '⏱️',
        'difficulty' => 'Medium',
        'bugs' => 2,
        'lesson' => 'lesson-01-request-lifecycle'
    ]
];

$challenge = $challenges[$challengeId] ?? null;
if (!$challenge) {
    http_response_code(404);
    view('status/404.view.php');
    exit;
}

view('learn/challenge.view.php', [
    'challengeId' => $challengeId,
    'challenge' => $challenge,
    'content' => $content
]);
