<?php

declare(strict_types=1);

use Core\CourseMetadataException;
use Core\ResourceCatalog;

function resourceCatalogFixture(array $catalog): string
{
    $root = sys_get_temp_dir() . '/dalt-resources-' . bin2hex(random_bytes(6));
    mkdir($root, 0700, true);
    file_put_contents(
        $root . '/resources.php',
        '<?php return ' . var_export($catalog, true) . ';',
    );

    return $root;
}

function removeResourceCatalogFixture(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    foreach (new FilesystemIterator($path) as $entry) {
        unlink($entry->getPathname());
    }
    rmdir($path);
}

/** @return array<string, mixed> */
function sampleCatalog(): array
{
    return [
        'postgresql' => [
            'title' => 'PostgreSQL',
            'blurb' => 'Blurb text.',
            'links' => [
                ['title' => 'Window functions', 'url' => 'https://example.com/window', 'read' => 'The whole section.', 'lessons' => ['13-postgres-advanced'], 'verified' => '2026-08-13'],
                ['title' => 'Indexes', 'url' => 'https://example.com/index', 'read' => 'One chapter.', 'lessons' => ['10-postgres-intermediate', '17-observability'], 'verified' => '2026-08-13'],
            ],
        ],
        'docker' => [
            'title' => 'Docker',
            'blurb' => null,
            'links' => [
                ['title' => 'Dockerfile basics', 'url' => 'https://example.com/dockerfile', 'read' => 'General guidelines.', 'lessons' => ['07-dockerfile'], 'verified' => '2026-08-13'],
            ],
        ],
    ];
}

test('categories loads the catalog verbatim from the course directory', function () {
    $root = resourceCatalogFixture(sampleCatalog());

    try {
        $categories = ResourceCatalog::categories($root);

        expect(array_keys($categories))->toBe(['postgresql', 'docker'])
            ->and($categories['postgresql']['title'])->toBe('PostgreSQL')
            ->and($categories['postgresql']['links'])->toHaveCount(2);
    } finally {
        removeResourceCatalogFixture($root);
    }
});

test('allLinks flattens every category and tags each link with its category', function () {
    $root = resourceCatalogFixture(sampleCatalog());

    try {
        $links = ResourceCatalog::allLinks($root);

        expect($links)->toHaveCount(3)
            ->and(array_column($links, 'category'))->toBe(['postgresql', 'postgresql', 'docker'])
            ->and(array_column($links, 'category_title'))->toBe(['PostgreSQL', 'PostgreSQL', 'Docker']);
    } finally {
        removeResourceCatalogFixture($root);
    }
});

test('forLesson returns only links tagged for that lesson, in catalog order', function () {
    $root = resourceCatalogFixture(sampleCatalog());

    try {
        $links = ResourceCatalog::forLesson('10-postgres-intermediate', $root);

        expect(array_column($links, 'title'))->toBe(['Indexes'])
            ->and(ResourceCatalog::forLesson('99-does-not-exist', $root))->toBe([]);
    } finally {
        removeResourceCatalogFixture($root);
    }
});

test('categoriesForLessons narrows links and drops categories left empty', function () {
    $root = resourceCatalogFixture(sampleCatalog());

    try {
        $narrowed = ResourceCatalog::categoriesForLessons(['07-dockerfile'], $root);

        expect(array_keys($narrowed))->toBe(['docker'])
            ->and($narrowed['docker']['links'])->toHaveCount(1)
            ->and($narrowed['docker']['links'][0]['title'])->toBe('Dockerfile basics');

        expect(ResourceCatalog::categoriesForLessons(['no-such-lesson'], $root))->toBe([]);
    } finally {
        removeResourceCatalogFixture($root);
    }
});

test('a missing catalog file is a clear metadata exception, not a silent empty result', function () {
    $root = sys_get_temp_dir() . '/dalt-resources-missing-' . bin2hex(random_bytes(6));
    mkdir($root, 0700, true);

    try {
        expect(fn () => ResourceCatalog::categories($root))->toThrow(CourseMetadataException::class);
    } finally {
        rmdir($root);
    }
});

test('the shipped catalog is valid and every tagged lesson id actually exists', function () {
    $categories = ResourceCatalog::categories();
    $lessonIds = array_column(Core\CourseLoader::getLessons(), 'id');

    expect($categories)->not->toBeEmpty();

    $unknown = [];
    foreach ($categories as $categoryId => $category) {
        expect($category['links'])->not->toBeEmpty();

        foreach ($category['links'] as $link) {
            expect($link['lessons'])->not->toBeEmpty();

            foreach ($link['lessons'] as $lessonId) {
                if (!in_array($lessonId, $lessonIds, true)) {
                    $unknown[] = "{$categoryId}/{$link['title']} -> {$lessonId}";
                }
            }
        }
    }

    expect($unknown)->toBe([]);
});
