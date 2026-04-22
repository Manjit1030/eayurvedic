<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('admin');
csrf_init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method not allowed");
}

csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) die("Invalid product id.");

$stmt = db()->prepare("SELECT main_image FROM products WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    $_SESSION['product_error_message'] = 'Product not found.';
    redirect('/admin/products_list.php');
}

$orderCheck = db()->prepare("SELECT COUNT(*) FROM order_items WHERE product_id=?");
$orderCheck->execute([$id]);
$orderCount = (int)$orderCheck->fetchColumn();

if ($orderCount > 0) {
    $_SESSION['product_error_message'] = 'This product cannot be deleted because it is already used in order history.';
    redirect('/admin/products_list.php');
}

$cartCheck = db()->prepare("SELECT COUNT(*) FROM cart_items WHERE product_id=?");
$cartCheck->execute([$id]);
$cartCount = (int)$cartCheck->fetchColumn();

if ($cartCount > 0) {
    $_SESSION['product_error_message'] = 'This product cannot be deleted because it is currently present in user carts.';
    redirect('/admin/products_list.php');
}

try {
    $del = db()->prepare("DELETE FROM products WHERE id=?");
    $del->execute([$id]);
    $_SESSION['product_success_message'] = 'Product deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['product_error_message'] = 'Unable to delete this product right now.';
    redirect('/admin/products_list.php');
}

if ($p && !empty($p['main_image'])) {
    $file = __DIR__ . '/../public/' . $p['main_image'];
    if (file_exists($file)) @unlink($file);
}

redirect('/admin/products_list.php');
