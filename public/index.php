<?php

use Core\App;
use Core\Request;
use Core\Session;
use Core\ValidationException;
const BASE_PATH = __DIR__ . '/../';
require BASE_PATH . 'vendor/autoload.php';

//spl_autoload_register(function ($class) {
//    $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
//    require base_path("{$class}.php");
//});

// Use a unique session name per project path to avoid cross-project session reuse
$sessionName = 'daltphp_' . substr(sha1(BASE_PATH), 0, 8);
session_name($sessionName);
session_start();

require BASE_PATH . ('framework/Core/functions.php');

require base_path('framework/Core/bootstrap.php');

// Load .dalt platform bootstrap if it exists
if (is_dir(base_path('.dalt')) && file_exists(base_path('.dalt/bootstrap.php'))) {
    require base_path('.dalt/bootstrap.php');
}

$router = new \Core\Router();

// Load user routes first (user routes take priority)
$routes = require base_path('routes/routes.php');

// Load platform routes only if .dalt exists
if (is_dir(base_path('.dalt')) && file_exists(base_path('.dalt/routes/routes.php'))) {
    require base_path('.dalt/routes/routes.php');
}

$request = Request::capture();

App::bind(Request::class, fn () => $request);

$uri = $request->path();
$method = $request->method();

try {
    $router->route($uri, $method, $request);
} catch (ValidationException $exception) {
    Session::flash('errors', $exception->errors);
    Session::flash('old', $exception->old);

    redirect($router->previousUrl());
} catch (\Throwable $e) {
    app_log(get_class($e) . ': ' . $e->getMessage());
    throw $e;
}

Session::unflash();

