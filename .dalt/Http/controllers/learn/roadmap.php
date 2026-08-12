<?php

declare(strict_types=1);

$roadmapPath = base_path('documentation/competency-roadmap.md');

if (!is_file($roadmapPath) || !is_readable($roadmapPath)) {
    abort(404, 'The competency roadmap is not available.');
}

$content = file_get_contents($roadmapPath);

if ($content === false) {
    abort(500, 'The competency roadmap could not be read.');
}

// The page header owns the title; keep the Markdown body as the canonical content.
$content = preg_replace('/\A# DALT\.PHP Competency Roadmap\R+/', '', $content, 1) ?? $content;

return view('learn/roadmap.view.php', [
    'content' => $content,
]);
