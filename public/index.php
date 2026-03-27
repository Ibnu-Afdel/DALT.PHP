<?php

use Core\App;
use Core\Request;
use Core\Session;
use Core\ValidationException;

const BASE_PATH = __DIR__ . '/../';
require BASE_PATH . 'vendor/autoload.php';

$sessionName = 'daltphp_' . substr(sha1(BASE_PATH), 0, 8);
session_name($sessionName);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require BASE_PATH . ('framework/Core/functions.php');
require base_path('framework/Core/bootstrap.php');

if (is_dir(base_path('.dalt')) && file_exists(base_path('.dalt/bootstrap.php'))) {
    require base_path('.dalt/bootstrap.php');
}

$router = new \Core\Router();

require base_path('routes/routes.php');

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
} catch (\Core\HttpException $exception) {
    app_log('HttpException ' . $exception->statusCode . ': ' . $exception->getMessage());
    http_response_code($exception->statusCode);
    echo "<h1>" . htmlspecialchars((string) $exception->statusCode) . "</h1>";
    echo "<p>" . htmlspecialchars($exception->getMessage()) . "</p>";
} catch (\Throwable $e) {
    app_log(get_class($e) . ': ' . $e->getMessage());
    throw $e;
}

Session::unflash();
