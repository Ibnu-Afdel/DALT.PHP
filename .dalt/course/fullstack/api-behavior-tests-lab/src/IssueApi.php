<?php

declare(strict_types=1);

namespace FsLab;

use PDO;
use PDOException;
use Throwable;

/**
 * A deliberately small issue API, shaped like the one FS05.3 asks you to build.
 *
 * It exists so FS06.1 has something real to test. It is not the application you are
 * building: it uses in-memory SQLite so the lab runs anywhere with no configuration,
 * and it handles requests as method-plus-path rather than through DALT's router.
 * Everything the lesson is actually about — status codes, validation, stored effects,
 * transaction boundaries — behaves the same way here as it will in your project.
 *
 * Read it before you read the tests. A test you cannot check against an implementation
 * is a test you are trusting rather than understanding.
 */
final class IssueApi
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function withSchema(): self
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // SQLite does not enforce foreign keys unless asked. Forgetting this is a
        // classic way to have constraints that quietly never fire.
        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->exec(<<<'SQL'
            CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            );
            CREATE TABLE issues (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE RESTRICT,
                title TEXT NOT NULL CHECK (length(trim(title)) > 0),
                status TEXT NOT NULL DEFAULT 'todo'
                    CHECK (status IN ('todo','in_progress','done')),
                priority TEXT NOT NULL DEFAULT 'medium'
                    CHECK (priority IN ('low','medium','high'))
            );
            CREATE TABLE activity (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                issue_id INTEGER NOT NULL REFERENCES issues(id) ON DELETE CASCADE,
                message TEXT NOT NULL CHECK (length(message) <= 40)
            );
            SQL);

        return new self($pdo);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function seedProject(string $name = 'Website'): int
    {
        $statement = $this->pdo->prepare('INSERT INTO projects (name) VALUES (?)');
        $statement->execute([$name]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Handle one request.
     *
     * @param array<string, mixed>|null $body
     * @return array{status: int, body: array<string, mixed>|null}
     */
    public function handle(string $method, string $path, ?array $body = null): array
    {
        if ($method === 'POST' && $path === '/api/issues') {
            return $this->store($body ?? []);
        }

        if (preg_match('#^/api/issues/(\d+)$#', $path, $match) === 1) {
            $id = (int) $match[1];

            return match ($method) {
                'GET' => $this->show($id),
                'DELETE' => $this->destroy($id),
                default => self::error(405, 'method_not_allowed', 'Method not allowed.'),
            };
        }

        return self::error(404, 'not_found', 'No such route.');
    }

    /**
     * @param array<string, mixed> $body
     * @return array{status: int, body: array<string, mixed>|null}
     */
    private function store(array $body): array
    {
        $errors = [];

        $title = is_string($body['title'] ?? null) ? trim($body['title']) : '';
        if ($title === '') {
            $errors['title'] = 'Required';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'] = 'Must be 200 characters or fewer';
        }

        $projectId = is_int($body['project_id'] ?? null) ? $body['project_id'] : null;
        if ($projectId === null) {
            $errors['project_id'] = 'Required';
        }

        $priority = $body['priority'] ?? 'medium';
        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            $errors['priority'] = 'Must be low, medium, or high';
        }

        if ($errors !== []) {
            return self::error(422, 'validation_failed', 'The issue could not be created.', $errors);
        }

        // Two writes, one business fact: an issue always has a creation record.
        $this->pdo->beginTransaction();

        try {
            $insert = $this->pdo->prepare(
                'INSERT INTO issues (project_id, title, priority) VALUES (?, ?, ?)',
            );
            $insert->execute([$projectId, $title, $priority]);
            $id = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare('INSERT INTO activity (issue_id, message) VALUES (?, ?)')
                ->execute([$id, 'Created: ' . $title]);

            $this->pdo->commit();
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // A missing parent project is the client's mistake, and it is the only
            // database error this handler claims to understand.
            if (str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
                return self::error(404, 'not_found', 'That project does not exist.');
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return ['status' => 201, 'body' => ['data' => $this->issueResource($id)]];
    }

    /** @return array{status: int, body: array<string, mixed>|null} */
    private function show(int $id): array
    {
        $row = $this->findIssue($id);

        return $row === null
            ? self::error(404, 'not_found', 'That issue does not exist.')
            : ['status' => 200, 'body' => ['data' => $row]];
    }

    /** @return array{status: int, body: array<string, mixed>|null} */
    private function destroy(int $id): array
    {
        if ($this->findIssue($id) === null) {
            return self::error(404, 'not_found', 'That issue does not exist.');
        }

        $this->pdo->prepare('DELETE FROM activity WHERE issue_id = ?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM issues WHERE id = ?')->execute([$id]);

        // 204: the deletion happened and there is nothing to say about it.
        return ['status' => 204, 'body' => null];
    }

    /** @return array<string, mixed>|null */
    private function findIssue(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM issues WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->toResource($row);
    }

    /** @return array<string, mixed> */
    private function issueResource(int $id): array
    {
        return $this->findIssue($id) ?? throw new PDOException('Issue vanished after insert.');
    }

    /**
     * The public shape. Note what is not here: the response is built field by field,
     * so a new column cannot become public by accident.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function toResource(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'projectId' => (string) $row['project_id'],
            'title' => $row['title'],
            'status' => $row['status'],
            'priority' => $row['priority'],
        ];
    }

    /**
     * @param array<string, string> $fields
     * @return array{status: int, body: array<string, mixed>}
     */
    private static function error(int $status, string $code, string $message, array $fields = []): array
    {
        $error = ['code' => $code, 'message' => $message];

        if ($fields !== []) {
            $error['fields'] = $fields;
        }

        return ['status' => $status, 'body' => ['error' => $error]];
    }
}
