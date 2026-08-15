<?php

declare(strict_types=1);

use Core\BuildMilestone;
use Core\CourseLoader;
use Core\FullstackTrack;

/**
 * The authoring standard, enforced.
 *
 * LESSON_STANDARD.md 97 and .dalt/course/build/README.md describe what a Fullstack
 * lesson and a Build milestone must contain. Those documents were followed exactly
 * for Part 00, loosely for Part 01, and barely at all for Parts 02 and 03 — nine of
 * thirteen lessons shipped with no Maintainer source record, and Part 03 shipped at
 * half the depth of the material before it. Nothing noticed, because nothing checked.
 *
 * This file is the check. A standard that lives only in a document degrades across
 * thirty-two lessons; a standard that fails the suite does not.
 *
 * Every assertion here is mechanical — presence, length, shape. None of it can judge
 * whether a lesson is any good. That is what the audit checkpoints in PIPELINE.md are
 * for. This only guarantees that nothing was silently skipped.
 */

/** @return list<string> */
function fullstackLessonIds(): array
{
    return array_values(array_map(
        static fn (array $lesson): string => $lesson['id'],
        array_filter(CourseLoader::getLessons(), static fn (array $lesson): bool => $lesson['section'] === 'fullstack'),
    ));
}

function fullstackLessonBody(string $id): string
{
    return (string) file_get_contents(base_path(".dalt/course/lessons/{$id}/README.md"));
}

/**
 * The largest group of template-identical lines in the lesson, where "template-identical"
 * means equal once trailing integers are stripped.
 *
 * `// state-boundary review 1` and `// state-boundary review 1350` collapse to the same
 * template. Genuine writing repeats short lines — a closing brace, a shell command — but
 * does not repeat the same *statement* dozens of times with only a counter changing. That
 * shape is generated filler, and it is worth one word per line to a counter that cannot
 * read.
 *
 * Counted over the whole document rather than per fenced block, because the padding is
 * only worth anything to the measure while it sits somewhere the measure counts — and
 * both sides of the fence are counted. Restricting this to code blocks would leave the
 * obvious evasion of moving the same lines into prose, where they score higher still, at
 * four words a line instead of one.
 *
 * @return array{count: int, template: string}
 */
function fullstackLargestTemplateRun(string $id): array
{
    $worst = ['count' => 0, 'template' => ''];
    $groups = [];

    foreach (explode("\n", fullstackLessonBody($id)) as $line) {
        $trimmed = trim($line);

        // Structural markup, not content. Sixteen lessons open six <details> hints each,
        // which is good practice and must not read as repetition.
        if ($trimmed === '' || preg_match('#^</?(details|summary|div|p|br)\b[^>]*>$#i', $trimmed) === 1) {
            continue;
        }
        if (preg_match('/^\s*(```|~~~)/', $line) === 1) {
            continue;
        }

        // Strip trailing integers, then require what remains to still say something.
        // Without this, a column of bare numbers in a table would read as padding.
        $template = rtrim(preg_replace('/\d+\s*$/', '', $trimmed) ?? '');
        if (strlen($template) < 8) {
            continue;
        }

        $groups[$template] = ($groups[$template] ?? 0) + 1;
        if ($groups[$template] > $worst['count']) {
            $worst = ['count' => $groups[$template], 'template' => $template];
        }
    }

    return $worst;
}

/**
 * Section bodies, keyed by their `## ` heading.
 *
 * @return array<string, string>
 */
function fullstackSections(string $id): array
{
    $sections = [];
    $heading = null;
    $buffer = [];

    foreach (explode("\n", fullstackLessonBody($id)) as $line) {
        if (preg_match('/^## (.+)$/', $line, $match) === 1) {
            if ($heading !== null) {
                $sections[$heading] = trim(implode("\n", $buffer));
            }
            $heading = '## ' . trim($match[1]);
            $buffer = [];

            continue;
        }
        $buffer[] = $line;
    }
    if ($heading !== null) {
        $sections[$heading] = trim(implode("\n", $buffer));
    }

    return $sections;
}

/**
 * How much a lesson actually teaches: prose words outside code fences, plus lines
 * of code inside them.
 *
 * Two decisions are load-bearing here.
 *
 * Prose is counted with the fences removed rather than by running strip_tags() over
 * the whole file. strip_tags eats everything from `<?php` to the next `>`, so a
 * lesson with several PHP blocks silently loses hundreds of words and scores as a
 * stub — penalising exactly the lessons that show the most code. That is how a
 * 2,500-word Part 05 lesson measured 1,209.
 *
 * Code lines then count as content, at one line per word. A line of code carries
 * more than a word of prose, so this undercounts it on purpose: the measure should
 * never reward padding a lesson with generated code.
 */
function fullstackLessonWords(string $id): int
{
    $prose = [];
    $codeLines = 0;
    $inside = false;

    foreach (explode("\n", fullstackLessonBody($id)) as $line) {
        if (preg_match('/^\s*(```|~~~)/', $line) === 1) {
            $inside = !$inside;

            continue;
        }
        if ($inside) {
            $codeLines++;
        } else {
            $prose[] = $line;
        }
    }

    return str_word_count(strip_tags(implode("\n", $prose))) + $codeLines;
}

/** The two-digit part number encoded in a lesson id, e.g. '33-fs04-1-...' gives '04'. */
function fullstackPartOf(string $id): string
{
    preg_match('/-fs([0-9]{2})-/', $id, $match);

    return $match[1] ?? '';
}

/** @return list<string> Part numbers present on disk, ascending. */
function fullstackParts(): array
{
    $parts = array_unique(array_map(fullstackPartOf(...), fullstackLessonIds()));
    sort($parts);

    return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
}

/** @return list<string> */
function fullstackPartsBefore(string $part): array
{
    $parts = fullstackParts();
    $index = array_search($part, $parts, true);

    return $index === false ? [] : array_slice($parts, 0, (int) $index);
}

/** @return list<string> */
function fullstackLessonsIn(string $part): array
{
    return array_values(array_filter(
        fullstackLessonIds(),
        static fn (string $id): bool => fullstackPartOf($id) === $part,
    ));
}

function fullstackPartMinimumWords(string $part): int
{
    return min(array_map(fullstackLessonWords(...), fullstackLessonsIn($part)));
}

/** @return array{id: string, words: int} */
function fullstackThinnestLesson(string $part): array
{
    $counts = [];
    foreach (fullstackLessonsIn($part) as $id) {
        $counts[$id] = fullstackLessonWords($id);
    }
    $id = (string) array_search(min($counts), $counts, true);

    return ['id' => $id, 'words' => $counts[$id]];
}

/**
 * Fenced blocks and the lines inside them. Both fence styles are in use across the
 * track, so counting only one silently reports zero.
 *
 * @return array{blocks: int, lines: int}
 */
function fullstackCodeDensity(string $id): array
{
    $blocks = 0;
    $lines = 0;
    $inside = false;

    foreach (explode("\n", fullstackLessonBody($id)) as $line) {
        if (preg_match('/^\s*(```|~~~)/', $line) === 1) {
            $inside = !$inside;
            if ($inside) {
                $blocks++;
            }

            continue;
        }
        if ($inside) {
            $lines++;
        }
    }

    return ['blocks' => $blocks, 'lines' => $lines];
}

dataset('fullstack lessons', array_map(static fn (string $id): array => [$id], fullstackLessonIds()));
dataset('fullstack parts', array_map(static fn (string $part): array => [$part], fullstackParts()));
dataset('build milestones', array_map(static fn (string $id): array => [$id], array_keys(BuildMilestone::all())));

const FULLSTACK_LESSON_SECTIONS = [
    '## Why this matters',
    '## Before you start',
    '## By the end',
    '## Predict before reading',
    '## Mental model',
    '## Try it',
    '## Common mistakes',
    '## When this goes wrong',
    '## In the project',
    '## Closed-book checkpoint',
    '## Resources',
    '## You are done when',
    '## Maintainer source record',
];

// '## Exercise' is deliberately NOT enforced here despite being in LESSON_STANDARD.md
// 97's template. Lessons 20-28 (Parts 00-02) predate the literal-heading convention and
// use differently-titled, differently-shaped exercise sections instead — FS00.1's
// "## Inspect real evidence" / "## Try it" pair, and FS00.2/FS01.1-FS02.5's
// "## Two ways to submit" / "## Focused exercise — <name>" headings, which already carry
// a real Goal/Requirements/Verification/Hints-equivalent structure under a descriptive
// title. Renaming nine shipped lessons' headings for a mechanical presence check is a
// content-standardization decision for whoever owns the Part 00-02 curve, not a defect
// this test should paper over by adding the check and immediately failing on it. See
// WORKLOG.md F29.

test('the lesson contains every mandatory section', function (string $id) {
    $body = fullstackLessonBody($id);

    foreach (FULLSTACK_LESSON_SECTIONS as $heading) {
        expect(str_contains($body, $heading . "\n"))->toBeTrue(
            "{$id} is missing '{$heading}'. LESSON_STANDARD.md 97 lists it as mandatory. "
            . 'If a lesson genuinely does not need it, amend the standard and this list together — '
            . 'do not skip it silently, which is how nine lessons lost their source record.',
        );
    }
})->with('fullstack lessons');

test('every mandatory section says something', function (string $id) {
    // Presence was checked above; this checks content. All three Part 08 lessons shipped
    // `## Common mistakes` followed immediately by the next heading, and passed, because
    // the presence check reads the heading and stops. An empty mandatory section is worse
    // than a missing one: it looks answered.
    $sections = fullstackSections($id);

    foreach (FULLSTACK_LESSON_SECTIONS as $heading) {
        $content = $sections[$heading] ?? '';

        expect(strlen($content))->toBeGreaterThan(
            40,
            "{$id} has '{$heading}' as an empty or near-empty heading (" . strlen($content) . ' characters). '
            . 'A section that exists but says nothing passes the presence check and teaches nobody. '
            . 'Write it, or amend the standard and FULLSTACK_LESSON_SECTIONS together.',
        );
    }
})->with('fullstack lessons');

test('the lesson is not padded with generated filler', function (string $id) {
    // Part 08 was written 1,350 lines over the depth floor by `// state-boundary review N`
    // repeated from 1 to 1350 inside a fenced block, worth one word per line to a counter
    // that cannot read. It was hidden inside a <details> block captioned "Internal
    // repeated markers (not learner material)", so an early version of this check simply
    // stopped counting <details>. That was wrong: <details> is the track's mechanism for
    // progressive hints and "Reference explanation — read after an honest attempt", used
    // legitimately by sixteen lessons, and not counting it would have penalised the
    // best-structured material to catch three bad ones. Detect the filler instead; where
    // it is hidden does not matter.
    //
    // Ten is chosen against the corpus, not invented: the deepest genuine repetition in
    // any passing lesson is four (FS06.3 repeating a signIn() call across cases).
    ['count' => $count, 'template' => $template] = fullstackLargestTemplateRun($id);

    expect($count)->toBeLessThan(
        10,
        "{$id} repeats the line '{$template} N' {$count} times. "
        . 'That is generated filler: it costs nothing to produce, it satisfies the depth '
        . 'measure at one word per line, and it teaches nothing. Show the work once and '
        . 'write the material the repetition was standing in for.',
    );
})->with('fullstack lessons');

test('the lesson is not another lesson with a new title', function (string $id) {
    // FS07.3 "Test frontend behavior" shipped as FS07.2 "Authentication in the frontend"
    // with a different heading: fourteen of seventeen sections byte-identical, including
    // its By the end objectives and an Exercise whose goal was to build authentication.
    // It contained no test code. Every other check in this file passed, because every
    // other check reads one lesson at a time.
    //
    // Exact identity is the whole rule — no similarity threshold, no tuning. Across the
    // thirty lessons on disk the only sections that collide at all are this one pair, so
    // anything looser would be inventing a problem the corpus does not have.
    static $index = null;
    if ($index === null) {
        $index = [];
        foreach (fullstackLessonIds() as $other) {
            foreach (fullstackSections($other) as $heading => $content) {
                if ($content !== '') {
                    $index[$heading][$content][] = $other;
                }
            }
        }
    }

    foreach (FULLSTACK_LESSON_SECTIONS as $heading) {
        $content = fullstackSections($id)[$heading] ?? '';
        if ($content === '') {
            continue;   // Emptiness is the previous test's finding, not this one's.
        }

        $sharedWith = array_values(array_diff($index[$heading][$content] ?? [], [$id]));

        expect($sharedWith)->toBe(
            [],
            "{$id} has a '{$heading}' section byte-identical to " . implode(', ', $sharedWith) . '. '
            . 'Two lessons teaching the same material under different titles means one of them '
            . 'was never written. Give this lesson the section its own subject needs.',
        );
    }
})->with('fullstack lessons');

test('every package the lesson installs names an exact version', function (string $id) {
    // FS07.1 told the learner to run `npm install react-router` while claiming to use
    // "the version selected for that project" — a version no document in the repository
    // ever stated. It resolves to the 8.x line, which requires React >= 19.2.7 against a
    // CR-08 pin of 19.2.3, so the first command of Part 07 failed with ERESOLVE before
    // the learner had written anything. An unpinned install is also a lesson that stops
    // describing the learner's project the day upstream publishes.
    //
    // Bare `npm install`, which installs what package.json already pins, is the point of
    // a lockfile and is always fine.
    // [ \t] rather than \s: \s matches a newline, so a bare `npm install` on its own line
    // swallowed the line after it and reported the next command as a package name.
    preg_match_all('/^[ \t]*npm (?:install|i|add)[ \t]+([^\n]+)$/m', fullstackLessonBody($id), $matches);

    if ($matches[1] === []) {
        expect(true)->toBeTrue();   // Most lessons install nothing; that is not a finding.

        return;
    }

    foreach ($matches[1] as $arguments) {
        foreach (preg_split('/\s+/', trim($arguments)) ?: [] as $token) {
            if ($token === '' || str_starts_with($token, '-')) {
                continue;
            }

            // A scoped package keeps one leading @, so look for a second separator.
            $hasVersion = str_contains(ltrim($token, '@'), '@');

            expect($hasVersion)->toBeTrue(
                "{$id} runs 'npm install {$token}' without a version. Pin it as "
                . "'{$token}@<exact>' and say why that version. Unpinned, the learner gets "
                . 'whatever npm published this morning, which is how Part 07 shipped a first '
                . 'command that could not resolve against the pinned React.',
            );
        }
    }
})->with('fullstack lessons');

test('the lesson records its provenance', function (string $id) {
    $body = fullstackLessonBody($id);
    $record = substr($body, (int) strpos($body, '## Maintainer source record'));

    foreach (['Source dossier:', 'Official sources:', 'Versions:', 'Consulted:', 'Curriculum authority:'] as $field) {
        expect(str_contains($record, $field))->toBeTrue(
            "{$id}'s Maintainer source record has no '{$field}' line. SOURCE_POLICY.md requires the "
            . 'provenance trail; a lesson without it cannot be audited or re-verified later.',
        );
    }
})->with('fullstack lessons');

test('the lesson states its exercise verification mode', function (string $id) {
    $body = fullstackLessonBody($id);

    // EXERCISE_STANDARD.md 17, as amended by Amendment B: an exercise says how it is
    // proven and never presents self-report as stronger than it is.
    expect(preg_match('/\*\*Mode:.*?\*\*/s', $body))->toBe(
        1,
        "{$id} has no '**Mode: ...**' declaration on its exercise. Amendment B allows manual and "
        . 'self-reported evidence precisely on condition that the lesson says which is in use.',
    );
})->with('fullstack lessons');

test('the lesson is deep enough to be worth its place', function (string $id) {
    $words = fullstackLessonWords($id);

    // An absolute floor for Part 00, which has no part before it to compare against.
    // Everything after Part 00 is governed by the regression rule below, which is the
    // rule AGENTS.md 3 actually states.
    expect($words)->toBeGreaterThan(
        1200,
        "{$id} is {$words} words. That is stub territory.",
    );
})->with('fullstack lessons');

test('the part does not regress in depth against the part before it', function (string $part) {
    // AGENTS.md 3: "A lesson on harder material may not be shorter than the lessons
    // before it." That rule lived only in a document, and a flat 1,200-word floor
    // certified Parts 04-06 at roughly half of Part 03 on strictly harder material —
    // Part 06 taught password storage and CSRF in 1,652 words. A floor cannot express
    // "does not regress"; only a comparison can.
    //
    // 10% of slack, because one tighter lesson in a part is a judgement call and a
    // 50% collapse is not.
    // A ratchet against every earlier part, not just the one immediately before. If it
    // only compared with the predecessor, deepening Part 04 alone would lower the bar
    // for Part 05 back to whatever Part 04 happened to land on.
    $earlier = fullstackPartsBefore($part);
    if ($earlier === []) {
        expect(true)->toBeTrue();

        return;
    }

    $benchmark = max(array_map(fullstackPartMinimumWords(...), $earlier));
    $deepest = (string) array_search($benchmark, array_combine(
        $earlier,
        array_map(fullstackPartMinimumWords(...), $earlier),
    ), true);

    $floor = (int) floor($benchmark * 0.9);
    $thinnest = fullstackThinnestLesson($part);

    expect($thinnest['words'])->toBeGreaterThanOrEqual(
        $floor,
        "Part {$part}'s thinnest lesson ({$thinnest['id']}) is {$thinnest['words']} words. "
        . "The deepest earlier part is {$deepest}, whose thinnest lesson is {$benchmark} words, "
        . "so the floor here is {$floor}. Later parts teach harder material; they do not get "
        . 'shorter. Deepen the lesson. If a part genuinely belongs below its predecessors, '
        . 'amend AGENTS.md 3 and this test together and say why.',
    );
})->with('fullstack parts');

test('the lesson shows code, not descriptions of code', function (string $id) {
    // Part 06 shipped one code block of five lines for "Users, passwords, sessions and
    // CSRF" — prose telling the learner to "add a users migration with a password
    // column sized for PHP's hash output", with no migration and no number. Every
    // Part 01-03 lesson clears 8 blocks and 25 lines; these floors are set at what the
    // track already accepted, so they catch a regression rather than impose a style.
    if (fullstackPartOf($id) === '00') {
        expect(true)->toBeTrue(); // Part 00 is observation: the browser is the artifact.

        return;
    }

    ['blocks' => $blocks, 'lines' => $lines] = fullstackCodeDensity($id);

    expect($blocks)->toBeGreaterThanOrEqual(
        8,
        "{$id} has {$blocks} code blocks. A lesson that tells the learner to write code "
        . 'shows the code. Describing it in prose moves the work of inventing it onto '
        . 'someone who does not yet know the material.',
    );

    expect($lines)->toBeGreaterThanOrEqual(
        25,
        "{$id} has {$lines} lines of code across {$blocks} blocks. That is a lesson made of "
        . 'fragments; show enough for the learner to see the shape of the thing.',
    );
})->with('fullstack lessons');

test('the lesson has no unfinished authoring markers', function (string $id) {
    $body = fullstackLessonBody($id);

    foreach (['TODO', 'FIXME', 'TBD', 'Lorem ipsum', 'XXX'] as $marker) {
        expect(str_contains($body, $marker))->toBeFalse(
            "{$id} still contains '{$marker}'. A published lesson is finished or it is not published.",
        );
    }

    expect(str_contains($body, 'Status: Published'))->toBeTrue(
        "{$id} does not declare 'Status: Published' in its metadata block.",
    );

    // All three Part 07 lessons shipped twelve headings written as `+## Route design
    // review` — a unified diff applied as literal text. Markdown renders those as a
    // paragraph beginning with a plus sign, so the section silently stops being a
    // section: it vanishes from the table of contents, and every structural check in
    // this file that looks for `^## ` walks straight past it.
    $strays = preg_grep('/^[+-](#{1,6} |\s*$)/', explode("\n", $body));

    expect($strays)->toBe(
        [],
        "{$id} contains lines left over from an applied patch, such as '"
        . trim((string) reset($strays)) . "'. Strip the leading +/- : as written it renders "
        . 'as body text, not as the heading it is supposed to be.',
    );
})->with('fullstack lessons');

test('the lesson never presents a Core lesson as required', function (string $id) {
    $lesson = CourseLoader::getLesson($id);
    $fullstackIds = fullstackLessonIds();

    // CURRICULUM.md 50 Amendment A. The prerequisites field can express a cross-track
    // dependency and is deliberately never used for one; the machinery makes the
    // wrong thing easy, which is the only reason this is worth asserting.
    foreach ($lesson['prerequisites'] as $prerequisite) {
        expect(in_array($prerequisite, $fullstackIds, true))->toBeTrue(
            "{$id} names Core lesson '{$prerequisite}' as a prerequisite. Amendment A withdrew every "
            . 'cross-track gate; a Core reference belongs in the optional "Going deeper" block as prose.',
        );
    }

    $body = fullstackLessonBody($id);

    // Every Fullstack lesson must carry the block, even to say "None" — its absence is
    // how a Core reference quietly reappears as an unlabelled requirement.
    expect(preg_match('/^Going deeper.*DALT Core.*$/mi', $body, $match))->toBe(
        1,
        "{$id} has no 'Going deeper in DALT Core — optional' block. LESSON_STANDARD.md 8 requires it; "
        . "'None.' is a valid body.",
    );
    expect(str_contains(strtolower($match[0]), 'optional'))->toBeTrue(
        "{$id}'s Core block does not say 'optional' in its heading.",
    );
})->with('fullstack lessons');

test('the build specification contains every mandatory section', function (string $id) {
    $body = BuildMilestone::specification($id);

    foreach ([
        '## What you are building',
        '## Why this milestone exists',
        '## Before you start',
        '## Decisions you have to make',
        '## Acceptance criteria',
        '## Prove it to yourself',
        '## What this unlocks',
    ] as $heading) {
        expect(str_contains($body, $heading . "\n"))->toBeTrue(
            "Build {$id} is missing '{$heading}'. See .dalt/course/build/README.md.",
        );
    }

    expect(preg_match('/^## Stage 1/m', $body))->toBe(
        1,
        "Build {$id} has no numbered stages. A milestone is built in stages, each with something to "
        . 'make and a way to check it.',
    );
})->with('build milestones');

test('every build stage tells the learner how to check their own work', function (string $id) {
    $body = BuildMilestone::specification($id);
    $stages = preg_split('/^## Stage /m', $body);
    array_shift($stages);

    expect($stages)->not->toBe([], "Build {$id} declares no stages.");

    foreach ($stages as $stage) {
        $name = trim(strtok($stage, "\n") ?: '?');
        expect(str_contains($stage, 'Check it yourself'))->toBeTrue(
            "Build {$id}, stage '{$name}' has no 'Check it yourself'. Without it the stage is a "
            . 'suggestion — the learner has no way to tell whether they finished it.',
        );
    }
})->with('build milestones');

test('the build specification asks for evidence, not feelings', function (string $id) {
    $body = BuildMilestone::specification($id);
    $criteria = substr($body, (int) strpos($body, '## Acceptance criteria'));
    $boxes = preg_match_all('/^- \[ \] /m', $criteria);

    expect($boxes)->toBeGreaterThan(4, "Build {$id} has only {$boxes} acceptance criteria.");

    // "I understand X" is not a criterion. Criteria name software that ran.
    expect(preg_match('/^- \[ \] I understand\b/mi', $criteria))->toBe(
        0,
        "Build {$id} has an acceptance criterion beginning 'I understand'. Criteria name an artifact "
        . 'or an observable result, not a feeling about one.',
    );

    expect(str_contains($body, 'Nothing here is checked automatically'))->toBeTrue(
        "Build {$id} does not state that its criteria are self-assessed. Never let a milestone imply "
        . 'the platform verified something it did not.',
    );
})->with('build milestones');

test('every npm script a lesson names is defined somewhere the learner can reach', function (string $id) {
    // The build-specification equivalent of this check already exists in
    // FullstackLabExecutionTest and caught B02 telling the learner to run a script its
    // starter called something else. Lessons had no such check.
    $body = fullstackLessonBody($id);
    preg_match_all('/npm run ([a-z][a-z0-9:-]*)/', $body, $matches);
    $scripts = array_unique($matches[1]);

    expect(true)->toBeTrue();

    if ($scripts === []) {
        return;
    }

    $available = [];
    foreach (['package.json', ...glob(base_path('.dalt/course/fullstack/*/starter/package.json'))] as $manifest) {
        $path = str_starts_with($manifest, '/') ? $manifest : base_path($manifest);
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            $available = [...$available, ...array_keys($decoded['scripts'] ?? [])];
        }
    }

    foreach ($scripts as $script) {
        expect(in_array($script, $available, true))->toBeTrue(
            "{$id} tells the learner to run `npm run {$script}`, but no lab starter or the root "
            . 'manifest defines it. Either the lesson or the lab is wrong.',
        );
    }
})->with('fullstack lessons');

test('every lesson link in the body points at a lesson that exists', function (string $id) {
    // FS06.2 and FS06.3 both linked /learn/lessons/06-authentication for as long as
    // nobody clicked it; the Core lesson is 04-authentication. A dead link in the
    // "Going deeper in DALT Core" block is worse than no link — it tells the learner
    // the optional reading exists and then 404s them.
    preg_match_all('#\(/learn/lessons/([a-z0-9-]+)\)#', fullstackLessonBody($id), $matches);
    $slugs = array_unique($matches[1]);

    expect(true)->toBeTrue();   // a lesson with no such links is fine, and not risky

    foreach ($slugs as $slug) {
        expect(is_dir(base_path(".dalt/course/lessons/{$slug}")))->toBeTrue(
            "{$id} links to /learn/lessons/{$slug}, which does not exist.",
        );
    }
})->with('fullstack lessons');

test('the build specification opens by naming what will exist', function (string $id) {
    $body = ltrim(BuildMilestone::specification($id));

    // B00-B05 all open with this line and B06 shipped without it. A learner should be
    // able to read one sentence and know what they are about to have.
    expect(str_starts_with($body, '> **What exists when you finish:**'))->toBeTrue(
        "Build {$id} does not open with a '> **What exists when you finish:**' blockquote. "
        . 'The end state is the first thing a milestone owes its reader.',
    );
})->with('build milestones');

test('the build specification is as substantial as the work it describes', function (string $id) {
    $words = str_word_count(strip_tags(BuildMilestone::specification($id)));

    // B00-B03 shipped at 1,419-1,917 words; B04-B06 shipped at 960-1,169 on strictly
    // harder work. The floor sits just under the thinnest specification the track has
    // accepted, so it catches a regression rather than imposing a length.
    expect($words)->toBeGreaterThan(
        1300,
        "Build {$id} is {$words} words. A milestone specification carries the whole of a part's "
        . 'practical work; at this length it is a summary of one. Later milestones are larger '
        . 'undertakings than earlier ones and their specifications should reflect that.',
    );
})->with('build milestones');

test('the track manifest and the catalog agree in both directions', function () {
    $track = FullstackTrack::load();

    $inParts = [];
    foreach ($track['parts'] as $part) {
        foreach ($part['lessons'] as $lessonId) {
            $inParts[] = $lessonId;
        }
    }

    // FullstackTrack::load() throws on most drift; this pins the totals so a whole
    // part quietly vanishing from the manifest is still caught.
    expect(count($inParts))->toBe(count(fullstackLessonIds()))
        ->and(array_diff(fullstackLessonIds(), $inParts))->toBe([])
        ->and(count(array_unique($inParts)))->toBe(count($inParts));
});
