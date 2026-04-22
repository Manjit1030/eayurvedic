<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/algorithms.php';

require_login();
require_role('user');
csrf_init();

if (!is_post()) {
    http_response_code(405);
    die("Method not allowed");
}

csrf_verify();

$user = current_user();
$address_id = (int)($_POST['address_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if ($address_id <= 0) die("Please select an address.");

/* Validate address belongs to this user */
$stmt = db()->prepare("SELECT id FROM user_addresses WHERE id=? AND user_id=? LIMIT 1");
$stmt->execute([$address_id, $user['id']]);
if (!$stmt->fetch()) die("Invalid address.");

/* Get cart */
$stmt = db()->prepare("SELECT id FROM carts WHERE user_id=? LIMIT 1");
$stmt->execute([$user['id']]);
$cart = $stmt->fetch();
if (!$cart) die("Cart not found.");

$cart_id = (int)$cart['id'];

/* Load cart items */
$stmt = db()->prepare("
  SELECT ci.id AS cart_item_id, ci.qty, ci.price_at_time,
         p.id AS product_id, p.name, p.stock
  FROM cart_items ci
  JOIN products p ON p.id = ci.product_id
  WHERE ci.cart_id=?
");
$stmt->execute([$cart_id]);
$items = $stmt->fetchAll();

if (!$items) die("Cart is empty.");

/* Subtotal */
$subtotal = 0;
foreach ($items as $it) {
    $subtotal += ((float)$it['price_at_time'] * (int)$it['qty']);
}

/* ✅ Use Algorithm #1 here too */
$totals = algo_cart_totals($subtotal);

db()->beginTransaction();

try {
    /* Create order code */
    $order_code = 'ORD' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    /* Handle Payment Method */
    $payment_method = ($_POST['payment_method'] ?? 'cash') === 'khalti' ? 'khalti' : 'cash';
    $payment_status = $payment_method === 'khalti' ? 'paid' : 'unpaid';

    /* Insert order */
    $ins = db()->prepare("
      INSERT INTO orders
      (order_code, user_id, address_id, subtotal, discount_amount, shipping_amount, tax_amount, total_amount,
       payment_method, payment_status, order_status, notes)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $ins->execute([
        $order_code,
        $user['id'],
        $address_id,
        $totals['subtotal'],
        $totals['discount'],
        $totals['shipping'],
        $totals['tax'],
        $totals['total'],
        $payment_method,
        $payment_status,
        'pending',
        $notes ?: null
    ]);

    $order_id = (int)db()->lastInsertId();

    /* Insert order items + reduce stock safely */
    $oi = db()->prepare("
      INSERT INTO order_items (order_id, product_id, product_name, unit_price, qty, line_total)
      VALUES (?,?,?,?,?,?)
    ");

    $updStock = db()->prepare("UPDATE products SET stock = stock - ? WHERE id=? AND stock >= ?");

    foreach ($items as $it) {
        $qty = (int)$it['qty'];
        $unit = (float)$it['price_at_time'];
        $line = round($unit * $qty, 2);

        // Update stock
        $updStock->execute([$qty, $it['product_id'], $qty]);
        if ($updStock->rowCount() === 0) {
            throw new Exception("Insufficient stock for: " . $it['name']);
        }

        // Insert order item
        $oi->execute([$order_id, $it['product_id'], $it['name'], $unit, $qty, $line]);
    }

    /* Clear cart */
    $del = db()->prepare("DELETE FROM cart_items WHERE cart_id=?");
    $del->execute([$cart_id]);

    db()->commit();

    /* If Khalti, initiate payment */
    if ($payment_method === 'khalti') {
        redirect('/public/khalti_init.php?order_id=' . $order_id);
    }

    redirect('/public/order_success.php?code=' . urlencode($order_code));
} catch (Exception $e) {
    db()->rollBack();
    die("Order failed: " . e($e->getMessage()));
}
