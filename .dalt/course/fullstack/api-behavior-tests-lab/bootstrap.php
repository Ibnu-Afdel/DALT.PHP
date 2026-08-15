<?php

declare(strict_types=1);

// Minimal bootstrap for the FS06.1 lab. It loads Composer's autoloader for Pest and
// the one class under test. The lab deliberately does not boot DALT: the lesson is
// about what a behaviour test observes, and a smaller surface makes that visible.

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once __DIR__ . '/src/IssueApi.php';
