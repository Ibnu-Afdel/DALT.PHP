<?php

/**
 * Static checks for the missing row-level security policy.
 *
 * Note on set_config: PostgreSQL's SET is a utility statement and cannot take
 * bind parameters — "SET app.tenant_id = :id" fails with a syntax error at $1.
 * set_config(name, value, is_local) is a normal function call, so the tenant id
 * can be bound properly. Runtime assertions pend DALT-0062.
 */

return [
    'rls_is_enabled' => [
        'type' => 'file_contains',
        'file' => 'database/migrations/003_enable_rls.sql',
        'search' => 'ENABLE ROW LEVEL SECURITY',
        'hint' => 'A policy has no effect until row-level security is switched on for the table.',
    ],

    'a_policy_exists' => [
        'type' => 'file_contains',
        'file' => 'database/migrations/003_enable_rls.sql',
        'search' => 'CREATE POLICY',
        'hint' => 'Define the rule that decides which rows a session may see.',
    ],

    'policy_reads_the_session_setting' => [
        'type' => 'file_contains',
        'file' => 'database/migrations/003_enable_rls.sql',
        'search' => 'current_setting',
        'hint' => "The policy must compare tenant_id against the per-session setting, e.g. current_setting('app.tenant_id', true)::INT.",
    ],

    // 'function_call' would not match here: set_config appears inside a SQL
    // string literal, not as a PHP call, so the token scan never sees it.
    'tenant_is_set_with_a_bound_parameter' => [
        'type' => 'file_contains',
        'file' => 'Http/controllers/tenant/posts.php',
        'search' => 'set_config(',
        'hint' => "Use SELECT set_config('app.tenant_id', :id, false) — SET cannot take a bound parameter, and interpolating the id into SQL reintroduces injection into the very feature meant to stop data leaks.",
    ],

    'php_no_longer_filters_by_tenant' => [
        'type' => 'file_not_contains',
        'file' => 'Http/controllers/tenant/posts.php',
        'search' => 'WHERE tenant_id =',
        'hint' => 'Once the database enforces isolation, the hand-written WHERE clause is the thing you are removing.',
    ],
];
