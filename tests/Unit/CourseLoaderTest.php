<?php

declare(strict_types=1);

use Core\CourseLoader;
use Core\CourseMetadataException;

function courseFixture(): string
{
    $root = sys_get_temp_dir() . '/dalt-p02-course-' . bin2hex(random_bytes(6));
    mkdir($root . '/lessons', 0700, true);
    mkdir($root . '/challenges', 0700, true);

    return $root;
}

function removeCourseFixture(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }

    if (is_dir($path) && !is_link($path)) {
        foreach (new FilesystemIterator($path) as $entry) {
            removeCourseFixture($entry->getPathname());
        }
        rmdir($path);
        return;
    }

    unlink($path);
}

/** @param array<string, mixed> $metadata */
function addCourseItem(string $root, string $kind, string $id, array $metadata): void
{
    $directory = "{$root}/{$kind}/{$id}";
    mkdir($directory, 0700, true);
    file_put_contents($directory . '/meta.json', json_encode($metadata, JSON_THROW_ON_ERROR));
    file_put_contents($directory . '/README.md', "# {$id}\n");
}

/** @return array<string, mixed> */
function lessonMetadata(int $order, array $overrides = []): array
{
    return array_replace([
        'title' => "Lesson {$order}",
        'description' => 'A complete lesson description.',
        'order' => $order,
        'icon' => 'routing',
        'color' => 'blue',
        'prerequisites' => [],
    ], $overrides);
}

/** @return array<string, mixed> */
function challengeMetadata(int $order, string $lesson, array $overrides = []): array
{
    return array_replace([
        'title' => "Challenge {$order}",
        'description' => 'A complete challenge description.',
        'order' => $order,
        'difficulty' => 'Easy',
        'bugs' => 1,
        'lesson' => $lesson,
        'color' => 'blue',
    ], $overrides);
}

test('course items use explicit order and expose stable navigation and relationships', function () {
    $root = courseFixture();

    try {
        addCourseItem($root, 'lessons', 'second', lessonMetadata(20, ['prerequisites' => ['first']]));
        addCourseItem($root, 'lessons', 'first', lessonMetadata(10));
        addCourseItem($root, 'challenges', 'later-challenge', challengeMetadata(20, 'second'));
        addCourseItem($root, 'challenges', 'first-challenge', challengeMetadata(10, 'first'));

        $lessons = CourseLoader::getLessons($root);
        $challenges = CourseLoader::getChallenges($root, ['later-challenge']);
        $second = CourseLoader::getLesson('second', $root);

        expect(array_column($lessons, 'id'))->toBe(['first', 'second'])
            ->and(array_column($challenges, 'id'))->toBe(['first-challenge', 'later-challenge'])
            ->and(array_column($challenges, 'num'))->toBe([1, 2])
            ->and(array_column($challenges, 'passed'))->toBe([false, true])
            ->and($second['prev'])->toBe('first')
            ->and($second['next'])->toBeNull()
            ->and($second['prerequisites'])->toBe(['first'])
            ->and(array_column(CourseLoader::getChallengesForLesson('second', $root), 'id'))
            ->toBe(['later-challenge']);
    } finally {
        removeCourseFixture($root);
    }
});

test('challenge icons come from their related lesson taxonomy', function () {
    $root = courseFixture();

    try {
        addCourseItem($root, 'lessons', 'containers', lessonMetadata(1, ['icon' => 'docker']));
        addCourseItem($root, 'challenges', 'container-fix', challengeMetadata(1, 'containers'));

        $lesson = CourseLoader::getLesson('containers', $root);
        $challenge = CourseLoader::getChallenge('container-fix', $root, []);

        expect($lesson['icon'])->toContain('<svg')
            ->and($challenge['icon'])->toBe($lesson['icon']);
    } finally {
        removeCourseFixture($root);
    }
});

test('missing malformed and incomplete metadata fail with an actionable path', function (string $case) {
    $root = courseFixture();
    $directory = $root . '/lessons/example';
    mkdir($directory, 0700, true);
    file_put_contents($directory . '/README.md', '# Example');

    if ($case === 'malformed') {
        file_put_contents($directory . '/meta.json', '{nope');
    } elseif ($case === 'incomplete') {
        file_put_contents($directory . '/meta.json', json_encode(['title' => 'Example'], JSON_THROW_ON_ERROR));
    }

    try {
        CourseLoader::getLessons($root);
    } finally {
        removeCourseFixture($root);
    }
})->with(['missing', 'malformed', 'incomplete'])
    ->throws(CourseMetadataException::class, 'lessons/example/meta.json');

test('catalog validation rejects duplicate order unknown icons and invalid prerequisite order', function (string $case) {
    $root = courseFixture();

    if ($case === 'duplicate order') {
        addCourseItem($root, 'lessons', 'first', lessonMetadata(1));
        addCourseItem($root, 'lessons', 'second', lessonMetadata(1));
    } elseif ($case === 'unknown icon') {
        addCourseItem($root, 'lessons', 'first', lessonMetadata(1, ['icon' => 'mystery']));
    } else {
        addCourseItem($root, 'lessons', 'first', lessonMetadata(1, ['prerequisites' => ['second']]));
        addCourseItem($root, 'lessons', 'second', lessonMetadata(2));
    }

    try {
        CourseLoader::getLessons($root);
    } finally {
        removeCourseFixture($root);
    }
})->with(['duplicate order', 'unknown icon', 'invalid prerequisite order'])
    ->throws(CourseMetadataException::class);

test('challenges must link to a discovered lesson and direct lookup cannot traverse paths', function () {
    $root = courseFixture();

    try {
        addCourseItem($root, 'lessons', 'first', lessonMetadata(1));
        addCourseItem($root, 'challenges', 'orphan', challengeMetadata(1, 'missing'));

        expect(fn () => CourseLoader::getChallenges($root, []))
            ->toThrow(CourseMetadataException::class, 'unknown lesson "missing"')
            ->and(CourseLoader::getLesson('../first', $root))->toBeNull()
            ->and(CourseLoader::getChallenge('../orphan', $root, []))->toBeNull();
    } finally {
        removeCourseFixture($root);
    }
});

test('the shipped course is complete and its full inventory is deterministic', function () {
    $lessons = CourseLoader::getLessons();
    $challenges = CourseLoader::getChallenges();

    expect($lessons)->toHaveCount(17)
        ->and($challenges)->toHaveCount(20)
        ->and(array_column($lessons, 'order'))->toBe(range(1, 17))
        ->and(array_column($challenges, 'order'))->toBe(range(1, 20));
});
