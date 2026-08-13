<?php

use Core\App;
use Core\Database;
use Core\Response;

$db = App::resolve(Database::class);
$code = $_POST['code'] ?? '';

$coupon = $db->query('SELECT * FROM coupons WHERE code = :code', ['code' => $code])->find();

if ($coupon === false) {
    return Response::json(['redeemed' => false, 'error' => 'Unknown coupon code.'], 404);
}

// BUG: nothing here checks whether $coupon['times_redeemed'] is already
// greater than zero before incrementing it again — an already-used coupon
// is silently accepted, and re-accepted, and accepted again.
$db->query('UPDATE coupons SET times_redeemed = times_redeemed + 1 WHERE code = :code', ['code' => $code]);

return Response::json(['redeemed' => true]);
