<?php

/**
 * Missing Row-Level Security — Test Specification
 *
 * Runs against the learner's own PostgreSQL, connected as the non-superuser
 * app_user role the README requires (driver: pgsql, see DECISIONS.md D-09) —
 * RLS is silently bypassed for superusers and table owners, so a check running
 * as postgres could never tell a working policy from a decorative one. Each
 * handler_result check opens its own transaction that is always rolled back.
 *
 * Every check's seed sets the session tenant with set_config before each tenant's
 * rows, so seeding succeeds regardless of whether the learner's policy scopes
 * WITH CHECK to INSERT as well as SELECT — both are valid designs.
 *
 * php_no_longer_filters_by_tenant stays a structural check: the response is
 * identical whether isolation comes from the database or from tenant/posts.php's
 * own WHERE clause, so removing the hand-written filter is invisible to every
 * behavioral check below and has to be asserted directly.
 *
 * isolation_is_enforced_by_the_database_not_php is the one that matters. It
 * dispatches a *different* controller — Http/controllers/db/rls-probe.php,
 * platform-authored and never routed — that reads the table directly with no
 * tenant_id filter of any kind, PHP or SQL. A fix that keeps tenant/posts.php's
 * own WHERE clause and skips real RLS passes tenant_one/tenant_two below (that
 * endpoint still filters correctly) but fails this one: nothing stops a raw read
 * from seeing every tenant's rows except the database itself. This is DALT-0074
 * restated as a check — "the code runs; it simply does not protect anything" is
 * exactly what a response-only check cannot see.
 */

$seedTwoTenants = [
    "SELECT set_config('app.tenant_id', '1', false)",
    "INSERT INTO posts (tenant_id, title, body, user_id) VALUES (1, 't1-a', 'x', 1)",
    "INSERT INTO posts (tenant_id, title, body, user_id) VALUES (1, 't1-b', 'x', 1)",
    "SELECT set_config('app.tenant_id', '2', false)",
    "INSERT INTO posts (tenant_id, title, body, user_id) VALUES (2, 't2-a', 'x', 1)",
];

return [
    'php_no_longer_filters_by_tenant' => [
        'type'   => 'file_not_contains',
        'file'   => 'Http/controllers/tenant/posts.php',
        'search' => 'WHERE tenant_id =',
        'hint'   => 'Once the database enforces isolation, the hand-written WHERE clause is the thing you are removing.',
    ],

    'tenant_one_sees_only_its_rows' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/tenant/posts.php',
        'seed'    => $seedTwoTenants,
        'route'   => ['tenant_id' => '1'],
        'expect'  => ['source' => 'body', 'count' => 2, 'count_key' => 'data', 'not_contains' => 't2-a'],
        'hint'    => 'Requesting tenant 1 must return exactly its two rows and never tenant 2\'s.',
    ],

    'tenant_two_sees_only_its_rows' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/tenant/posts.php',
        'seed'    => $seedTwoTenants,
        'route'   => ['tenant_id' => '2'],
        'expect'  => ['source' => 'body', 'count' => 1, 'count_key' => 'data', 'contains' => 't2-a', 'not_contains' => 't1-a'],
        'hint'    => 'Requesting tenant 2 must return exactly its one row and never tenant 1\'s.',
    ],

    'isolation_is_enforced_by_the_database_not_php' => [
        'type'    => 'handler_result',
        'driver'  => 'pgsql',
        'file'    => 'Http/controllers/db/rls-probe.php',
        'seed'    => $seedTwoTenants,
        'expect'  => ['source' => 'body', 'count' => 1, 'count_key' => 'rows', 'contains' => 't2-a', 'not_contains' => 't1-a'],
        'hint'    => "A raw, unfiltered read of posts must still come back scoped to whatever tenant the session last set — nothing in PHP is protecting this read. If this fails while the two checks above pass, isolation lives in tenant/posts.php's own WHERE clause, not in the database.",
    ],
];
