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

$del = db()->prepare("DELETE FROM products WHERE id=?");
$del->execute([$id]);

if ($p && !empty($p['main_image'])) {
    $file = __DIR__ . '/../public/' . $p['main_image'];
    if (file_exists($file)) @unlink($file);
}

redirect('/admin/products_list.php');
