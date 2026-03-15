<?php

namespace Core;

class ChallengeManager
{
    private const COURSE_PATH = '.dalt/course/challenges';
    private const ACTIVE_FILE = '.dalt/active_challenge.txt';
    private const PROGRESS_FILE = '.dalt/progress.json';
    private const BASELINE_PATH = '.dalt/baseline';

    public static function getChallengesDir(): string
    {
        return base_path(self::COURSE_PATH);
    }

    public static function listChallenges(): array
    {
        $dir = self::getChallengesDir();
        if (!is_dir($dir)) {
            return [];
        }
        $items = array_filter(glob($dir . '/*'), 'is_dir');
        $challenges = array_map('basename', $items);
        sort($challenges);
        return $challenges;
    }

    public static function getChallengeMetadata(): array
    {
        return \Core\CourseLoader::getChallenges();
    }

    public static function getActiveChallenge(): ?string
    {
        $file = base_path(self::ACTIVE_FILE);
        if (!file_exists($file)) {
            return null;
        }
        $name = trim((string) file_get_contents($file));
        return $name !== '' ? $name : null;
    }

    public static function setActiveChallenge(string $name): void
    {
        $dir = dirname(base_path(self::ACTIVE_FILE));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(base_path(self::ACTIVE_FILE), $name);
    }

    public static function clearActiveChallenge(): void
    {
        $file = base_path(self::ACTIVE_FILE);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Map challenge-relative path to base app path.
     * Http/controllers/* -> app/Http/controllers/*
     */
    private static function mapToBasePath(string $relativePath): string
    {
        if (str_starts_with($relativePath, 'Http/controllers/')) {
            return 'app/' . $relativePath;
        }
        return $relativePath;
    }

    /**
     * Get all files to copy from challenge directory (convention-based).
     */
    private static function getChallengeFiles(string $challengeDir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($challengeDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $path = $item->getPathname();
                $relative = str_replace($challengeDir . DIRECTORY_SEPARATOR, '', $path);
                $relative = str_replace('\\', '/', $relative);
                $files[] = $relative;
            }
        }
        return $files;
    }

    /**
     * Backup a file to baseline before overwriting (for challenge:stop restore).
     */
    private static function backupToBaseline(string $destRelative): void
    {
        $basePath = base_path('');
        $dest = $basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $destRelative);
        if (!file_exists($dest)) {
            return;
        }
        $baselineFile = base_path(self::BASELINE_PATH . '/' . $destRelative);
        $baselineDir = dirname($baselineFile);
        if (!is_dir($baselineDir)) {
            mkdir($baselineDir, 0755, true);
        }
        if (!file_exists($baselineFile)) {
            copy($dest, $baselineFile);
        }
    }

    /**
     * Copy a single file from challenge to base, applying path mapping.
     */
    private static function copyFile(string $challengeDir, string $relativePath, string $basePath): void
    {
        $src = $challengeDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (!file_exists($src)) {
            return;
        }
        $destRelative = self::mapToBasePath($relativePath);
        self::backupToBaseline($destRelative);
        $dest = $basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $destRelative);
        $destDir = dirname($dest);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        copy($src, $dest);
    }

    /**
     * Start a challenge: copy broken files to base app.
     */
    public static function start(string $challengeName): bool
    {
        $challengeDir = base_path(self::COURSE_PATH . '/' . $challengeName);
        if (!is_dir($challengeDir)) {
            return false;
        }
        $basePath = base_path('');
        $files = self::getChallengeFiles($challengeDir);
        foreach ($files as $file) {
            $pathParts = explode('/', $file);
            $first = $pathParts[0] ?? '';
            if (in_array($first, ['README.md', 'tests.php'], true)) {
                continue;
            }
            self::copyFile($challengeDir, $file, $basePath);
        }
        self::setActiveChallenge($challengeName);
        return true;
    }

    private const MINIMAL_ROUTES = "<?php\n\nglobal \$router;\n\n\$router->get('/', 'welcome.php');\n";

    /**
     * Stop the active challenge: restore baseline and remove challenge files.
     */
    public static function stop(): bool
    {
        $active = self::getActiveChallenge();
        if ($active === null) {
            return false;
        }
        $basePath = base_path('');
        $baselinePath = base_path(self::BASELINE_PATH);
        if (is_dir($baselinePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baselinePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $relative = str_replace($baselinePath . DIRECTORY_SEPARATOR, '', $item->getPathname());
                    $relative = str_replace('\\', '/', $relative);
                    $dest = $basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                    $destDir = dirname($dest);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    copy($item->getPathname(), $dest);
                }
            }
        } else {
            file_put_contents($basePath . '/routes/routes.php', self::MINIMAL_ROUTES);
        }
        foreach (['dashboard', 'posts', 'auth', 'contact'] as $dir) {
            $path = $basePath . '/app/Http/controllers/' . $dir;
            if (is_dir($path)) {
                foreach (glob($path . '/*.php') ?: [] as $f) {
                    unlink($f);
                }
                @rmdir($path);
            }
        }
        self::clearActiveChallenge();
        return true;
    }

    /**
     * Reset: re-copy broken files from active challenge.
     */
    public static function reset(): bool
    {
        $active = self::getActiveChallenge();
        if ($active === null) {
            return false;
        }
        return self::start($active);
    }

    /**
     * Get list of passed challenges from progress file.
     */
    public static function getPassedChallenges(): array
    {
        $file = base_path(self::PROGRESS_FILE);
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data['passed'] ?? null) ? $data['passed'] : [];
    }

    /**
     * Mark a challenge as passed.
     */
    public static function markPassed(string $challengeName): void
    {
        $file = base_path(self::PROGRESS_FILE);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $passed = self::getPassedChallenges();
        if (!in_array($challengeName, $passed, true)) {
            $passed[] = $challengeName;
        }
        file_put_contents($file, json_encode(['passed' => $passed], JSON_PRETTY_PRINT));
    }
}
