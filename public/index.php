<?php

declare(strict_types=1);

use Core\App;
use Core\Config;
use Core\ExceptionHandler;
use Core\Platform;
use Core\Request;
use Core\Session;
use Core\ValidationException;

const BASE_PATH = __DIR__ . '/../';
require BASE_PATH . 'vendor/autoload.php';
require BASE_PATH . ('framework/Core/functions.php');
require base_path('framework/Core/bootstrap.php');

$config = App::resolve(Config::class);
$platform = App::resolve(Platform::class);

if (!$config instanceof Config) {
    throw new LogicException('The Config binding must resolve to Core\\Config.');
}
if (!$platform instanceof Platform) {
    throw new LogicException('The Platform binding must resolve to Core\\Platform.');
}

$exceptionHandler = new ExceptionHandler($config->boolean('app.debug'));

try {
    Session::start($config->array('session'));

    $platform->boot();

    $router = new \Core\Router(App::container());

    require base_path('routes/routes.php');

    $platform->registerRoutes($router);

    $request = Request::capture();
    App::instance(Request::class, $request);

    $uri = $request->path();
    $method = $request->method();

    try {
        $response = $router->route($uri, $method, $request);
    } catch (ValidationException $exception) {
        Session::flash('errors', $exception->errors);
        Session::flash('old', $exception->old);
        $response = redirect($router->previousUrl());
    }
} catch (\Throwable $exception) {
    $exceptionHandler->report($exception);
    $response = $exceptionHandler->render($exception);
}

$response->send();
