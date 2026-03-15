<?php

namespace Core;

class CourseLoader
{
    private const LESSONS_PATH = '.dalt/course/lessons';
    private const CHALLENGES_PATH = '.dalt/course/challenges';

    private static ?array $icons = null;

    private static function getIcons(): array
    {
        if (self::$icons === null) {
            self::$icons = [
                'lifecycle' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>',
                'routing' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>',
                'middleware' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>',
                'auth' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>',
                'database' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>',
                'session' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            ];
        }
        return self::$icons;
    }

    private static function resolveIcon(string $key): string
    {
        $icons = self::getIcons();
        return $icons[$key] ?? $icons['routing'];
    }

    /**
     * Load meta.json from a directory, return merged with defaults.
     */
    private static function loadMeta(string $dir, array $defaults = []): ?array
    {
        $file = $dir . '/meta.json';
        if (!file_exists(base_path($file))) {
            return null;
        }
        $content = file_get_contents(base_path($file));
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }
        return array_merge($defaults, $data);
    }

    /**
     * Get all lessons, sorted by folder name.
     */
    public static function getLessons(): array
    {
        $base = base_path(self::LESSONS_PATH);
        if (!is_dir($base)) {
            return [];
        }
        $dirs = array_filter(glob($base . '/*'), 'is_dir');
        $lessons = [];
        foreach ($dirs as $dir) {
            $id = basename($dir);
            if ($id === 'README.md' || str_starts_with($id, '.')) {
                continue;
            }
            $meta = self::loadMeta(self::LESSONS_PATH . '/' . $id, [
                'id' => $id,
                'title' => $id,
                'description' => '',
                'icon' => 'routing',
                'color' => 'gray',
            ]);
            if ($meta) {
                $meta['icon'] = self::resolveIcon($meta['icon'] ?? 'routing');
                $lessons[] = $meta;
            }
        }
        usort($lessons, fn($a, $b) => strcmp($a['id'], $b['id']));
        return $lessons;
    }

    /**
     * Get all challenges, sorted by id.
     */
    public static function getChallenges(): array
    {
        $base = base_path(self::CHALLENGES_PATH);
        if (!is_dir($base)) {
            return [];
        }
        $dirs = array_filter(glob($base . '/*'), 'is_dir');
        $challenges = [];
        $passed = ChallengeManager::getPassedChallenges();
        $i = 0;
        foreach ($dirs as $dir) {
            $id = basename($dir);
            if (!file_exists($dir . '/meta.json')) {
                continue;
            }
            $meta = self::loadMeta(self::CHALLENGES_PATH . '/' . $id, [
                'id' => $id,
                'title' => $id,
                'difficulty' => '?',
                'bugs' => 0,
                'lesson' => null,
                'description' => '',
            ]);
            if ($meta) {
                $meta['icon'] = self::resolveIcon(self::getLessonIconKey($meta['lesson'] ?? ''));
                $meta['num'] = ++$i;
                $meta['passed'] = in_array($id, $passed, true);
                $challenges[] = $meta;
            }
        }
        usort($challenges, fn($a, $b) => strcmp($a['id'], $b['id']));
        foreach ($challenges as $i => $c) {
            $challenges[$i]['num'] = $i + 1;
        }
        return $challenges;
    }

    private static function getLessonIconKey(?string $lessonId): string
    {
        if (!$lessonId) return 'routing';
        $map = [
            '01-request-lifecycle' => 'lifecycle',
            '02-routing' => 'routing',
            '03-middleware' => 'middleware',
            '04-authentication' => 'auth',
            '05-database' => 'database',
        ];
        return $map[$lessonId] ?? 'routing';
    }

    /**
     * Get a single challenge by id.
     */
    public static function getChallenge(string $id): ?array
    {
        $meta = self::loadMeta(self::CHALLENGES_PATH . '/' . $id, ['id' => $id]);
        if (!$meta) {
            return null;
        }
        $meta['icon'] = self::resolveIcon(self::getLessonIconKey($meta['lesson'] ?? ''));
        return $meta;
    }

    /**
     * Get a single lesson by id, with prev/next navigation.
     */
    public static function getLesson(string $id): ?array
    {
        $all = self::getLessons();
        $lesson = null;
        $prev = null;
        $next = null;
        foreach ($all as $i => $l) {
            if ($l['id'] === $id) {
                $lesson = $l;
                $prev = $i > 0 ? $all[$i - 1]['id'] : null;
                $next = $i < count($all) - 1 ? $all[$i + 1]['id'] : null;
                break;
            }
        }
        if (!$lesson) {
            return null;
        }
        $lesson['prev'] = $prev;
        $lesson['next'] = $next;
        return $lesson;
    }

    /**
     * Find challenge(s) that are linked to a lesson.
     */
    public static function getChallengesForLesson(string $lessonId): array
    {
        return array_filter(self::getChallenges(), fn($c) => ($c['lesson'] ?? '') === $lessonId);
    }
}
