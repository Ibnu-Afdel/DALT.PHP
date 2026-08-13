<?php

/**
 * Verification-only file. Never linked from routes/routes.php — nothing in
 * the app ever dispatches to this URL. It exists so the handler_result check
 * type (which requires a controller to execute) can apply the *actual*
 * files under database/migrations/ — same glob + SORT_STRING filename
 * ordering as Migration::migrationFiles() — and report which table each
 * specific file created. That is the only way to tell "001_create_users_table.sql
 * really creates users" from "the word users appears somewhere in the
 * migrations directory" — and it's the only way to test this challenge at
 * all under SQLite, since SQLite neither rejects a forward foreign-key
 * reference at CREATE TABLE time nor validates BIGSERIAL/AUTOINCREMENT
 * against a fixed type list, so neither bug produces a SQLite error in
 * either its broken or fixed state.
 *
 * These migrations are written in native Postgres SQL (that is the point of
 * the challenge), and one part of that — `TIMESTAMPTZ DEFAULT NOW()` — SQLite
 * cannot parse at all: SQLite's DEFAULT clause does not accept an arbitrary
 * function call. Both files use it on an unrelated `created_at` column, so it
 * is normalized to a SQLite-native equivalent before executing, the same way
 * Migration::convertSqliteSqlToPgsql() already translates syntax the other
 * direction for the real runner. It never touches the `id` column, which is
 * what this challenge's second bug is actually about.
 */

use Core\App;
use Core\Database;

$database = App::resolve(Database::class);
$connection = $database->getConnection();

$tableNames = static function () use ($connection): array {
    return $connection->query("SELECT name FROM sqlite_master WHERE type = 'table'")
        ->fetchAll(PDO::FETCH_COLUMN);
};

// Reads the *declared* type via PRAGMA table_info rather than grepping
// sqlite_master's stored CREATE TABLE text. SQLite preserves SQL comments
// verbatim in that stored text — `id INTEGER PRIMARY KEY AUTOINCREMENT, --
// BIGSERIAL` would satisfy a raw `contains 'BIGSERIAL'` check without
// changing anything real. PRAGMA table_info parses the column definition
// structurally and has no comment to carry.
$columnType = static function (string $table, string $column) use ($connection): ?string {
    foreach ($connection->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC) as $info) {
        if ($info['name'] === $column) {
            return (string) $info['type'];
        }
    }

    return null;
};

$sqliteCompatible = static function (string $sql): string {
    $sql = preg_replace('/\bNOW\(\)/i', 'CURRENT_TIMESTAMP', $sql) ?? $sql;

    return preg_replace('/\bTIMESTAMPTZ\b/i', 'TEXT', $sql) ?? $sql;
};

$files = glob(base_path('database/migrations') . DIRECTORY_SEPARATOR . '*.sql') ?: [];
sort($files, SORT_STRING);

// Maps each table name to the file that created it. AUTOINCREMENT makes
// SQLite create an extra bookkeeping table (sqlite_sequence) alongside the
// one actually named in the migration, so "which tables came out of this
// file" is noisier to assert on than "which file created this table" —
// this is the latter, inverted for a clean lookup by table name.
$fileThatCreated = [];
$error = null;

foreach ($files as $file) {
    $before = $tableNames();

    try {
        $connection->exec($sqliteCompatible((string) file_get_contents($file)));
    } catch (\Throwable $exception) {
        $error = basename($file) . ': ' . $exception->getMessage();
        break;
    }

    foreach (array_diff($tableNames(), $before) as $created) {
        $fileThatCreated[$created] = basename($file);
    }
}

// A relationship-through-insert probe was tried here and dropped: BIGSERIAL
// has no special meaning to SQLite (unlike the literal keyword INTEGER, it
// does not alias the rowid), so an insert relying on auto-assigned ids
// would fail on the *correctly fixed* schema too — a false positive for the
// wrong reason. Explicit ids sidestep that, but at that point the check no
// longer independently discriminates the broken case from the fixed one:
// if the two schema checks below already pass, a same-shaped insert always
// succeeds regardless. Schema introspection is the reliable signal here.

$postsIdType = $columnType('posts', 'id');

return [
    'error' => $error,
    'users_created_by' => $fileThatCreated['users'] ?? null,
    'posts_created_by' => $fileThatCreated['posts'] ?? null,
    // Uppercased: SQL type keywords are case-insensitive, the check below isn't.
    'posts_id_type' => $postsIdType !== null ? strtoupper($postsIdType) : null,
];
