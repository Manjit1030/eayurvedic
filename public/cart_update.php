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
$qty = (int)($_POST['qty'] ?? 1);
if ($qty < 1) $qty = 1;

/* Ensure item belongs to this user */
$stmt = db()->prepare("
  SELECT ci.id, p.stock
  FROM cart_items ci
  JOIN carts c ON c.id = ci.cart_id
  JOIN products p ON p.id = ci.product_id
  WHERE ci.id = ? AND c.user_id = ?
  LIMIT 1
");
$stmt->execute([$cart_item_id, $user['id']]);
$row = $stmt->fetch();
if (!$row) die("Invalid cart item");

$maxStock = (int)$row['stock'];
if ($maxStock <= 0) $qty = 1;
if ($qty > $maxStock) $qty = $maxStock;

$upd = db()->prepare("UPDATE cart_items SET qty=? WHERE id=?");
$upd->execute([$qty, $cart_item_id]);

redirect('/public/cart.php');
