<?php

declare(strict_types=1);

namespace Core;

/** The deliberately small, course-owned representation of the Fullstack journey. */
final class FullstackTrack
{
    /** @return array{title: string, description: string, parts: array<string, array<string, mixed>>} */
    public static function load(?string $courseRoot = null): array
    {
        $root = rtrim($courseRoot ?? base_path('.dalt/course'), '/\\');
        $path = $root . '/fullstack.php';
        if (!is_file($path) || !is_readable($path)) {
            throw new CourseMetadataException('Fullstack track manifest is missing or unreadable: fullstack.php');
        }

        $track = require $path;
        if (!is_array($track) || array_is_list($track)) {
            throw new CourseMetadataException('Fullstack track manifest must return an associative array: fullstack.php');
        }
        foreach (['title', 'description', 'parts'] as $field) {
            if (!array_key_exists($field, $track)) {
                throw new CourseMetadataException("Fullstack track manifest is missing '{$field}': fullstack.php");
            }
        }
        if (!is_string($track['title']) || trim($track['title']) === '' || !is_string($track['description']) || trim($track['description']) === '') {
            throw new CourseMetadataException('Fullstack track manifest title and description must be non-empty strings: fullstack.php');
        }
        if (!is_array($track['parts']) || array_is_list($track['parts'])) {
            throw new CourseMetadataException('Fullstack track manifest parts must be keyed by part number: fullstack.php');
        }

        $partNumbers = array_map(static fn (string|int $part): int => (int) $part, array_keys($track['parts']));
        if ($partNumbers !== range(0, 12)) {
            throw new CourseMetadataException('Fullstack track manifest parts must be ordered 00 through 12 exactly: fullstack.php');
        }

        $catalog = array_column(CourseLoader::getLessons($root), null, 'id');
        $specs = BuildMilestone::all($root);
        $seen = [];
        foreach ($track['parts'] as $number => &$part) {
            if (!is_array($part) || array_is_list($part)) {
                throw new CourseMetadataException("Fullstack part {$number} must be an associative array: fullstack.php");
            }
            foreach (['title', 'purpose', 'lessons', 'milestones'] as $field) {
                if (!array_key_exists($field, $part)) {
                    throw new CourseMetadataException("Fullstack part {$number} is missing '{$field}': fullstack.php");
                }
            }
            if (!is_string($part['title']) || trim($part['title']) === '' || !is_string($part['purpose']) || trim($part['purpose']) === '') {
                throw new CourseMetadataException("Fullstack part {$number} title and purpose must be non-empty strings: fullstack.php");
            }
            if (!is_array($part['lessons']) || !array_is_list($part['lessons']) || !is_array($part['milestones']) || !array_is_list($part['milestones'])) {
                throw new CourseMetadataException("Fullstack part {$number} lessons and milestones must be lists: fullstack.php");
            }
            foreach ($part['milestones'] as $milestone) {
                if (!is_array($milestone) || !is_string($milestone['id'] ?? null) || !is_string($milestone['title'] ?? null)) {
                    throw new CourseMetadataException("Fullstack part {$number} has an invalid milestone: fullstack.php");
                }
                if (isset($milestone['route']) && (!is_string($milestone['route']) || !str_starts_with($milestone['route'], '/learn/fullstack/'))) {
                    throw new CourseMetadataException("Fullstack milestone '{$milestone['id']}' has an invalid route: fullstack.php");
                }
                // A specification with no route is unreachable; a route with no
                // specification is a 404 the learner finds instead of the author.
                // Both directions are checked so neither can drift silently.
                $hasSpec = isset($specs[$milestone['id']]);
                if ($hasSpec && ($milestone['route'] ?? null) !== BuildMilestone::routeFor($milestone['id'])) {
                    throw new CourseMetadataException(
                        "Fullstack milestone '{$milestone['id']}' has a specification at "
                        . ".dalt/course/build/{$specs[$milestone['id']]['id']}-{$specs[$milestone['id']]['slug']} but its manifest route is not '"
                        . BuildMilestone::routeFor($milestone['id']) . "': fullstack.php",
                    );
                }
                if (!$hasSpec && isset($milestone['route'])) {
                    throw new CourseMetadataException(
                        "Fullstack milestone '{$milestone['id']}' declares a route but has no specification. "
                        . "Add .dalt/course/build/{$milestone['id']}-<slug>/README.md or remove the route: fullstack.php",
                    );
                }
                if (isset($milestone['prerequisites']) && (!is_array($milestone['prerequisites']) || !array_is_list($milestone['prerequisites']))) {
                    throw new CourseMetadataException("Fullstack milestone '{$milestone['id']}' has invalid prerequisites: fullstack.php");
                }
                foreach ($milestone['prerequisites'] ?? [] as $lessonId) {
                    if (!is_string($lessonId) || !isset($catalog[$lessonId]) || $catalog[$lessonId]['section'] !== 'fullstack') {
                        throw new CourseMetadataException("Fullstack milestone '{$milestone['id']}' references unknown Fullstack lesson '{$lessonId}': fullstack.php");
                    }
                }
            }
            foreach ($part['lessons'] as $lessonId) {
                if (!is_string($lessonId) || !isset($catalog[$lessonId])) {
                    throw new CourseMetadataException("Fullstack part {$number} references unknown lesson '{$lessonId}': fullstack.php");
                }
                if ($catalog[$lessonId]['section'] !== 'fullstack') {
                    throw new CourseMetadataException("Fullstack part {$number} references non-Fullstack lesson '{$lessonId}': fullstack.php");
                }
                if (isset($seen[$lessonId])) {
                    throw new CourseMetadataException("Fullstack lesson '{$lessonId}' appears more than once: fullstack.php");
                }
                $seen[$lessonId] = true;
            }
        }
        unset($part);

        foreach ($catalog as $lesson) {
            if ($lesson['section'] === 'fullstack' && !isset($seen[$lesson['id']])) {
                throw new CourseMetadataException("Fullstack lesson '{$lesson['id']}' is missing from the track manifest: fullstack.php");
            }
        }

        return $track;
    }
}
