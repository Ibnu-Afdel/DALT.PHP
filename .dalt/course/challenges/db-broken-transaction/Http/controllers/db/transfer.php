<?php

$db  = \Core\App::resolve(\Core\Database::class);
$pdo = $db->getConnection();

$fromId = $_POST['from_id'] ?? null;
$toId   = $_POST['to_id']   ?? null;
$amount = (int)($_POST['amount'] ?? 0);

$pdo->beginTransaction();

$db->query(
    'UPDATE users SET credits = credits - :amount WHERE id = :id',
    ['amount' => $amount, 'id' => $fromId]
);

$db->query(
    'UPDATE users SET credits = credits + :amount WHERE id = :id',
    ['amount' => $amount, 'id' => $toId]
);

$pdo->commit();

return ['success' => true];
