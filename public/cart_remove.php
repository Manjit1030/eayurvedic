<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('user');
csrf_init();

if (!is_post()) {
    http_response_code(405);
    die("Method not allowed");
}

csrf_verify();

$user = current_user();
$cart_item_id = (int)($_POST['cart_item_id'] ?? 0);

$stmt = db()->prepare("
  DELETE ci
  FROM cart_items ci
  JOIN carts c ON c.id = ci.cart_id
  WHERE ci.id = ? AND c.user_id = ?
");
$stmt->execute([$cart_item_id, $user['id']]);

redirect('/public/cart.php');
