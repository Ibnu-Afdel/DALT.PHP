<?php

global $router;

// Learning routes
$router->get("/learn", "learn/index.php");
$router->get("/learn/resources", "learn/resources.php");
$router->get("/learn/tracks/{section}", "learn/track.php");
$router->get("/learn/roadmap", "learn/roadmap.php");
$router->get("/learn/lessons/{lesson}", "learn/lesson.php");
$router->post("/learn/lessons/{lesson}/complete", "learn/complete.php")->only('csrf');
$router->get("/learn/challenges/{challenge}", "learn/challenge.php");

// API routes for verification
$router->post("/api/verify/{challenge}", "api/verify.php")->only('csrf');

// DALT build output remains inside .dalt and is reachable only while DALT is installed.
$router->get('/dalt-assets/assets/{asset}', static fn (string $asset): \Core\Response => \Core\DaltAssets::response($asset));
