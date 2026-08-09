<?php

/**
 * Broken Nginx Config Challenge - Test Specification
 *
 * Verifies that the learner has added the two FastCGI directives
 * that forward PHP requests from Nginx to PHP-FPM.
 */

return [
    'has_fastcgi_pass' => [
        'type' => 'file_contains',
        'file' => 'nginx/default.conf',
        'search' => 'fastcgi_pass app:9000',
        'hint' => 'Add: fastcgi_pass app:9000;  inside the location ~ \\.php$ block. "app" is the service name of the PHP-FPM container in docker-compose.yml',
    ],

    'has_script_filename' => [
        'type' => 'file_contains',
        'file' => 'nginx/default.conf',
        'search' => 'SCRIPT_FILENAME',
        'hint' => 'Add: fastcgi_param SCRIPT_FILENAME /var/www/html/public$fastcgi_script_name;  — this tells PHP-FPM which file to execute',
    ],

    'no_todos_remaining' => [
        'type' => 'file_not_contains',
        'file' => 'nginx/default.conf',
        'search' => '# TODO',
        'include_comments' => true,
        'hint' => 'Remove all # TODO comments after completing each directive',
    ],
];
