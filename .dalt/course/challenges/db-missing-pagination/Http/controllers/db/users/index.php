<?php

$db = \Core\App::resolve(\Core\Database::class);

// No pagination — returns every user in the table.
// On a large dataset this exhausts memory and response time.
return $db->query(
    'SELECT id, name, email, created_at FROM users ORDER BY created_at DESC'
)->get();
