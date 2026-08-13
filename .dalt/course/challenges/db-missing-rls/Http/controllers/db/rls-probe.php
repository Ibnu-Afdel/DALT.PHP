<?php

/**
 * Platform-authored, never routed (see routes/routes.php — nothing points here).
 * Exists solely so handler_result has a controller to execute for the one check
 * that matters most in this challenge: proving isolation lives in the database,
 * not in tenant/posts.php's own WHERE clause.
 *
 * Deliberately does not call set_config and does not filter by tenant_id in SQL.
 * A real per-tenant read on any *other* endpoint over this table — one that
 * forgets the hand-written filter tenant/posts.php happens to have today — looks
 * exactly like this: a bare SELECT relying entirely on whatever the database
 * enforces on its own. See DECISIONS.md D-09.
 */

$db = \Core\App::resolve(\Core\Database::class);

$rows = $db->query('SELECT tenant_id, title FROM posts')->get();

return ['rows' => $rows];
