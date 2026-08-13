<?php

declare(strict_types=1);

/**
 * Isolated probe for the compose_config check.
 *
 * Shells out to `docker compose config`, which normalizes a compose file on
 * the client side — no daemon is contacted, so this works even on a machine
 * that cannot run containers. This is what distinguishes "the magic word
 * appears somewhere in the file" from "the file structurally means what it
 * needs to mean": a file_contains check for "service_healthy" passes even if
 * the string sits under the wrong service or inside a comment.
 *
 * Runs as its own process, mirroring probe-handler-result.php and
 * probe-class-contract.php: a malformed compose file, or a missing/broken
 * Docker CLI, must not disturb the verifier's own process. Uses proc_open
 * rather than shell_exec (unlike the other probes) because the caller needs
 * stdout and stderr kept apart to turn a Compose failure into an actionable
 * message instead of a bare "it failed".
 *
 * Usage: php probe-compose-config.php <composeFile>
 * Prints a single JSON object on stdout:
 *   {"ok": true, "config": {...normalized compose config...}}
 *   {"ok": false, "error": "..."}
 * A missing or broken Docker CLI is reported as an error — never a silent pass.
 */

if ($argc !== 2) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => 'The compose config probe received the wrong arguments.']));
    exit(1);
}

[, $composeFile] = $argv;

$fail = static function (string $message): never {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => $message]));
    exit(0);
};

if (!is_file($composeFile)) {
    $fail('Compose file not found.');
}

// Canonicalize before changing the child process's working directory below —
// otherwise a relative $composeFile would be resolved twice: once here,
// once more by Docker against its new cwd.
$composeFile = realpath($composeFile);
if ($composeFile === false) {
    $fail('Compose file path could not be resolved.');
}

$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$process = @proc_open(
    ['docker', 'compose', '-f', $composeFile, 'config', '--format', 'json'],
    $descriptors,
    $pipes,
    dirname($composeFile),
);

if (!is_resource($process)) {
    $fail('The Docker CLI could not be started. Install Docker and make sure "docker" is on PATH.');
}

fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0) {
    $reason = is_string($stderr) && trim($stderr) !== '' ? trim($stderr) : "docker compose config exited with status {$exitCode}";
    $fail("Docker Compose could not parse the file: {$reason}");
}

$config = is_string($stdout) ? json_decode($stdout, true) : null;
if (!is_array($config)) {
    $fail('Docker Compose produced output that was not valid JSON.');
}

fwrite(STDOUT, json_encode(['ok' => true, 'config' => $config]));

exit(0);
