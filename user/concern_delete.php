<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('user');
csrf_init();

if (!is_post()) {
  redirect('/user/concerns_list.php');
}

csrf_verify();

$u = current_user();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) die("Invalid id.");

$del = db()->prepare("DELETE FROM patient_concerns WHERE id=? AND user_id=?");
$del->execute([$id, $u['id']]);

redirect('/user/concerns_list.php');
