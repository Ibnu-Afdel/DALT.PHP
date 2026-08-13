<?php

declare(strict_types=1);

/**
 * Isolated probe for the handler_result check.
 *
 * Executes a learner controller against a throwaway seeded database and reports
 * the response it produced. This is what distinguishes "the source looks right"
 * from "the code does the right thing": a fix written into dead code changes the
 * source and leaves this result unchanged.
 *
 * Runs as its own process because a controller is arbitrary learner code — it can
 * fatal, print, or exit, none of which the verifier should absorb.
 *
 * Usage: php probe-handler-result.php <projectRoot> <controllerFile> <jsonSpec>
 * where jsonSpec is {"seed": [...sql], "query": {...}, "input": {...}, "route": {...},
 * "session": {...}, "inspect": "SELECT ..."|null}.
 * Prints a single JSON object on stdout.
 *
 * "session" pre-populates $_SESSION before dispatch (e.g. simulating flash data left
 * over from a previous request), and \Core\Session::ageFlashData() runs immediately
 * before the handler, mirroring the real front-controller's request-start boundary.
 * The final $_SESSION is always reported back, win or lose — this is the only way to
 * observe session state, since the controller's HTTP response rarely carries it.
 *
 * "inspect" is an optional read-only SQL statement run against the same seeded
 * database once the handler returns successfully. It exists for the same reason
 * "session" does: some bugs (a transaction that partially commits) are invisible in
 * the response body and only show up in the data the handler left behind.
 */

if ($argc !== 4) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => 'The handler probe received the wrong arguments.']));
    exit(1);
}

[, $projectRoot, $controllerFile, $rawSpec] = $argv;

$fail = static function (string $message): never {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => $message]));
    exit(0);
};

$spec = json_decode($rawSpec, true);
if (!is_array($spec)) {
    $fail('The handler probe received an unreadable specification.');
}

if (!is_file($projectRoot . '/vendor/autoload.php')) {
    $fail('Composer autoloader not found.');
}
if (!is_file($controllerFile)) {
    $fail('Controller file not found.');
}

define('BASE_PATH', $projectRoot . '/');
require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/framework/Core/functions.php';

use Core\App;
use Core\Container;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

try {
    $database = new Database(['driver' => 'sqlite', 'database' => ':memory:']);

    foreach (($spec['seed'] ?? []) as $statement) {
        if (is_string($statement) && trim($statement) !== '') {
            $database->query($statement);
        }
    }
} catch (Throwable $exception) {
    $fail('The check could not prepare its database: ' . $exception->getMessage());
}

$query = is_array($spec['query'] ?? null) ? $spec['query'] : [];
$input = is_array($spec['input'] ?? null) ? $spec['input'] : [];
$route = is_array($spec['route'] ?? null) ? $spec['route'] : [];
$inspect = is_string($spec['inspect'] ?? null) ? $spec['inspect'] : null;

$_GET = $query;
$_POST = $input;
$_SESSION = is_array($spec['session'] ?? null) ? $spec['session'] : [];

// Mirrors Session::start()'s request-start boundary so a seeded "previous
// request" flash state ages the same way it would in a real request.
Session::ageFlashData();

$request = new Request(
    query: $query,
    input: $input,
    server: [
        'REQUEST_METHOD' => $input === [] ? 'GET' : 'POST',
        'REQUEST_URI' => '/',
    ],
);
$request->setRouteParameters(array_map(static fn ($value): string => (string) $value, $route));

// Existing controllers read route values from $_GET as well.
foreach ($route as $key => $value) {
    $_GET[(string) $key] = (string) $value;
}

$container = new Container();
$container->instance(Database::class, $database);
$container->instance(Request::class, $request);
App::setContainer($container);

try {
    $response = Response::fromHandler(static fn (): mixed => require $controllerFile);
} catch (Throwable $exception) {
    $fail('The handler threw ' . $exception::class . ': ' . $exception->getMessage());
}

$inspected = null;
if ($inspect !== null) {
    try {
        $inspected = $database->query($inspect)->get();
    } catch (Throwable $exception) {
        $fail('The check could not inspect the database after the handler ran: ' . $exception->getMessage());
    }
}

fwrite(STDOUT, json_encode([
    'ok' => true,
    'status' => $response->status(),
    'body' => $response->content(),
    'session' => $_SESSION,
    'inspect' => $inspected,
]));

exit(0);
