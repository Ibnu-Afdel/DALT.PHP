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
 * "session": {...}, "inspect": "SELECT ..."|null, "driver": "sqlite"|"pgsql"}.
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
 *
 * "driver" defaults to "sqlite", a throwaway `:memory:` database built from nothing
 * but "seed". "pgsql" is different in kind, not just connection string: it connects
 * through the project's own `.env` + `config/database.php`, exactly as the real app
 * does, because these checks test schema and roles the learner built by hand against
 * their own Postgres (see DECISIONS.md D-09). Everything after connecting — seed,
 * dispatch, inspect — runs inside one transaction that is always rolled back before
 * the process exits, so a check can never leave data behind in a real database. A
 * "pgsql" check against a project still on sqlite, or with no reachable Postgres, is
 * a loud failure with an actionable message — never a silent pass.
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
use Core\DatabaseManager;
use Core\Request;
use Core\Response;
use Core\Session;

$driver = ($spec['driver'] ?? 'sqlite') === 'pgsql' ? 'pgsql' : 'sqlite';
$transactional = false;

try {
    if ($driver === 'pgsql') {
        $dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
        if (method_exists($dotenv, 'safeLoad')) {
            $dotenv->safeLoad();
        } else {
            try {
                $dotenv->load();
            } catch (Dotenv\Exception\InvalidPathException) {
                // A missing .env is valid; config files provide their own defaults.
            }
        }

        $databaseConfigFile = require $projectRoot . '/config/database.php';
        $databaseConfig = is_array($databaseConfigFile['database'] ?? null) ? $databaseConfigFile['database'] : [];

        if (($databaseConfig['driver'] ?? 'sqlite') !== 'pgsql') {
            $fail(
                'This check requires PostgreSQL, but the project is configured for '
                . ($databaseConfig['driver'] ?? 'sqlite') . '. Set DB_DRIVER=pgsql and the matching '
                . 'DB_HOST/DB_PORT/DB_NAME/DB_USERNAME/DB_PASSWORD in .env, with the database container running, then try again.',
            );
        }

        $database = DatabaseManager::create($databaseConfig);
        $database->getConnection()->beginTransaction();
        $transactional = true;

        // Registered instead of rolled back inline so every exit path is covered,
        // including the fail() closure's own exit() calls below and any uncaught
        // fatal in learner-controlled code: a check must never leave data behind
        // in the learner's real database, no matter how it finishes.
        register_shutdown_function(static function () use ($database): void {
            try {
                if ($database->getConnection()->inTransaction()) {
                    $database->getConnection()->rollBack();
                }
            } catch (Throwable) {
                // Best effort; the connection is about to close regardless.
            }
        });
    } else {
        $database = new Database(['driver' => 'sqlite', 'database' => ':memory:']);
    }

    foreach (($spec['seed'] ?? []) as $statement) {
        if (is_string($statement) && trim($statement) !== '') {
            $database->query($statement);
        }
    }
} catch (Throwable $exception) {
    $fail($driver === 'pgsql'
        ? 'The check could not reach PostgreSQL: ' . $exception->getMessage()
            . '. Confirm the database container is running and the DB_* variables in .env match it.'
        : 'The check could not prepare its database: ' . $exception->getMessage());
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
