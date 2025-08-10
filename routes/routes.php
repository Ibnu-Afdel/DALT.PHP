<?php

global $router;

$router->get('/', 'welcome.php');

// Auth demo (optional)
$router->get('/register', 'registration/create.php')->only('guest');
$router->post('/register', 'registration/store.php')->only('guest');

$router->get('/login', 'session/create.php')->only('guest');
$router->post('/session', 'session/store.php')->only('guest');
$router->delete('/session', 'session/destroy.php')->only('auth');

// Demo route to test {id} matching
$router->get('/demo/{id}', 'demo.php');
