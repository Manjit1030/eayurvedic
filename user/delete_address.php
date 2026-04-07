<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('user');
csrf_init();

$user = current_user();

if (!is_post()) {
    http_response_code(405);
    die("Method not allowed.");
}

csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    die("Invalid id.");
}

$stmt = db()->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user['id']]);

redirect('/user/addresses.php');
