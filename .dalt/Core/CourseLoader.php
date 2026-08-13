<?php

declare(strict_types=1);

namespace Core;

use FilesystemIterator;
use JsonException;

final class CourseLoader
{
    private const COURSE_PATH = '.dalt/course';

    private const COLORS = ['blue', 'green', 'purple', 'red', 'yellow', 'orange', 'gray'];
    private const DIFFICULTIES = ['Easy', 'Medium', 'Hard'];
    private const LESSON_FIELDS = ['title', 'description', 'order', 'section', 'section_order', 'icon', 'color', 'prerequisites'];
    private const CHALLENGE_FIELDS = ['title', 'description', 'order', 'difficulty', 'bugs', 'lesson', 'color'];
    private const SECTIONS = [
        'foundation' => ['id' => 'foundation', 'title' => 'Foundation', 'description' => 'Understand how backend requests and application fundamentals fit together.', 'display_order' => 1],
        'docker' => ['id' => 'docker', 'title' => 'Docker', 'description' => 'Package and run applications reliably.', 'display_order' => 2],
        'postgres' => ['id' => 'postgres', 'title' => 'PostgreSQL', 'description' => 'Learn to work confidently with PostgreSQL from first connection to advanced usage.', 'display_order' => 3],
        'operations' => ['id' => 'operations', 'title' => 'Operations', 'description' => 'Understand how to observe and operate a running backend system.', 'display_order' => 4],
    ];

    /** @return array<string, array{id: string, title: string, description: string, display_order: int}> */
    public static function getSections(): array
    {
        return self::SECTIONS;
    }

    /** @return list<array<string, mixed>> */
    public static function getLessons(?string $courseRoot = null): array
    {
        $lessons = [];

        foreach (self::itemDirectories(self::root($courseRoot), 'lessons') as $id => $directory) {
            $meta = self::loadMetadata($directory, "lessons/{$id}", self::LESSON_FIELDS);
            self::requireTextFields($meta, "lessons/{$id}", ['title', 'description', 'icon', 'color']);
            self::requirePositiveInteger($meta, "lessons/{$id}", 'order');
            self::requirePositiveInteger($meta, "lessons/{$id}", 'section_order');
            if (!is_string($meta['section'] ?? null) || !isset(self::SECTIONS[$meta['section']])) {
                self::invalid("lessons/{$id}", 'section', 'must name a supported learning section');
            }

            if (!Icon::supports($meta['icon'])) {
                self::invalid("lessons/{$id}", 'icon', 'must name a supported icon');
            }
            if (!in_array($meta['color'], self::COLORS, true)) {
                self::invalid("lessons/{$id}", 'color', 'must name a supported color');
            }

            $prerequisites = $meta['prerequisites'] ?? null;
            if (!is_array($prerequisites) || !array_is_list($prerequisites)) {
                self::invalid("lessons/{$id}", 'prerequisites', 'must be a JSON list of lesson IDs');
            }
            foreach ($prerequisites as $prerequisite) {
                if (!is_string($prerequisite) || !self::validId($prerequisite)) {
                    self::invalid("lessons/{$id}", 'prerequisites', 'must contain only lesson IDs');
                }
            }
            if (count($prerequisites) !== count(array_unique($prerequisites))) {
                self::invalid("lessons/{$id}", 'prerequisites', 'must not contain duplicates');
            }
            if (in_array($id, $prerequisites, true)) {
                self::invalid("lessons/{$id}", 'prerequisites', 'cannot contain the lesson itself');
            }

            $meta['id'] = $id;
            $lessons[] = $meta;
        }

        self::sortAndValidateOrder($lessons, 'lessons');
        self::validateSectionOrder($lessons);
        $byId = array_column($lessons, null, 'id');

        foreach ($lessons as $lesson) {
            foreach ($lesson['prerequisites'] as $prerequisite) {
                if (!isset($byId[$prerequisite])) {
                    self::invalid("lessons/{$lesson['id']}", 'prerequisites', "references unknown lesson \"{$prerequisite}\"");
                }
                if ($byId[$prerequisite]['order'] >= $lesson['order']) {
                    self::invalid("lessons/{$lesson['id']}", 'prerequisites', "lesson \"{$prerequisite}\" must occur earlier");
                }
            }
        }

        return $lessons;
    }

    /**
     * @param list<string>|null $passedChallenges
     * @return list<array<string, mixed>>
     */
    public static function getChallenges(?string $courseRoot = null, ?array $passedChallenges = null): array
    {
        $root = self::root($courseRoot);
        $lessons = self::getLessons($root);
        $lessonsById = array_column($lessons, null, 'id');
        $passedChallenges ??= ChallengeManager::getPassedChallenges();
        $challenges = [];

        foreach (self::itemDirectories($root, 'challenges') as $id => $directory) {
            $meta = self::loadMetadata($directory, "challenges/{$id}", self::CHALLENGE_FIELDS);
            self::requireTextFields($meta, "challenges/{$id}", ['title', 'description', 'difficulty', 'lesson', 'color']);
            self::requirePositiveInteger($meta, "challenges/{$id}", 'order');
            self::requirePositiveInteger($meta, "challenges/{$id}", 'bugs');

            if (!in_array($meta['difficulty'], self::DIFFICULTIES, true)) {
                self::invalid("challenges/{$id}", 'difficulty', 'must be Easy, Medium, or Hard');
            }
            if (!in_array($meta['color'], self::COLORS, true)) {
                self::invalid("challenges/{$id}", 'color', 'must name a supported color');
            }
            if (!self::validId($meta['lesson']) || !isset($lessonsById[$meta['lesson']])) {
                self::invalid("challenges/{$id}", 'lesson', "references unknown lesson \"{$meta['lesson']}\"");
            }

            $meta['id'] = $id;
            $meta['icon'] = $lessonsById[$meta['lesson']]['icon'];
            $meta['passed'] = in_array($id, $passedChallenges, true);
            $challenges[] = $meta;
        }

        self::sortAndValidateOrder($challenges, 'challenges');
        foreach ($challenges as $index => &$challenge) {
            $challenge['num'] = $index + 1;
        }
        unset($challenge);

        return $challenges;
    }

    /** @return array<string, mixed>|null */
    public static function getChallenge(
        string $id,
        ?string $courseRoot = null,
        ?array $passedChallenges = null,
    ): ?array {
        if (!self::validId($id)) {
            return null;
        }

        foreach (self::getChallenges($courseRoot, $passedChallenges) as $challenge) {
            if ($challenge['id'] === $id) {
                return $challenge;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public static function getLesson(string $id, ?string $courseRoot = null): ?array
    {
        if (!self::validId($id)) {
            return null;
        }

        $lessons = self::getLessons($courseRoot);
        foreach ($lessons as $lesson) {
            if ($lesson['id'] === $id) {
                $sectionLessons = array_values(array_filter(
                    $lessons,
                    static fn (array $candidate): bool => $candidate['section'] === $lesson['section'],
                ));
                usort($sectionLessons, static fn (array $left, array $right): int => [$left['section_order'], $left['id']] <=> [$right['section_order'], $right['id']]);
                $index = array_search($id, array_column($sectionLessons, 'id'), true);
                $lesson['prev'] = $index > 0 ? $sectionLessons[$index - 1]['id'] : null;
                $lesson['next'] = $index < count($sectionLessons) - 1 ? $sectionLessons[$index + 1]['id'] : null;

                return $lesson;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public static function getChallengesForLesson(string $lessonId, ?string $courseRoot = null): array
    {
        if (!self::validId($lessonId)) {
            return [];
        }

        return array_values(array_filter(
            self::getChallenges($courseRoot),
            static fn (array $challenge): bool => $challenge['lesson'] === $lessonId,
        ));
    }

    private static function root(?string $courseRoot): string
    {
        return rtrim($courseRoot ?? base_path(self::COURSE_PATH), '/\\');
    }

    /** @return array<string, string> */
    private static function itemDirectories(string $root, string $kind): array
    {
        $path = $root . DIRECTORY_SEPARATOR . $kind;
        if (!is_dir($path) || !is_readable($path)) {
            throw new CourseMetadataException("Course catalog directory is missing or unreadable: {$kind}");
        }

        $directories = [];
        foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $entry) {
            if (!$entry->isDir()) {
                continue;
            }
            if ($entry->isLink()) {
                throw new CourseMetadataException("Course catalog item cannot be a symbolic link: {$kind}/{$entry->getBasename()}");
            }

            $id = $entry->getBasename();
            if (!self::validId($id)) {
                throw new CourseMetadataException("Course catalog item has an invalid ID: {$kind}/{$id}");
            }
            $directories[$id] = $entry->getPathname();
        }
        ksort($directories, SORT_STRING);

        return $directories;
    }

    /**
     * @param list<string> $allowedFields
     * @return array<string, mixed>
     */
    private static function loadMetadata(string $directory, string $item, array $allowedFields): array
    {
        $readme = $directory . DIRECTORY_SEPARATOR . 'README.md';
        if (!is_file($readme) || !is_readable($readme)) {
            throw new CourseMetadataException("Course item is missing readable content: {$item}/README.md");
        }

        $path = $directory . DIRECTORY_SEPARATOR . 'meta.json';
        if (!is_file($path) || !is_readable($path)) {
            throw new CourseMetadataException("Course item is missing readable metadata: {$item}/meta.json");
        }

        try {
            $metadata = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new CourseMetadataException(
                "Course item metadata is invalid JSON: {$item}/meta.json ({$exception->getMessage()})",
                previous: $exception,
            );
        }
        if (!is_array($metadata) || array_is_list($metadata)) {
            throw new CourseMetadataException("Course item metadata must be a JSON object: {$item}/meta.json");
        }

        $unknown = array_values(array_diff(array_keys($metadata), $allowedFields));
        if ($unknown !== []) {
            throw new CourseMetadataException(
                "Course item metadata has unknown field(s): {$item}/meta.json (" . implode(', ', $unknown) . ')',
            );
        }
        $missing = array_values(array_diff($allowedFields, array_keys($metadata)));
        if ($missing !== []) {
            throw new CourseMetadataException(
                "Course item metadata is missing field(s): {$item}/meta.json (" . implode(', ', $missing) . ')',
            );
        }

        return $metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param list<string> $fields
     */
    private static function requireTextFields(array $metadata, string $item, array $fields): void
    {
        foreach ($fields as $field) {
            if (!is_string($metadata[$field] ?? null) || trim($metadata[$field]) === '') {
                self::invalid($item, $field, 'must be a non-empty string');
            }
        }
    }

    /** @param array<string, mixed> $metadata */
    private static function requirePositiveInteger(array $metadata, string $item, string $field): void
    {
        if (!is_int($metadata[$field] ?? null) || $metadata[$field] < 1) {
            self::invalid($item, $field, 'must be a positive integer');
        }
    }

    /** @param list<array<string, mixed>> $items */
    private static function sortAndValidateOrder(array &$items, string $kind): void
    {
        usort($items, static fn (array $left, array $right): int => [
            $left['order'],
            $left['id'],
        ] <=> [
            $right['order'],
            $right['id'],
        ]);

        $orders = [];
        foreach ($items as $item) {
            if (isset($orders[$item['order']])) {
                throw new CourseMetadataException(
                    "Course {$kind} have duplicate order {$item['order']}: {$orders[$item['order']]} and {$item['id']}",
                );
            }
            $orders[$item['order']] = $item['id'];
        }
    }

    /** @param list<array<string, mixed>> $lessons */
    private static function validateSectionOrder(array $lessons): void
    {
        $orders = [];
        foreach ($lessons as $lesson) {
            $section = $lesson['section'];
            $order = $lesson['section_order'];
            if (isset($orders[$section][$order])) {
                self::invalid("lessons/{$lesson['id']}", 'section_order', "duplicates {$orders[$section][$order]} in {$section}");
            }
            $orders[$section][$order] = $lesson['id'];
        }
    }

    private static function validId(string $id): bool
    {
        return preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $id) === 1;
    }

    private static function invalid(string $item, string $field, string $reason): never
    {
        throw new CourseMetadataException("Invalid course metadata at {$item}/meta.json: {$field} {$reason}.");
    }
}
