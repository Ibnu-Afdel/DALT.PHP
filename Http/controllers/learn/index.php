<?php

// Get all lessons
$lessons = [
    [
        'id' => 'lesson-01-request-lifecycle',
        'title' => 'Request Lifecycle',
        'description' => 'Learn how HTTP requests flow through a web framework',
        'icon' => '🔄',
        'color' => 'blue'
    ],
    [
        'id' => 'lesson-02-routing',
        'title' => 'Routing',
        'description' => 'Understand how URLs map to controllers',
        'icon' => '🗺️',
        'color' => 'green'
    ],
    [
        'id' => 'lesson-03-middleware',
        'title' => 'Middleware',
        'description' => 'Master request filtering and authentication',
        'icon' => '🛡️',
        'color' => 'purple'
    ],
    [
        'id' => 'lesson-04-authentication',
        'title' => 'Authentication',
        'description' => 'Implement secure user login and registration',
        'icon' => '🔐',
        'color' => 'red'
    ],
    [
        'id' => 'lesson-05-database',
        'title' => 'Database',
        'description' => 'Work with databases safely and efficiently',
        'icon' => '💾',
        'color' => 'yellow'
    ]
];

// Get all challenges
$challenges = [
    [
        'id' => 'broken-routing',
        'title' => 'Broken Routing',
        'description' => 'Fix route order issues and missing route registrations',
        'difficulty' => 'Easy',
        'bugs' => 2,
        'icon' => '🗺️',
        'color' => 'red'
    ],
    [
        'id' => 'broken-middleware',
        'title' => 'Broken Middleware',
        'description' => 'Debug auth checks and CSRF validation',
        'difficulty' => 'Medium',
        'bugs' => 2,
        'icon' => '🛡️',
        'color' => 'blue'
    ],
    [
        'id' => 'broken-auth',
        'title' => 'Broken Authentication',
        'description' => 'Fix insecure password comparison',
        'difficulty' => 'Easy',
        'bugs' => 1,
        'icon' => '🔐',
        'color' => 'purple'
    ],
    [
        'id' => 'broken-database',
        'title' => 'Broken Database',
        'description' => 'Patch SQL injection vulnerabilities',
        'difficulty' => 'Medium',
        'bugs' => 2,
        'icon' => '💾',
        'color' => 'green'
    ],
    [
        'id' => 'broken-session',
        'title' => 'Broken Session',
        'description' => 'Fix flash data handling and session persistence',
        'difficulty' => 'Medium',
        'bugs' => 2,
        'icon' => '⏱️',
        'color' => 'yellow'
    ]
];

view('learn/index.view.php', [
    'lessons' => $lessons,
    'challenges' => $challenges
]);
