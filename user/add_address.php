<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');
csrf_init();

$user = current_user();
$errors = [];

if (is_post()) {
    csrf_verify();

    $label = trim($_POST['label'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');

    if ($province === '' || $district === '' || $city === '') {
        $errors[] = "Province, district and city are required.";
    }

    if (!$errors) {
        $stmt = db()->prepare("
            INSERT INTO user_addresses
            (user_id, label, province, district, city, area, street, postal_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'], $label, $province, $district,
            $city, $area, $street, $postal_code
        ]);

        redirect('/user/index.php');
    }
}
?>

<h2 class="h5 fw-bold mb-3">Add Delivery Address</h2>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="post">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label">Label (Home / Office)</label>
    <input type="text" name="label" class="form-control">
  </div>

  <div class="mb-3">
    <label class="form-label">Province *</label>
    <input type="text" name="province" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">District *</label>
    <input type="text" name="district" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">City *</label>
    <input type="text" name="city" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Area</label>
    <input type="text" name="area" class="form-control">
  </div>

  <div class="mb-3">
    <label class="form-label">Street</label>
    <input type="text" name="street" class="form-control">
  </div>

  <div class="mb-3">
    <label class="form-label">Postal Code</label>
    <input type="text" name="postal_code" class="form-control">
  </div>

  <button class="btn btn-success">Save Address</button>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
