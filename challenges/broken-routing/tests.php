<?php

/**
 * Broken Routing Challenge - Test Specification
 * 
 * This file defines the expected behavior after fixing the routing bugs.
 */

return [
    'route_create_exists' => [
        'type' => 'route_exists',
        'route' => '/posts/create',
        'method' => 'get',
        'hint' => 'Make sure /posts/create route is registered in routes/routes.php'
    ],

    'route_edit_exists' => [
        'type' => 'route_exists',
        'route' => '/posts/{id}/edit',
        'method' => 'get',
        'hint' => 'Uncomment the /posts/{id}/edit route in routes/routes.php'
    ],

    'route_order_correct' => [
        'type' => 'route_order',
        'specific' => '/posts/create',
        'generic' => '/posts/{id}',
        'hint' => 'Move /posts/create BEFORE /posts/{id} in routes/routes.php'
    ],

    'edit_route_not_commented' => [
        'type' => 'file_not_contains',
        'file' => 'routes/routes.php',
        'search' => "// \$router->get('/posts/{id}/edit'",
        'hint' => 'Remove the // comment from the edit route'
    ]
];
