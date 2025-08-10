<?php

global $router;

$router->get('/', 'welcome.php');

// Auth demo (optional)
$router->get('/register', 'registration/create.php')->only('guest');
$router->post('/register', 'registration/store.php')->only(['guest','csrf']);

$router->get('/login', 'session/create.php')->only('guest');
$router->post('/session', 'session/store.php')->only(['guest','csrf']);
$router->delete('/session', 'session/destroy.php')->only(['auth','csrf']);

