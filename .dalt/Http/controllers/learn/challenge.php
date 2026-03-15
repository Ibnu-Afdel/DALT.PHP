<?php

$challengeId = $_GET['challenge'] ?? '';

// Validate challenge exists and load metadata from meta.json
$challenge = \Core\CourseLoader::getChallenge($challengeId);
if (!$challenge) {
    abort(404);
}

$readmePath = base_path(".dalt/course/challenges/{$challengeId}/README.md");
if (!file_exists($readmePath)) {
    abort(404);
}
$content = file_get_contents($readmePath);

view('learn/challenge.view.php', [
    'challengeId' => $challengeId,
    'challenge' => $challenge,
    'content' => $content
]);
