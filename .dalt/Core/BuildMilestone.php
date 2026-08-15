<?php

declare(strict_types=1);

namespace Core;

/**
 * Build milestone specifications.
 *
 * A milestone is a directory under `.dalt/course/build/` named `<ID>-<slug>`,
 * holding a `README.md` the learner reads and, optionally, a `starter/` workspace
 * they copy. The README is course-owned trusted Markdown, rendered through the
 * same pipeline as a lesson.
 *
 * This is IMPLEMENTATION_PLAN.md 4.3's original design. It replaced four bespoke
 * controller/view pairs — one per milestone, each with its own hand-written HTML
 * and its own copy of a draft-saving script — which were on a trajectory to
 * nineteen. A milestone is content, not an application.
 *
 * What deliberately does not exist here: no learner input is collected, stored or
 * graded. Completion is a single self-reported flag on ProgressManager. See
 * IMPLEMENTATION_PLAN.md 4.8 — the honesty of the label is the mitigation.
 */
final class BuildMilestone
{
    private const ID_PATTERN = '/\A([BC][0-9]{2})-([a-z0-9]+(?:-[a-z0-9]+)*)\z/D';

    /**
     * Every milestone that has a written specification, keyed by ID.
     *
     * @return array<string, array{id: string, slug: string, path: string, has_starter: bool}>
     */
    public static function all(?string $courseRoot = null): array
    {
        $root = rtrim($courseRoot ?? base_path('.dalt/course'), '/\\') . '/build';
        if (!is_dir($root)) {
            return [];
        }

        $milestones = [];
        foreach (new \FilesystemIterator($root, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if (!$entry->isDir() || $entry->isLink()) {
                continue;
            }
            $basename = $entry->getFilename();
            if (preg_match(self::ID_PATTERN, $basename, $match) !== 1) {
                throw new CourseMetadataException(
                    "Build milestone directory '{$basename}' must be named <ID>-<slug>, "
                    . "for example 'B00-trace-the-system': .dalt/course/build",
                );
            }
            $readme = $entry->getPathname() . '/README.md';
            if (!is_file($readme)) {
                throw new CourseMetadataException(
                    "Build milestone '{$match[1]}' has no README.md. A milestone without a "
                    . "specification cannot be opened by the learner: .dalt/course/build/{$basename}",
                );
            }
            if (isset($milestones[$match[1]])) {
                throw new CourseMetadataException(
                    "Build milestone '{$match[1]}' is declared by more than one directory: .dalt/course/build",
                );
            }
            $milestones[$match[1]] = [
                'id' => $match[1],
                'slug' => $match[2],
                'path' => $entry->getPathname(),
                'has_starter' => is_dir($entry->getPathname() . '/starter'),
            ];
        }

        ksort($milestones);

        return $milestones;
    }

    /** @return array{id: string, slug: string, path: string, has_starter: bool}|null */
    public static function find(string $id, ?string $courseRoot = null): ?array
    {
        return self::all($courseRoot)[strtoupper($id)] ?? null;
    }

    /** The learner-facing route for a milestone ID. Derived, never stored twice. */
    public static function routeFor(string $id): string
    {
        return '/learn/fullstack/build/' . strtolower($id);
    }

    /**
     * The specification Markdown.
     *
     * Only README.md is ever read. A milestone directory may also hold `starter/`
     * and, for course-authoring purposes, `reference/` snapshots — EXERCISE_STANDARD.md
     * 55 requires those stay unreachable from learner navigation, and reading one
     * fixed filename is what keeps that true.
     */
    public static function specification(string $id, ?string $courseRoot = null): string
    {
        $milestone = self::find($id, $courseRoot);
        if ($milestone === null) {
            throw new CourseMetadataException("Unknown build milestone '{$id}'.");
        }
        $content = file_get_contents($milestone['path'] . '/README.md');
        if ($content === false) {
            throw new CourseMetadataException("Build milestone '{$id}' specification could not be read.");
        }

        return $content;
    }
}
