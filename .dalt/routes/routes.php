<?php

global $router;

// Learning routes
$router->get("/learn", "learn/index.php");
$router->get("/learn/fullstack", "learn/fullstack.php");
$router->get("/learn/fullstack/build/b00", "learn/fullstack-build.php");
$router->post("/learn/fullstack/build/b00/complete", "learn/fullstack-build.php")->only('csrf');
$router->get("/learn/fullstack/build/b01", "learn/fullstack-javascript-build.php");
$router->post("/learn/fullstack/build/b01/complete", "learn/fullstack-javascript-build.php")->only('csrf');
$router->get("/learn/fullstack/build/b02", "learn/fullstack-typescript-build.php");
$router->post("/learn/fullstack/build/b02/complete", "learn/fullstack-typescript-build.php")->only('csrf');
$router->get("/learn/fullstack/observe/forms", "learn/fullstack-observation.php");
$router->post("/learn/fullstack/observe/forms/traditional", "learn/fullstack-observation.php");
$router->post("/learn/fullstack/observe/forms/json", "learn/fullstack-observation.php");
$router->get("/learn/fullstack/observe/async/issue-preview", "learn/fullstack-observation.php");
$router->get("/learn/fullstack/observe/async/missing-issue", "learn/fullstack-observation.php");
$router->get("/learn/fullstack/observe/async/invalid-json", "learn/fullstack-observation.php");
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
