<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();            // must be logged in to add
require_role('user');
csrf_init();

if (!is_post()) {
    http_response_code(405);
    die("Method not allowed");
}

csrf_verify();

$user = current_user();
$product_id = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 1);
if ($qty <= 0) $qty = 1;

if ($product_id <= 0) die("Invalid product");

/* Check product exists & active */
$stmt = db()->prepare("SELECT id, price, stock FROM products WHERE id=? AND status='active' LIMIT 1");
$stmt->execute([$product_id]);
$p = $stmt->fetch();
if (!$p) die("Product not found");

if ($qty > (int)$p['stock']) {
    $qty = (int)$p['stock'];
    if ($qty <= 0) die("Out of stock");
}

/* Ensure cart exists */
$stmt = db()->prepare("SELECT id FROM carts WHERE user_id=? LIMIT 1");
$stmt->execute([$user['id']]);
$cart = $stmt->fetch();

if (!$cart) {
    $ins = db()->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $ins->execute([$user['id']]);
    $cart_id = (int)db()->lastInsertId();
} else {
    $cart_id = (int)$cart['id'];
}

/* If item exists, update qty; else insert */
$stmt = db()->prepare("SELECT id, qty FROM cart_items WHERE cart_id=? AND product_id=? LIMIT 1");
$stmt->execute([$cart_id, $product_id]);
$item = $stmt->fetch();

if ($item) {
    $newQty = (int)$item['qty'] + $qty;
    if ($newQty > (int)$p['stock']) $newQty = (int)$p['stock'];

    $upd = db()->prepare("UPDATE cart_items SET qty=?, price_at_time=? WHERE id=?");
    $upd->execute([$newQty, $p['price'], $item['id']]);
} else {
    $ins = db()->prepare("INSERT INTO cart_items (cart_id, product_id, qty, price_at_time) VALUES (?,?,?,?)");
    $ins->execute([$cart_id, $product_id, $qty, $p['price']]);
}

redirect('/public/cart.php');
