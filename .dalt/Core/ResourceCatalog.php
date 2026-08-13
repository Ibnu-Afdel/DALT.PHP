<?php

declare(strict_types=1);

namespace Core;

/**
 * Loads the curated external-resources catalog from `.dalt/course/resources.php`.
 *
 * The catalog is trusted platform content, maintained the same way a challenge
 * `tests.php` is: a plain array, `require`d each call, never derived from or
 * exposed to a learner's own files. See that file's header for selection rules
 * (D-06). No static cache, matching `CourseLoader`: a request-scoped runtime
 * doesn't need one, and caching would leak stale data across Pest tests that
 * exercise different `$courseRoot` fixtures in the same process.
 */
final class ResourceCatalog
{
    private const DEFAULT_COURSE_PATH = '.dalt/course';
    private const FILENAME = 'resources.php';

    /**
     * @param string|null $courseRoot The course directory (same meaning as
     *     `CourseLoader`'s `$courseRoot` — e.g. the fixture root a test builds
     *     `lessons/`/`challenges/` under), not the project base path.
     * @return array<string, array{title: string, blurb: string|null, links: list<array<string, mixed>>}>
     */
    public static function categories(?string $courseRoot = null): array
    {
        $root = rtrim($courseRoot ?? base_path(self::DEFAULT_COURSE_PATH), '/\\');
        $path = $root . DIRECTORY_SEPARATOR . self::FILENAME;
        if (!is_file($path) || !is_readable($path)) {
            throw new CourseMetadataException('Resource catalog is missing or unreadable: ' . self::FILENAME);
        }

        $catalog = require $path;
        if (!is_array($catalog)) {
            throw new CourseMetadataException('Resource catalog must return an array: ' . self::FILENAME);
        }

        return $catalog;
    }

    /**
     * Every link across every category, each tagged with its category id/title.
     *
     * @return list<array<string, mixed>>
     */
    public static function allLinks(?string $courseRoot = null): array
    {
        $links = [];
        foreach (self::categories($courseRoot) as $categoryId => $category) {
            foreach ($category['links'] as $link) {
                $link['category'] = $categoryId;
                $link['category_title'] = $category['title'];
                $links[] = $link;
            }
        }

        return $links;
    }

    /**
     * Links tagged for one lesson, in catalog order.
     *
     * @return list<array<string, mixed>>
     */
    public static function forLesson(string $lessonId, ?string $courseRoot = null): array
    {
        return array_values(array_filter(
            self::allLinks($courseRoot),
            static fn (array $link): bool => in_array($lessonId, $link['lessons'], true),
        ));
    }

    /**
     * The full category structure, narrowed to links tagged for at least one
     * of the given lessons. Categories left with no matching links are dropped.
     *
     * @param list<string> $lessonIds
     * @return array<string, array{title: string, blurb: string|null, links: list<array<string, mixed>>}>
     */
    public static function categoriesForLessons(array $lessonIds, ?string $courseRoot = null): array
    {
        $narrowed = [];
        foreach (self::categories($courseRoot) as $categoryId => $category) {
            $links = array_values(array_filter(
                $category['links'],
                static fn (array $link): bool => array_intersect($lessonIds, $link['lessons']) !== [],
            ));
            if ($links !== []) {
                $narrowed[$categoryId] = ['title' => $category['title'], 'blurb' => $category['blurb'], 'links' => $links];
            }
        }

        return $narrowed;
    }
}
