<?php

declare(strict_types=1);

namespace Core;

use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class ChallengeManager
{
    private const COURSE_PATH = '.dalt/course/challenges';
    private const ACTIVE_FILE = '.dalt/active_challenge.txt';
    private const STATE_FILE = '.dalt/challenge-state.json';
    private const BACKUP_PATH = '.dalt/challenge-backup';
    private const LOCK_FILE = '.dalt/challenge.lock';
    private const PROGRESS_FILE = '.dalt/progress.json';

    /** @var list<string> */
    private const CONTENT_FILES = ['README.md', 'meta.json', 'tests.php'];

    public static function getChallengesDir(): string
    {
        return base_path(self::COURSE_PATH);
    }

    /** @return list<string> */
    public static function listChallenges(): array
    {
        $dir = self::getChallengesDir();
        if (!is_dir($dir)) {
            return [];
        }

        $items = array_filter(glob($dir . '/*') ?: [], 'is_dir');
        $challenges = array_values(array_map('basename', $items));
        sort($challenges, SORT_STRING);

        return $challenges;
    }

    /** @return list<array<string, mixed>> */
    public static function getChallengeMetadata(): array
    {
        return CourseLoader::getChallenges();
    }

    public static function getActiveChallenge(): ?string
    {
        $state = self::readState();
        if ($state !== null) {
            return $state['challenge'];
        }

        $legacyFile = base_path(self::ACTIVE_FILE);
        if (!is_file($legacyFile) || is_link($legacyFile)) {
            return null;
        }

        $name = self::readFile($legacyFile, 'active challenge marker');

        return self::validChallengeId(trim($name)) ? trim($name) : null;
    }

    private static function setActiveChallenge(string $name): void
    {
        if (!self::validChallengeId($name)) {
            throw new ChallengeStateException("Invalid challenge ID '{$name}'.");
        }

        self::atomicWrite(base_path(self::ACTIVE_FILE), $name);
    }

    private static function clearActiveChallenge(): void
    {
        $file = base_path(self::ACTIVE_FILE);
        if ((file_exists($file) || is_link($file)) && !unlink($file)) {
            throw new ChallengeStateException('Unable to remove the active challenge marker.');
        }
    }

    public static function start(string $challengeName): bool
    {
        if (!self::validChallengeId($challengeName)) {
            return false;
        }

        return self::withLock(function () use ($challengeName): bool {
            $challengeDir = base_path(self::COURSE_PATH . '/' . $challengeName);
            if (!is_dir($challengeDir) || is_link($challengeDir)) {
                return false;
            }

            $existingState = self::readState();
            if ($existingState !== null || file_exists(base_path(self::ACTIVE_FILE))) {
                $active = $existingState['challenge'] ?? self::getActiveChallenge() ?? 'unknown';
                throw new ChallengeStateException(
                    "Challenge '{$active}' is already active. Stop it before starting another challenge.",
                );
            }

            self::removeAbandonedBackup();
            $plan = self::buildPlan($challengeDir);
            if ($plan === []) {
                throw new ChallengeStateException("Challenge '{$challengeName}' has no mutable files.");
            }

            $createdDirectories = self::directoriesToCreate($plan);
            $state = [
                'version' => 1,
                'challenge' => $challengeName,
                'phase' => 'prepared',
                'created_directories' => $createdDirectories,
                'entries' => [],
            ];

            self::createDirectory(base_path(self::BACKUP_PATH));
            try {
                foreach ($plan as $entry) {
                    $destination = base_path($entry['destination']);
                    self::assertSafeDestination($entry['destination']);
                    $existed = file_exists($destination) || is_link($destination);
                    if ($existed) {
                        self::assertRegularFile($destination, 'destination');
                        $backup = self::BACKUP_PATH . '/files/' . $entry['destination'];
                        self::atomicCopy($destination, base_path($backup));
                    } else {
                        $backup = null;
                    }

                    $state['entries'][] = [
                        'source' => $entry['source'],
                        'destination' => $entry['destination'],
                        'existed' => $existed,
                        'backup' => $backup,
                    ];
                }

                // The recovery manifest exists before the first learner file changes.
                self::writeState($state);
                foreach ($plan as $entry) {
                    self::atomicCopy($entry['source_absolute'], base_path($entry['destination']));
                }
                self::setActiveChallenge($challengeName);
                $state['phase'] = 'active';
                self::writeState($state);
            } catch (Throwable $exception) {
                try {
                    if (file_exists(base_path(self::STATE_FILE))) {
                        self::restore($state);
                        self::removeRuntimeState();
                    } else {
                        self::removeTree(base_path(self::BACKUP_PATH));
                    }
                } catch (Throwable $rollbackException) {
                    throw new ChallengeStateException(
                        'Challenge start failed and automatic restoration also failed. Run challenge:stop to recover: '
                        . $rollbackException->getMessage(),
                        0,
                        $exception,
                    );
                }

                throw new ChallengeStateException('Unable to start challenge: ' . $exception->getMessage(), 0, $exception);
            }

            return true;
        });
    }

    public static function stop(): bool
    {
        return self::withLock(function (): bool {
            $state = self::readState();
            if ($state === null) {
                if (file_exists(base_path(self::ACTIVE_FILE))) {
                    throw new ChallengeStateException(
                        'A legacy active challenge has no recovery manifest; no files were changed. Restore it manually before removing the marker.',
                    );
                }

                return false;
            }

            self::restore($state);
            self::removeRuntimeState();

            return true;
        });
    }

    public static function reset(): bool
    {
        return self::withLock(function (): bool {
            $state = self::readState();
            if ($state === null) {
                return false;
            }

            $challengeDir = base_path(self::COURSE_PATH . '/' . $state['challenge']);
            if (!is_dir($challengeDir) || is_link($challengeDir)) {
                throw new ChallengeStateException('The active challenge source directory is missing or unsafe.');
            }

            $plan = self::buildPlan($challengeDir);
            $expected = array_column($state['entries'], 'destination');
            $actual = array_column($plan, 'destination');
            if ($expected !== $actual) {
                throw new ChallengeStateException('The active challenge file set changed; stop it before retrying.');
            }

            foreach ($plan as $entry) {
                self::assertSafeDestination($entry['destination']);
                self::atomicCopy($entry['source_absolute'], base_path($entry['destination']));
            }

            return true;
        });
    }

    /** @return list<string> */
    public static function getPassedChallenges(): array
    {
        return ProgressManager::getPassedChallenges();
    }

    public static function markPassed(string $challengeName): void
    {
        if (!self::validChallengeId($challengeName)) {
            throw new ChallengeStateException("Invalid challenge ID '{$challengeName}'.");
        }

        ProgressManager::markChallengePassed($challengeName);
    }

    /**
     * @return list<array{source: string, source_absolute: string, destination: string}>
     */
    private static function buildPlan(string $challengeDir): array
    {
        $plan = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($challengeDir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new ChallengeStateException('Challenge sources cannot contain symbolic links.');
            }
            if (!$item->isFile()) {
                continue;
            }

            $relative = substr($item->getPathname(), strlen($challengeDir) + 1);
            $relative = str_replace('\\', '/', $relative);
            if (in_array($relative, self::CONTENT_FILES, true)) {
                continue;
            }
            if (!self::validRelativePath($relative)) {
                throw new ChallengeStateException("Unsafe challenge source path '{$relative}'.");
            }

            $destination = str_starts_with($relative, 'Http/controllers/')
                ? 'app/' . $relative
                : $relative;
            if (!self::allowedDestination($destination)) {
                throw new ChallengeStateException("Challenge file '{$relative}' maps outside the mutable allowlist.");
            }

            self::assertRegularFile($item->getPathname(), 'challenge source');
            $plan[] = [
                'source' => $relative,
                'source_absolute' => $item->getPathname(),
                'destination' => $destination,
            ];
        }

        usort($plan, static fn (array $left, array $right): int => $left['destination'] <=> $right['destination']);
        $destinations = array_column($plan, 'destination');
        if (count($destinations) !== count(array_unique($destinations))) {
            throw new ChallengeStateException('Challenge files map to duplicate destinations.');
        }

        return $plan;
    }

    private static function allowedDestination(string $path): bool
    {
        return preg_match(
            '~\A(?:'
            . 'framework/Core/[A-Za-z][A-Za-z0-9]*(?:/[A-Za-z][A-Za-z0-9]*)*\.php'
            . '|routes/routes\.php'
            . '|app/Http/controllers/[a-z0-9][a-z0-9-]*(?:/[a-z0-9][a-z0-9-]*)*\.php'
            . '|database/migrations/[0-9][A-Za-z0-9_-]*\.sql'
            . '|Dockerfile'
            . '|docker-compose\.yml'
            . '|nginx/[a-z0-9][a-z0-9._-]*\.conf'
            . ')\z~D',
            $path,
        ) === 1;
    }

    private static function validRelativePath(string $path): bool
    {
        return $path !== ''
            && !str_contains($path, "\0")
            && !str_contains($path, '\\')
            && !str_starts_with($path, '/')
            && preg_match('~(?:\A|/)\.\.?(?:/|\z)~', $path) !== 1
            && !str_contains($path, '//');
    }

    private static function validChallengeId(string $name): bool
    {
        return preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $name) === 1;
    }

    /** @param list<array{destination: string}> $plan @return list<string> */
    private static function directoriesToCreate(array $plan): array
    {
        $directories = [];
        foreach ($plan as $entry) {
            $directory = dirname($entry['destination']);
            while ($directory !== '.' && !is_dir(base_path($directory))) {
                if (is_link(base_path($directory))) {
                    throw new ChallengeStateException("Destination directory '{$directory}' is a symbolic link.");
                }
                $directories[] = $directory;
                $directory = dirname($directory);
            }
        }

        $directories = array_values(array_unique($directories));
        usort($directories, static fn (string $left, string $right): int => substr_count($left, '/') <=> substr_count($right, '/'));

        return $directories;
    }

    private static function assertSafeDestination(string $relative): void
    {
        if (!self::validRelativePath($relative) || !self::allowedDestination($relative)) {
            throw new ChallengeStateException("Unsafe challenge destination '{$relative}'.");
        }

        $path = base_path($relative);
        $root = rtrim(base_path(), '/\\');
        $parent = dirname($path);
        while ($parent !== $root && str_starts_with($parent, $root . DIRECTORY_SEPARATOR)) {
            if (is_link($parent)) {
                throw new ChallengeStateException("Challenge destination uses symbolic-link directory '{$parent}'.");
            }
            $parent = dirname($parent);
        }
        if ($parent !== $root) {
            throw new ChallengeStateException("Challenge destination escapes the project root: '{$relative}'.");
        }
        if (is_link($path)) {
            throw new ChallengeStateException("Challenge destination '{$relative}' is a symbolic link.");
        }
    }

    private static function assertRegularFile(string $path, string $label): void
    {
        if (is_link($path) || !is_file($path)) {
            throw new ChallengeStateException("The {$label} '{$path}' must be a regular file, not a link.");
        }

        $status = lstat($path);
        if ($status === false || ($status['nlink'] ?? 1) > 1) {
            throw new ChallengeStateException("The {$label} '{$path}' has an unsafe hard-link count.");
        }
    }

    private static function atomicCopy(string $source, string $destination): void
    {
        self::assertRegularFile($source, 'copy source');
        $permissions = fileperms($source);
        if ($permissions === false) {
            throw new ChallengeStateException("Unable to read permissions for copy source '{$source}'.");
        }
        self::createDirectory(dirname($destination));
        if (is_link($destination) || is_dir($destination)) {
            throw new ChallengeStateException("Refusing to replace unsafe destination '{$destination}'.");
        }

        self::atomicWrite($destination, self::readFile($source, 'copy source'), $permissions & 0777);
    }

    private static function atomicWrite(string $path, string $contents, ?int $permissions = null): void
    {
        self::createDirectory(dirname($path));
        if (is_link($path) || is_dir($path)) {
            throw new ChallengeStateException("Refusing to write unsafe path '{$path}'.");
        }

        $temporary = tempnam(dirname($path), '.dalt-');
        if ($temporary === false) {
            throw new ChallengeStateException("Unable to create a temporary file beside '{$path}'.");
        }

        try {
            $written = file_put_contents($temporary, $contents, LOCK_EX);
            if ($written !== strlen($contents)) {
                throw new ChallengeStateException("Unable to write complete contents for '{$path}'.");
            }
            if ($permissions !== null && !chmod($temporary, $permissions)) {
                throw new ChallengeStateException("Unable to preserve permissions for '{$path}'.");
            }
            if (file_exists($path) && !is_writable($path)) {
                throw new ChallengeStateException("Destination '{$path}' is not writable.");
            }
            if (!rename($temporary, $path)) {
                throw new ChallengeStateException("Unable to atomically replace '{$path}'.");
            }
        } finally {
            if (file_exists($temporary)) {
                unlink($temporary);
            }
        }
    }

    private static function createDirectory(string $directory): void
    {
        if (is_link($directory)) {
            throw new ChallengeStateException("Refusing symbolic-link directory '{$directory}'.");
        }
        if (is_dir($directory)) {
            return;
        }
        if (file_exists($directory) || !mkdir($directory, 0755, true)) {
            throw new ChallengeStateException("Unable to create directory '{$directory}'.");
        }
    }

    /** @param array<string, mixed> $state */
    private static function restore(array $state): void
    {
        foreach ($state['entries'] as $entry) {
            self::assertSafeDestination($entry['destination']);
            $destination = base_path($entry['destination']);
            if ($entry['existed']) {
                self::atomicCopy(base_path($entry['backup']), $destination);
            } elseif (file_exists($destination) || is_link($destination)) {
                if (is_dir($destination) || !unlink($destination)) {
                    throw new ChallengeStateException("Unable to remove challenge-created file '{$destination}'.");
                }
            }
        }

        $directories = $state['created_directories'];
        usort($directories, static fn (string $left, string $right): int => substr_count($right, '/') <=> substr_count($left, '/'));
        foreach ($directories as $directory) {
            $absolute = base_path($directory);
            if (is_dir($absolute) && !is_link($absolute) && (new FilesystemIterator($absolute))->valid() === false) {
                if (!rmdir($absolute)) {
                    throw new ChallengeStateException("Unable to remove challenge-created directory '{$absolute}'.");
                }
            }
        }
    }

    /** @return array{version: int, challenge: string, phase: string, created_directories: list<string>, entries: list<array{source: string, destination: string, existed: bool, backup: ?string}>}|null */
    private static function readState(): ?array
    {
        $file = base_path(self::STATE_FILE);
        if (!file_exists($file)) {
            return null;
        }
        self::assertRegularFile($file, 'challenge recovery manifest');

        try {
            $state = json_decode(self::readFile($file, 'challenge recovery manifest'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ChallengeStateException('The challenge recovery manifest is malformed JSON.', 0, $exception);
        }

        if (!is_array($state)
            || ($state['version'] ?? null) !== 1
            || !is_string($state['challenge'] ?? null)
            || !self::validChallengeId($state['challenge'])
            || !in_array($state['phase'] ?? null, ['prepared', 'active'], true)
            || !is_array($state['created_directories'] ?? null)
            || !is_array($state['entries'] ?? null)
        ) {
            throw new ChallengeStateException('The challenge recovery manifest has an invalid structure.');
        }

        foreach ($state['created_directories'] as $directory) {
            if (!is_string($directory)
                || !self::validRelativePath($directory)
                || !self::isDestinationAncestor($directory, $state['entries'])
            ) {
                throw new ChallengeStateException('The challenge recovery manifest contains an unsafe directory.');
            }
        }
        if (count($state['created_directories']) !== count(array_unique($state['created_directories']))) {
            throw new ChallengeStateException('The challenge recovery manifest contains duplicate directories.');
        }
        $destinations = [];
        foreach ($state['entries'] as $entry) {
            if (!is_array($entry)
                || !is_string($entry['source'] ?? null)
                || !is_string($entry['destination'] ?? null)
                || !is_bool($entry['existed'] ?? null)
                || !array_key_exists('backup', $entry)
                || !(is_string($entry['backup']) || $entry['backup'] === null)
                || !self::validRelativePath($entry['source'])
                || !self::allowedDestination($entry['destination'])
                || ($entry['existed'] !== ($entry['backup'] !== null))
                || ($entry['backup'] !== null && $entry['backup'] !== self::BACKUP_PATH . '/files/' . $entry['destination'])
            ) {
                throw new ChallengeStateException('The challenge recovery manifest contains an invalid file entry.');
            }
            $destinations[] = $entry['destination'];
        }
        if (count($destinations) !== count(array_unique($destinations))) {
            throw new ChallengeStateException('The challenge recovery manifest contains duplicate destinations.');
        }

        /** @var array{version: int, challenge: string, phase: string, created_directories: list<string>, entries: list<array{source: string, destination: string, existed: bool, backup: ?string}>} $state */
        return $state;
    }

    /** @param list<array<string, mixed>> $entries */
    private static function isDestinationAncestor(string $directory, array $entries): bool
    {
        foreach ($entries as $entry) {
            $destination = $entry['destination'] ?? null;
            if (is_string($destination) && str_starts_with($destination, $directory . '/')) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $state */
    private static function writeState(array $state): void
    {
        self::atomicWrite(
            base_path(self::STATE_FILE),
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        );
    }

    private static function removeRuntimeState(): void
    {
        self::clearActiveChallenge();
        $stateFile = base_path(self::STATE_FILE);
        if (file_exists($stateFile) && !unlink($stateFile)) {
            throw new ChallengeStateException('Unable to remove the challenge recovery manifest.');
        }
        self::removeTree(base_path(self::BACKUP_PATH));
    }

    private static function removeAbandonedBackup(): void
    {
        if (is_dir(base_path(self::BACKUP_PATH)) || is_link(base_path(self::BACKUP_PATH))) {
            self::removeTree(base_path(self::BACKUP_PATH));
        }
    }

    private static function removeTree(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }
        if (is_link($path) || is_file($path)) {
            if (!unlink($path)) {
                throw new ChallengeStateException("Unable to remove '{$path}'.");
            }
            return;
        }

        foreach (new FilesystemIterator($path) as $entry) {
            self::removeTree($entry->getPathname());
        }
        if (!rmdir($path)) {
            throw new ChallengeStateException("Unable to remove directory '{$path}'.");
        }
    }

    private static function readFile(string $path, string $label): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new ChallengeStateException("Unable to read {$label} '{$path}'.");
        }

        return $contents;
    }

    /** @template T @param callable(): T $operation @return T */
    private static function withLock(callable $operation): mixed
    {
        self::createDirectory(base_path('.dalt'));
        $handle = fopen(base_path(self::LOCK_FILE), 'c');
        if ($handle === false) {
            throw new ChallengeStateException('Unable to open the challenge operation lock.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new ChallengeStateException('Unable to acquire the challenge operation lock.');
            }

            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
