<?php

declare(strict_types=1);

namespace Core;

use JsonException;

/** Small project-local learning state service. */
final class ProgressManager
{
    private const FILE = '.dalt/progress.json';
    private const LOCK = '.dalt/progress.lock';

    /** @return array{passed: list<string>, completed_lessons: list<string>, completed_milestones: list<string>, last_visited_lesson: ?string} */
    public static function state(): array
    {
        $path = base_path(self::FILE);
        if (!file_exists($path)) {
            return ['passed' => [], 'completed_lessons' => [], 'completed_milestones' => [], 'last_visited_lesson' => null];
        }

        if (!is_file($path) || is_link($path)) {
            throw new ChallengeStateException('The learning progress file must be a regular file.');
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ChallengeStateException('The learning progress file is malformed JSON.', 0, $exception);
        }

        if (!is_array($data) || array_is_list($data)) {
            throw new ChallengeStateException('The learning progress file must be a JSON object.');
        }

        return [
            'passed' => self::ids($data['passed'] ?? [], 'passed challenge'),
            // Optional new keys deliberately degrade to an empty/null value so an
            // old or partially edited progress file remains usable.
            'completed_lessons' => self::optionalIds($data['completed_lessons'] ?? []),
            'completed_milestones' => self::optionalMilestoneIds($data['completed_milestones'] ?? []),
            'last_visited_lesson' => self::validId($data['last_visited_lesson'] ?? null)
                ? $data['last_visited_lesson']
                : null,
        ];
    }

    /** @return list<string> */
    public static function getPassedChallenges(): array
    {
        return self::state()['passed'];
    }

    /** @return array<string, true> */
    public static function verifiedLessonIds(array $challenges): array
    {
        $passed = array_flip(self::getPassedChallenges());
        $verified = [];
        foreach ($challenges as $challenge) {
            if (isset($passed[$challenge['id']])) {
                $verified[$challenge['lesson']] = true;
            }
        }
        return $verified;
    }

    /** @return array<string, true> */
    public static function completedLessonIds(array $challenges): array
    {
        $state = self::state();
        $completed = array_fill_keys($state['completed_lessons'], true);
        foreach (self::verifiedLessonIds($challenges) as $lessonId => $_) {
            $completed[$lessonId] = true;
        }
        return $completed;
    }

    public static function lastVisitedLesson(): ?string
    {
        return self::state()['last_visited_lesson'];
    }

    public static function visitLesson(string $lessonId): void
    {
        self::assertLesson($lessonId);
        self::update(static function (array $state) use ($lessonId): array {
            $state['last_visited_lesson'] = $lessonId;
            return $state;
        });
    }

    public static function markLessonCompleted(string $lessonId): void
    {
        self::assertLesson($lessonId);
        self::update(static function (array $state) use ($lessonId): array {
            if (!in_array($lessonId, $state['completed_lessons'], true)) {
                $state['completed_lessons'][] = $lessonId;
            }
            return $state;
        });
    }

    public static function markLessonIncomplete(string $lessonId): void
    {
        self::assertLesson($lessonId);
        self::update(static function (array $state) use ($lessonId): array {
            $state['completed_lessons'] = array_values(array_filter(
                $state['completed_lessons'],
                static fn (string $id): bool => $id !== $lessonId,
            ));
            return $state;
        });
    }

    /** @return array<string, true> */
    public static function completedMilestoneIds(): array
    {
        return array_fill_keys(self::state()['completed_milestones'], true);
    }

    public static function markMilestoneCompleted(string $milestoneId): void
    {
        if (!self::validMilestoneId($milestoneId)) {
            throw new ChallengeStateException("Invalid milestone ID '{$milestoneId}'.");
        }
        self::update(static function (array $state) use ($milestoneId): array {
            if (!in_array($milestoneId, $state['completed_milestones'], true)) {
                $state['completed_milestones'][] = $milestoneId;
            }
            return $state;
        });
    }

    public static function markChallengePassed(string $challengeId): void
    {
        if (!self::validId($challengeId)) {
            throw new ChallengeStateException("Invalid challenge ID '{$challengeId}'.");
        }
        // ChallengeManager is also used by isolated transaction fixtures that do
        // not carry a complete course catalog. Preserve its historic capability
        // to record a valid challenge ID there; the real verifier has already
        // validated its challenge against CourseLoader.
        try {
            $challenge = CourseLoader::getChallenge($challengeId, passedChallenges: []);
        } catch (CourseMetadataException) {
            $challenge = null;
        }
        self::update(static function (array $state) use ($challengeId, $challenge): array {
            if (!in_array($challengeId, $state['passed'], true)) {
                $state['passed'][] = $challengeId;
            }
            if ($challenge !== null && !in_array($challenge['lesson'], $state['completed_lessons'], true)) {
                $state['completed_lessons'][] = $challenge['lesson'];
            }
            return $state;
        });
    }

    /** @return array<string, mixed>|null */
    public static function continuation(array $lessons, array $completedLessonIds): ?array
    {
        $byId = array_column($lessons, null, 'id');
        $lastId = self::lastVisitedLesson();
        if ($lastId !== null && isset($byId[$lastId])) {
            $last = $byId[$lastId];
            if (!isset($completedLessonIds[$lastId])) {
                return $last;
            }
            $sectionLessons = array_values(array_filter($lessons, static fn (array $lesson): bool => $lesson['section'] === $last['section']));
            usort($sectionLessons, static fn (array $a, array $b): int => $a['section_order'] <=> $b['section_order']);
            foreach ($sectionLessons as $lesson) {
                if ($lesson['section_order'] > $last['section_order'] && !isset($completedLessonIds[$lesson['id']])) {
                    return $lesson;
                }
            }
        }
        foreach ($lessons as $lesson) {
            if (!isset($completedLessonIds[$lesson['id']])) {
                return $lesson;
            }
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public static function nextInSection(array $lesson, array $lessons, array $completedLessonIds): ?array
    {
        foreach ($lessons as $candidate) {
            if ($candidate['section'] === $lesson['section']
                && $candidate['section_order'] > $lesson['section_order']
                && !isset($completedLessonIds[$candidate['id']])) {
                return $candidate;
            }
        }
        return null;
    }

    private static function assertLesson(string $lessonId): void
    {
        if (CourseLoader::getLesson($lessonId) === null) {
            throw new ChallengeStateException("Invalid lesson ID '{$lessonId}'.");
        }
    }

    /** @param callable(array{passed: list<string>, completed_lessons: list<string>, completed_milestones: list<string>, last_visited_lesson: ?string}): array{passed: list<string>, completed_lessons: list<string>, completed_milestones: list<string>, last_visited_lesson: ?string} $mutation */
    private static function update(callable $mutation): void
    {
        $lock = fopen(base_path(self::LOCK), 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new ChallengeStateException('Unable to lock learning progress.');
        }
        try {
            $state = $mutation(self::state());
            $path = base_path(self::FILE);
            $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
            file_put_contents($temporary, json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n", LOCK_EX);
            if (!rename($temporary, $path)) {
                @unlink($temporary);
                throw new ChallengeStateException('Unable to save learning progress.');
            }
        } catch (JsonException $exception) {
            throw new ChallengeStateException('Unable to encode learning progress.', 0, $exception);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return list<string> */
    private static function ids(mixed $value, string $label): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new ChallengeStateException("The learning progress file contains an invalid {$label} list.");
        }
        $ids = self::optionalIds($value);
        if (count($ids) !== count($value)) {
            throw new ChallengeStateException("The learning progress file contains an invalid {$label} ID.");
        }
        return $ids;
    }

    /** @return list<string> */
    private static function optionalIds(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            return [];
        }
        $ids = [];
        foreach ($value as $id) {
            if (self::validId($id) && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    private static function validId(mixed $id): bool
    {
        return is_string($id) && preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $id) === 1;
    }

    /** @return list<string> */
    private static function optionalMilestoneIds(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            return [];
        }
        return array_values(array_unique(array_filter($value, static fn (mixed $id): bool => self::validMilestoneId($id))));
    }

    private static function validMilestoneId(mixed $id): bool
    {
        return is_string($id) && preg_match('/\A[BC][0-9]{2}\z/D', $id) === 1;
    }
}
