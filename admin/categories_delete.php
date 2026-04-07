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
if ($id <= 0) {
    die("Invalid category id");
}

try {
    $stmt = db()->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
} catch (PDOException $e) {
    // Probably FK restriction: products exist in this category
    redirect('/admin/categories_list.php?err=has_products');
}

redirect('/admin/categories_list.php');
