<?php

declare(strict_types=1);

use Core\App;
use Core\Config;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\ValidationException;

const BASE_PATH = __DIR__ . '/../';
require BASE_PATH . 'vendor/autoload.php';
require BASE_PATH . ('framework/Core/functions.php');
require base_path('framework/Core/bootstrap.php');

$config = App::resolve(Config::class);

if (!$config instanceof Config) {
    throw new LogicException('The Config binding must resolve to Core\\Config.');
}

Session::start($config->array('session'));

if (is_dir(base_path('.dalt')) && file_exists(base_path('.dalt/bootstrap.php'))) {
    require base_path('.dalt/bootstrap.php');
}

$router = new \Core\Router(App::container());

require base_path('routes/routes.php');

if (is_dir(base_path('.dalt')) && file_exists(base_path('.dalt/routes/routes.php'))) {
    require base_path('.dalt/routes/routes.php');
}

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
} catch (\Core\HttpException $exception) {
    app_log('HttpException ' . $exception->statusCode . ': ' . $exception->getMessage());
    $response = Response::html(
        "<h1>" . htmlspecialchars((string) $exception->statusCode) . "</h1>"
        . "<p>" . htmlspecialchars($exception->getMessage()) . "</p>",
        $exception->statusCode,
    );
} catch (\Throwable $e) {
    app_log(get_class($e) . ': ' . $e->getMessage());
    throw $e;
}

$response->send();
