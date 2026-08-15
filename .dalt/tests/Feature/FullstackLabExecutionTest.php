<?php

declare(strict_types=1);

/**
 * Runs every course-owned Fullstack lab the way the lesson tells the learner to run it.
 *
 * Structural assertions elsewhere ("package.json exists", "the file contains 19.2.3")
 * cannot tell you whether the learner's very first command works. They did not, and a
 * Part 03 lab shipped whose `npm test` failed before the learner had changed anything.
 * This file exists so that cannot happen again.
 *
 * Several labs are *supposed* to fail on the unmodified starter — that seeded failure is
 * the lesson. So a bare "exit code was non-zero" assertion is not enough: it would pass
 * just as happily for a lab broken by a missing dependency or a typo in a script name.
 * Every expected failure therefore also pins a marker string proving the lab failed for
 * the intended reason. That is the plausible-fake standard applied to the labs
 * themselves: the seeded failure fails, a genuine fix passes, and a lab that merely
 * happens to be broken does not read as either.
 *
 * Cost: roughly a minute, dominated by six `npm ci` runs. `DALT_SKIP_LAB_EXECUTION=1`
 * skips the file for tight inner loops — never in a run whose result you intend to report.
 */

/**
 * Every script in every lab, with what it must do on the untouched starter.
 *
 * 'pass'            exit 0.
 * ['fail', marker]  non-zero exit, and the combined output contains marker.
 *
 * Reasons are sourced from the lesson that owns the lab, not from observed behavior.
 * If a pin here disagrees with reality, decide which one is wrong before editing either.
 */
function fullstackLabExpectations(): array
{
    return [
        // FS02.1 — the starter ships a deliberate contract mismatch: IssueSummary.id is a
        // number, the changed requirement supplies the visible key 'ISS-19'. The learner
        // decides whether the caller or the contract is wrong. README.md step 1.
        'typescript-lab' => [
            'runtime' => ['fail', 'TypeError'],          // the JavaScript surprise the lesson opens with
            'stage-a' => ['fail', 'error TS'],           // the checker catching what JavaScript did not
            'typecheck' => ['fail', 'error TS2322'],     // the seeded mismatch
            'emit:erasure' => 'pass',
            'run:erasure' => 'pass',
            'build' => ['fail', 'error TS'],             // same seeded mismatch, through the emitting path
        ],

        // FS02.2 — each stage: script isolates one modeling mistake. stage:structural is the
        // exception and must succeed: the lesson says "It succeeds", because a richer object
        // satisfies a smaller shape structurally.
        'typescript-modeling-lab' => [
            'typecheck' => 'pass',
            'stage:required' => ['fail', 'error TS'],
            'stage:optional' => ['fail', 'error TS'],
            'stage:readonly' => ['fail', 'error TS'],
            'stage:status' => ['fail', 'error TS'],
            'stage:nested' => ['fail', 'error TS'],
            'stage:structural' => 'pass',
            'run' => 'pass',
        ],

        // FS02.3 — the --noEmit stages demonstrate a diagnostic; stage:truthiness and
        // stage:guard compile *and execute*, so they must succeed.
        'typescript-narrowing-lab' => [
            'typecheck' => 'pass',
            'run' => 'pass',
            'stage:union' => ['fail', 'error TS'],
            'stage:unknown' => ['fail', 'error TS'],
            'stage:truthiness' => 'pass',
            'stage:state' => ['fail', 'error TS'],
            'stage:exhaustive' => ['fail', 'error TS'],
            'stage:guard' => 'pass',
        ],

        // FS02.4 — "The stage:* commands intentionally show a compiler error; a non-zero
        // result there is evidence for the experiment, not a final failure." (lesson, §30)
        'typescript-functions-lab' => [
            'typecheck' => 'pass',
            'run' => 'pass',
            'stage:return-contract' => ['fail', 'error TS'],
            'stage:callbacks' => ['fail', 'error TS'],
            'stage:constraint' => ['fail', 'error TS'],
            'stage:utility-model-change' => ['fail', 'error TS'],
        ],

        // FS02.5 — parseIssue is an unimplemented TODO throw. `run` and `test` must fail
        // until the learner establishes runtime evidence; `unsafe` must fail at runtime
        // despite a clean typecheck, which is the entire point of the lesson.
        'typescript-runtime-boundaries-lab' => [
            'typecheck' => 'pass',
            'unsafe' => ['fail', 'TypeError'],
            'stage:unknown' => ['fail', 'error TS'],
            'stage:object-shape' => 'pass',
            'run' => ['fail', 'TODO: establish runtime evidence'],
            'test' => ['fail', 'TODO: establish runtime evidence'],
        ],

        // FS03.1–FS03.4 — the only lab with nothing seeded broken. The learner grows it
        // across four lessons, so everything must be green before they touch it.
        'react-foundations-lab' => [
            'typecheck' => 'pass',
            'test' => 'pass',
            'build' => 'pass',
        ],

        // FS07.3 — ProjectPage imports the API client directly instead of reading the
        // ApiContext seam, so the five component tests wrap it in a provider holding a
        // fake and the component ignores all of it and calls fetch. jsdom cannot resolve
        // a relative URL, which surfaces as "Failed to parse URL" — the honest error a
        // learner gets when the seam is missing, not a synthetic one.
        //
        // typecheck must PASS: the defect is a wiring mistake between two values of the
        // same type, so the compiler has nothing to say. That combination — green types,
        // red run — is the trap the lesson is built around, and pinning it here is what
        // stops someone "fixing" the lab by making it a type error instead.
        'frontend-testing-lab' => [
            'typecheck' => 'pass',
            'test' => ['fail', 'Failed to parse URL'],
            'test:parsers' => 'pass',   // the cheapest level works before the seam exists
            'build' => 'pass',
        ],
    ];
}

/**
 * Build-milestone workspaces, under `.dalt/course/build/<ID>-<slug>/`.
 *
 * These were missed when this file was first written, which covered only
 * `.dalt/course/fullstack/`. The B02 specification told the learner to run
 * `npm run test` against a starter whose script was called `test:parser` — a
 * "Missing script" error on the milestone that teaches trust boundaries. The gap in
 * the guard is what let the gap in the content through, so the guard now covers
 * every runnable workspace the course ships, not one directory of them.
 *
 * Keys are `<milestone-dir>` or `<milestone-dir>/reference/<name>`.
 */
function fullstackBuildExpectations(): array
{
    return [
        // B02 stage 1 is "complete the model until it typechecks", so the untouched
        // starter must not typecheck — and everything downstream of tsc fails with it.
        'B02-type-the-future-application/starter' => [
            'typecheck' => ['fail', 'error TS'],
            'run' => ['fail', 'error TS'],
            'test' => ['fail', 'error TS'],
        ],

        // The author-facing worked solution. Its job is to prove the milestone is
        // completable at all; if it stops passing, the specification is asking for
        // something that cannot be built. Never reachable from learner navigation.
        'B02-type-the-future-application/reference/final' => [
            'typecheck' => 'pass',
            'run' => 'pass',
            'test' => 'pass',
        ],
    ];
}

/** Scripts that never terminate on their own and so cannot be asserted on. */
const FULLSTACK_LAB_LONG_RUNNING = ['dev', 'preview', 'watch'];

function fullstackLabRun(string $directory, array $command, int $timeoutSeconds = 300): array
{
    $process = proc_open(
        $command,
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $directory,
        null,
        ['bypass_shell' => true],
    );

    if (!is_resource($process)) {
        return [-1, 'could not start: ' . implode(' ', $command)];
    }

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $output = '';
    $deadline = microtime(true) + $timeoutSeconds;
    while (true) {
        $output .= (string) stream_get_contents($pipes[1]);
        $output .= (string) stream_get_contents($pipes[2]);

        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if (microtime(true) > $deadline) {
            proc_terminate($process, 9);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return [-1, $output . "\n[timed out after {$timeoutSeconds}s]"];
        }
        usleep(20000);
    }

    $output .= (string) stream_get_contents($pipes[1]);
    $output .= (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
}

function fullstackLabCopy(string $source, string $destination): void
{
    mkdir($destination, 0700, true);
    foreach (new FilesystemIterator($source, FilesystemIterator::SKIP_DOTS) as $entry) {
        $target = $destination . '/' . $entry->getFilename();
        if ($entry->isDir()) {
            fullstackLabCopy($entry->getPathname(), $target);
        } else {
            copy($entry->getPathname(), $target);
        }
    }
}

function fullstackLabRemove(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);

        return;
    }
    if (!is_dir($path)) {
        return;
    }
    foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $entry) {
        fullstackLabRemove($entry->getPathname());
    }
    rmdir($path);
}

dataset('fullstack labs', array_map(
    static fn (string $lab): array => [$lab],
    array_keys(fullstackLabExpectations()),
));

dataset('build workspaces', array_map(
    static fn (string $workspace): array => [$workspace],
    array_keys(fullstackBuildExpectations()),
));

/**
 * Copy a workspace to a temp directory, install it, and assert every pinned script.
 *
 * Shared by both datasets so a runnable workspace cannot be covered by one and not
 * the other — which is exactly how the B02 starter went unchecked.
 */
function fullstackAssertWorkspace(object $test, string $label, string $source, array $expectations): void
{
    if (getenv('DALT_SKIP_LAB_EXECUTION') === '1') {
        $test->markTestSkipped('DALT_SKIP_LAB_EXECUTION=1.');
    }

    expect(is_dir($source))->toBeTrue("'{$label}' has no directory at {$source}.");
    expect(is_file($source . '/package-lock.json'))
        ->toBeTrue("'{$label}' has no lockfile, so `npm ci` cannot pin what the learner installs.");

    $npm = fullstackLabRun($source, ['npm', '--version'], 30);
    if ($npm[0] !== 0) {
        $test->markTestSkipped('npm is not available on this machine.');
    }

    $workspace = sys_get_temp_dir() . '/dalt-lab-' . bin2hex(random_bytes(6));

    try {
        fullstackLabCopy($source, $workspace);

        // A failed install is an environment problem (no network, cold cache), not a
        // defect in the workspace. Skip rather than fail — but never treat it as a pass.
        [$installExit, $installOutput] = fullstackLabRun(
            $workspace,
            ['npm', 'ci', '--prefer-offline', '--no-audit', '--no-fund'],
            600,
        );
        if ($installExit !== 0) {
            $test->markTestSkipped("`npm ci` failed for '{$label}'; treating as an offline environment.\n" . $installOutput);
        }

        $scripts = json_decode(
            (string) file_get_contents($workspace . '/package.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        )['scripts'] ?? [];

        $assertable = array_values(array_diff(array_keys($scripts), FULLSTACK_LAB_LONG_RUNNING));

        // An unpinned script is a hole in this guard, so the pin list and the workspace
        // must agree in both directions. The reverse check is the one that catches a
        // specification referring to a script that does not exist.
        expect(array_values(array_diff($assertable, array_keys($expectations))))
            ->toBe([], "'{$label}' has scripts with no pinned expectation. Add them to the expectation list.");
        expect(array_values(array_diff(array_keys($expectations), $assertable)))
            ->toBe([], "The expectation list pins scripts that '{$label}' does not have.");

        foreach ($expectations as $script => $expected) {
            [$exit, $output] = fullstackLabRun($workspace, ['npm', 'run', '--silent', $script], 300);

            if ($expected === 'pass') {
                expect($exit)->toBe(
                    0,
                    "`npm run {$script}` must succeed on the untouched '{$label}', "
                    . "because the lesson or specification tells the learner to run it and read a clean result.\n{$output}",
                );

                continue;
            }

            [, $marker] = $expected;
            expect($exit)->not->toBe(
                0,
                "`npm run {$script}` must fail on the untouched '{$label}' — that seeded "
                . "failure is the lesson. It succeeded, so the seed is gone.\n{$output}",
            );
            // str_contains rather than toContain(): Pest's toContain() is variadic over
            // needles and would read a failure message as a second thing to look for.
            expect(str_contains($output, $marker))->toBeTrue(
                "`npm run {$script}` failed for the wrong reason in '{$label}'. Expected the seeded "
                . "failure containing \"{$marker}\", which is what makes this an exercise rather "
                . "than a broken workspace.\n{$output}",
            );
        }
    } finally {
        fullstackLabRemove($workspace);
    }
}

test('the lab runs exactly as its lesson promises', function (string $lab) {
    fullstackAssertWorkspace(
        $this,
        $lab,
        base_path(".dalt/course/fullstack/{$lab}/starter"),
        fullstackLabExpectations()[$lab],
    );
})->with('fullstack labs');

test('the build workspace runs exactly as its milestone specification promises', function (string $workspace) {
    fullstackAssertWorkspace(
        $this,
        $workspace,
        base_path(".dalt/course/build/{$workspace}"),
        fullstackBuildExpectations()[$workspace],
    );
})->with('build workspaces');

test('every command a milestone specification names actually exists', function () {
    // The defect this catches: B02's specification said `npm run test` three times
    // while its starter only defined `test:parser`. Structural conformance passed,
    // the lab test did not cover build workspaces, and the learner would have hit
    // "Missing script" on the milestone about trust boundaries.
    foreach (\Core\BuildMilestone::all() as $id => $milestone) {
        $body = \Core\BuildMilestone::specification($id);
        preg_match_all('/`npm run ([a-z][a-z0-9:-]*)`/', $body, $matches);
        $referenced = array_unique($matches[1]);
        if ($referenced === []) {
            continue;
        }

        // A milestone may legitimately name scripts belonging to the repository root
        // (B03 works there) as well as to its own starter. Both are valid targets.
        $available = [];
        foreach ([$milestone['path'] . '/starter/package.json', base_path('package.json')] as $manifest) {
            if (is_file($manifest)) {
                $decoded = json_decode((string) file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);
                $available = [...$available, ...array_keys($decoded['scripts'] ?? [])];
            }
        }

        foreach ($referenced as $script) {
            expect(in_array($script, $available, true))->toBeTrue(
                "Build {$id} tells the learner to run `npm run {$script}`, but no package.json "
                . 'it can reach defines that script. Either the specification or the starter is wrong.',
            );
        }
    }
});

test('the FS06.1 behaviour-test lab passes, and its sabotages fail', function () {
    $lab = base_path('.dalt/course/fullstack/api-behavior-tests-lab');
    expect(is_dir($lab))->toBeTrue('FS06.1 needs a runnable lab; a lesson about tests must ship tests.');

    $run = static function () use ($lab): array {
        $process = proc_open(
            [
                PHP_BINARY, base_path('vendor/bin/pest'),
                $lab . '/tests', '--bootstrap=' . $lab . '/bootstrap.php',
            ],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path(),
        );
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output];
    };

    [$status, $output] = $run();
    expect($status)->toBe(0, "The FS06.1 lab does not pass as shipped:\n" . $output);
    expect(str_contains($output, '10 passed'))->toBeTrue(
        "The FS06.1 lab should run ten tests. If a test was added or removed, update the\n"
        . "lab README's table and this expectation together.\n" . $output,
    );

    // The plausible-fake standard, applied to the lab itself. The README tells the
    // learner to break the implementation and watch a named test fail. If a sabotage
    // stops failing, that instruction becomes a lie and the lab teaches nothing.
    $source = $lab . '/src/IssueApi.php';
    $original = (string) file_get_contents($source);

    $sabotages = [
        'rollback removed' => ['$this->pdo->rollBack();', '$this->pdo->commit();'],
        'validation bypassed' => ['if ($errors !== []) {', 'if (false) {'],
    ];

    try {
        foreach ($sabotages as $label => [$from, $to]) {
            file_put_contents($source, str_replace($from, $to, $original));
            [$sabotagedStatus, $sabotagedOutput] = $run();

            expect($sabotagedStatus)->not->toBe(
                0,
                "The FS06.1 lab still passes with {$label}. Its tests do not prove what the "
                . "README says they prove.\n" . $sabotagedOutput,
            );
        }
    } finally {
        file_put_contents($source, $original);
    }

    [$restoredStatus] = $run();
    expect($restoredStatus)->toBe(0, 'The lab was left broken after the sabotage check.');
})->skip(
    !is_file(base_path('vendor/bin/pest')),
    'Pest is not installed; the FS06.1 lab cannot be executed.',
);

test('the Part 04 fixture API executes the documented issue lifecycle', function () {
    $fixture = base_path('.dalt/course/fullstack/react-server-fixture/fixture-api.php');
    expect(is_file($fixture))->toBeTrue('Part 04 needs its resettable fixture API.');

    $port = random_int(18000, 24000);
    $process = proc_open(
        ['php', '-S', "127.0.0.1:{$port}", $fixture],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname($fixture),
        null,
        ['bypass_shell' => true],
    );
    expect(is_resource($process))->toBeTrue('Could not start the Part 04 fixture server.');

    try {
        $base = "http://127.0.0.1:{$port}";
        // Wait on the socket rather than polling with file_get_contents: `@` hides the
        // "Connection refused" message but Pest's error handler still records it, and
        // a warning-coloured pass is a pass nobody reads.
        // Pest promotes even `@`-suppressed warnings, and every refused connect during
        // startup would colour this test WARN. Silence the wait loop specifically, then
        // restore — a warning-coloured pass is a pass nobody reads.
        set_error_handler(static fn (): bool => true);
        try {
            $deadline = microtime(true) + 5;
            while (microtime(true) < $deadline) {
                $probe = fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
                if (is_resource($probe)) {
                    fclose($probe);
                    break;
                }
                usleep(50000);
            }
        } finally {
            restore_error_handler();
        }

        $initial = file_get_contents("{$base}/api/issues");
        expect($initial)->not->toBeFalse('Part 04 fixture did not accept GET /api/issues.');
        $issues = json_decode((string) $initial, true, flags: JSON_THROW_ON_ERROR);
        expect($issues)->toHaveCount(3);

        // The domain must survive Part 04. B02 types `Project` and puts `projectId` on
        // CreateIssueInput, and FS05.2 makes projects a table; a fixture without the
        // field would drop it from the domain for one whole part, so the learner's own
        // Issue type would stop matching the server through no fault of theirs.
        expect(array_keys($issues[0]))->toBe(['id', 'projectId', 'title', 'status', 'priority']);

        $request = static function (string $method, string $path, ?array $body = null) use ($base): array {
            $context = stream_context_create(['http' => [
                'method' => $method,
                'header' => "Content-Type: application/json\r\n",
                'content' => $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR),
                'ignore_errors' => true,
            ]]);
            $response = file_get_contents($base . $path, false, $context);
            $status = $http_response_header[0] ?? '';
            return [$status, $response];
        };

        [$createdStatus, $createdBody] = $request('POST', '/api/issues', ['title' => 'Prove a mutation', 'priority' => 'high']);
        expect($createdStatus)->toContain('201');
        $created = json_decode((string) $createdBody, true, flags: JSON_THROW_ON_ERROR);
        expect($created['title'])->toBe('Prove a mutation');

        // FS03.3 makes the learner build a priority select and verify the new issue
        // "appears with the chosen priority". B04 Stage 2 then points that form at this
        // fixture and says to render the returned 201 issue rather than a guessed copy.
        // While the fixture hardcoded 'medium', doing exactly what both documents say
        // silently deleted the feature, and the learner would hunt for it in their own
        // request body. Found by performing B04; same class as the projectId gap above.
        expect($created['priority'])->toBe(
            'high',
            'The Part 04 fixture ignored the priority the learner sent. FS03.3 requires the '
            . 'chosen priority to survive creation.',
        );

        // str_contains rather than toContain(): toContain() is variadic over needles and
        // would read the failure message as a second thing to look for.
        [$badPriorityStatus] = $request('POST', '/api/issues', ['title' => 'Bad priority', 'priority' => 'urgent']);
        expect(str_contains($badPriorityStatus, '422'))->toBeTrue(
            'A priority outside the union must be rejected, not stored. The learner parses '
            . 'this response against their own Priority type.',
        );

        [$defaultedStatus, $defaultedBody] = $request('POST', '/api/issues', ['title' => 'No priority sent']);
        expect($defaultedStatus)->toContain('201')
            ->and(json_decode((string) $defaultedBody, true, flags: JSON_THROW_ON_ERROR)['priority'])->toBe('medium');

        [$invalidStatus, $invalidBody] = $request('POST', '/api/issues', ['title' => '   ']);
        expect($invalidStatus)->toContain('422')
            ->and(json_decode((string) $invalidBody, true, flags: JSON_THROW_ON_ERROR)['error']['code'])->toBe('validation_failed');

        [$patchedStatus, $patchedBody] = $request('PATCH', '/api/issues/' . $created['id'], ['status' => 'done']);
        expect($patchedStatus)->toContain('200')
            ->and(json_decode((string) $patchedBody, true, flags: JSON_THROW_ON_ERROR)['status'])->toBe('done');

        [$deletedStatus, $deletedBody] = $request('DELETE', '/api/issues/' . $created['id']);
        expect($deletedStatus)->toContain('204')->and($deletedBody)->toBe('');

        // FS04.2, FS04.3, FS05.1 and B04 tell the learner ten times over that a 204
        // carries no body and must not be handed to .json(). The fixture emitted
        // `null` after the status line for exactly as long as nobody ran this.
        [, $reDeletedBody] = $request('DELETE', '/api/issues/' . $created['id']);
        expect($reDeletedBody)->not->toBe(
            'null',
            'The Part 04 fixture put a JSON body on a bodiless response. Part 04 teaches '
            . 'the opposite in four places.',
        );

        // 404 and 405 are different facts about a request, and FS05.1 makes the learner
        // implement both. A fixture that answers 405 for an unknown path teaches the
        // learner that their typo was a method problem.
        [$missingStatus, $missingBody] = $request('GET', '/api/issues/ISS-9999');
        expect($missingStatus)->toContain('404')
            ->and(json_decode((string) $missingBody, true, flags: JSON_THROW_ON_ERROR)['error']['code'])->toBe('not_found');

        [$unroutedStatus] = $request('GET', '/api/nope');
        expect($unroutedStatus)->toContain('404');

        [$wrongMethodStatus] = $request('PUT', '/api/issues/ISS-42');
        expect($wrongMethodStatus)->toContain('405');

        [$detailStatus, $detailBody] = $request('GET', '/api/issues/ISS-42');
        expect($detailStatus)->toContain('200')
            ->and(json_decode((string) $detailBody, true, flags: JSON_THROW_ON_ERROR)['id'])->toBe('ISS-42');

        // The Part 03 lab serves on :5174 and this fixture allowed only :5173, so the
        // learner's first fetch in Part 04 died on CORS. Both loopback ports must work
        // and a foreign origin must not.
        $originHeaders = static function (string $origin) use ($base): string {
            $context = stream_context_create(['http' => [
                'method' => 'GET',
                'header' => "Origin: {$origin}\r\n",
                'ignore_errors' => true,
            ]]);
            file_get_contents($base . '/api/issues', false, $context);

            return implode("\n", $http_response_header ?? []);
        };

        foreach (['http://localhost:5173', 'http://localhost:5174', 'http://127.0.0.1:5173'] as $origin) {
            expect(str_contains($originHeaders($origin), 'Access-Control-Allow-Origin: ' . $origin))->toBeTrue(
                "The Part 04 fixture did not allow the local dev origin {$origin}. Part 03 serves "
                . 'on 5174; a fixture that only knows 5173 makes the learner debug the course.',
            );
        }

        expect(str_contains($originHeaders('http://evil.example'), 'Access-Control-Allow-Origin'))->toBeFalse(
            'The Part 04 fixture reflected a non-loopback origin. Reflecting any origin with '
            . 'Allow-Credentials is the bug this fixture should not be teaching.',
        );
    } finally {
        proc_terminate($process);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
});
