<?php

declare(strict_types=1);

defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__) . '/');

require_once BASE_PATH . 'framework/Core/functions.php';

pest()->extend(Tests\TestCase::class)->in('Unit', 'Feature');
