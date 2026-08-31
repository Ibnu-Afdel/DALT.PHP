<?php

declare(strict_types=1);

use Core\Database;
use Core\DatabaseManager;
use Core\HttpException;

function memoryDatabase(): Database
{
    return DatabaseManager::create([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
}

test('the manager creates an in-memory connection without schema side effects', function () {
    $database = memoryDatabase();

    expect($database->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME))->toBe('sqlite')
        ->and($database->getConnection()->getAttribute(\PDO::ATTR_ERRMODE))->toBe(\PDO::ERRMODE_EXCEPTION)
        ->and($database->getConnection()->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE))->toBe(\PDO::FETCH_ASSOC)
        ->and($database->getConnection()->getAttribute(\PDO::ATTR_STRINGIFY_FETCHES))->toBeFalse()
        ->and($database->query("SELECT name FROM sqlite_master WHERE type = 'table'")->get())->toBe([]);
});

test('the manager preserves SQLite temporary database mode', function () {
    $database = DatabaseManager::create([
        'driver' => 'sqlite',
        'database' => '',
    ]);

    expect($database->query('SELECT 1 AS value')->find())->toBe(['value' => 1]);
});

test('queries support positional and named bindings with associative fetches', function () {
    $database = memoryDatabase();
    $database->query('CREATE TABLE learners (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
    $database->query('INSERT INTO learners (id, name) VALUES (?, ?)', [1, 'Ada']);
    $database->query(
        'INSERT INTO learners (id, name) VALUES (:id, :name)',
        ['id' => 2, 'name' => 'Grace'],
    );

    expect($database->query('SELECT id, name FROM learners WHERE id = :id', ['id' => 1])->find())
        ->toBe(['id' => 1, 'name' => 'Ada'])
        ->and($database->query('SELECT id, name FROM learners ORDER BY id')->get())->toBe([
            ['id' => 1, 'name' => 'Ada'],
            ['id' => 2, 'name' => 'Grace'],
        ]);
});

test('missing rows have distinct one-row and many-row results', function () {
    $database = memoryDatabase();

    expect($database->query('SELECT 1 AS value WHERE 0')->find())->toBeFalse()
        ->and($database->query('SELECT 1 AS value WHERE 0')->get())->toBe([]);
});

test('find or fail maps a missing row to the framework 404 boundary', function () {
    memoryDatabase()->query('SELECT 1 AS value WHERE 0')->findOrFail();
})->throws(HttpException::class, 'Not Found');

test('fetching before a query reports the lifecycle error', function () {
    memoryDatabase()->find();
})->throws(LogicException::class, 'Run query() before fetching results.');

test('query errors remain PDO exceptions with exception mode enabled', function () {
    memoryDatabase()->query('SELECT * FROM table_that_does_not_exist');
})->throws(\PDOException::class);

test('connection configuration rejects unsupported or incomplete drivers', function (array $config, string $message) {
    expect(fn () => DatabaseManager::create($config))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing SQLite database' => [['driver' => 'sqlite'], "Database configuration 'database' must be a string for sqlite."],
    'missing PostgreSQL host' => [[
        'driver' => 'pgsql',
        'port' => 5432,
        'dbname' => 'dalt',
    ], "Database configuration 'host' must be a safe non-empty string for pgsql."],
    'invalid PostgreSQL port' => [[
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 'not-a-port',
        'dbname' => 'dalt',
    ], "Database configuration 'port' must be an integer from 1 to 65535 for pgsql."],
    'unsafe PostgreSQL host' => [[
        'driver' => 'pgsql',
        'host' => 'localhost;password=exposed',
        'port' => 5432,
        'dbname' => 'dalt',
    ], "Database configuration 'host' must be a safe non-empty string for pgsql."],
    'missing MySQL dbname' => [[
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
    ], "Database configuration 'dbname' must be a safe non-empty string for mysql."],
    'invalid MySQL port' => [[
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 'not-a-port',
        'dbname' => 'dalt',
    ], "Database configuration 'port' must be an integer from 1 to 65535 for mysql."],
    'unsafe MySQL host' => [[
        'driver' => 'mysql',
        'host' => '127.0.0.1;password=exposed',
        'port' => 3306,
        'dbname' => 'dalt',
    ], "Database configuration 'host' must be a safe non-empty string for mysql."],
]);

test('SQLite setup rejects a parent path that is a file', function () {
    $parentFile = tempnam(sys_get_temp_dir(), 'dalt-f08-parent-');

    if (!is_string($parentFile)) {
        throw new RuntimeException('Unable to create the SQLite setup fixture.');
    }

    expect(fn () => DatabaseManager::create([
        'driver' => 'sqlite',
        'database' => $parentFile . DIRECTORY_SEPARATOR . 'database.sqlite',
    ]))->toThrow(RuntimeException::class, 'SQLite database directory path is not a directory:');

    unlink($parentFile);
});

test('relative SQLite paths resolve from the project root and create their parent directory', function () {
    $relativeDirectory = 'tests/.tmp-f08-' . bin2hex(random_bytes(4));
    $relativePath = $relativeDirectory . '/nested.sqlite';
    $absolutePath = base_path($relativePath);

    try {
        $database = DatabaseManager::create([
            'driver' => 'sqlite',
            'database' => $relativePath,
        ]);
        $database->query('CREATE TABLE proof (id INTEGER PRIMARY KEY)');

        expect(file_exists($absolutePath))->toBeTrue();
    } finally {
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }

        if (is_dir(base_path($relativeDirectory))) {
            rmdir(base_path($relativeDirectory));
        }
    }
});

test('PostgreSQL matches the query and fetch contract when test infrastructure is configured', function () {
    $json = getenv('DALT_TEST_PGSQL_CONFIG');

    if (!is_string($json) || $json === '') {
        $this->markTestSkipped('Set DALT_TEST_PGSQL_CONFIG to run PostgreSQL parity checks.');
    }

    $config = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    if (!is_array($config)) {
        throw new RuntimeException('DALT_TEST_PGSQL_CONFIG must decode to an object.');
    }

    $database = DatabaseManager::create($config);
    $database->query('CREATE TEMP TABLE dalt_f08_parity (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
    $database->query('INSERT INTO dalt_f08_parity (id, name) VALUES (:id, :name)', [
        'id' => 1,
        'name' => 'Ada',
    ]);

    expect($database->query('SELECT id, name FROM dalt_f08_parity WHERE id = ?', [1])->find())
        ->toBe(['id' => 1, 'name' => 'Ada'])
        ->and($database->query('SELECT id, name FROM dalt_f08_parity WHERE id = ?', [404])->find())
        ->toBeFalse();
});

test('MySQL matches the query and fetch contract when test infrastructure is configured', function () {
    $json = getenv('DALT_TEST_MYSQL_CONFIG');

    if (!is_string($json) || $json === '') {
        $this->markTestSkipped('Set DALT_TEST_MYSQL_CONFIG to run MySQL parity checks.');
    }

    $config = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    if (!is_array($config)) {
        throw new RuntimeException('DALT_TEST_MYSQL_CONFIG must decode to an object.');
    }

    $table = 'dalt_f08_parity_' . bin2hex(random_bytes(4));
    $database = DatabaseManager::create($config);

    try {
        $database->query("CREATE TABLE `{$table}` (id INTEGER PRIMARY KEY, name TEXT NOT NULL)");
        $database->query("INSERT INTO `{$table}` (id, name) VALUES (:id, :name)", [
            'id' => 1,
            'name' => 'Ada',
        ]);

        expect($database->query("SELECT id, name FROM `{$table}` WHERE id = ?", [1])->find())
            ->toBe(['id' => 1, 'name' => 'Ada'])
            ->and($database->query("SELECT id, name FROM `{$table}` WHERE id = ?", [404])->find())
            ->toBeFalse();
    } finally {
        $database->query("DROP TABLE IF EXISTS `{$table}`");
    }
});
