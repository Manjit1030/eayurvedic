<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('user');
csrf_init();

$user = current_user();
$errors = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid address id.");

$stmt = db()->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user['id']]);
$address = $stmt->fetch();

if (!$address) {
    die("Address not found.");
}

$label = $address['label'] ?? '';
$province = $address['province'] ?? '';
$district = $address['district'] ?? '';
$city = $address['city'] ?? '';
$area = $address['area'] ?? '';
$street = $address['street'] ?? '';
$postal_code = $address['postal_code'] ?? '';

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
        $upd = db()->prepare("
            UPDATE user_addresses
            SET label=?, province=?, district=?, city=?, area=?, street=?, postal_code=?
            WHERE id=? AND user_id=?
        ");
        $upd->execute([$label, $province, $district, $city, $area, $street, $postal_code, $id, $user['id']]);

        redirect('/user/addresses.php');
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">User Panel</div>
    <h1 class="ea-page-title">Edit Address</h1>
    <p class="ea-page-subtitle">Update your delivery details so future orders reach the correct location.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/user/addresses.php">Back to Addresses</a>
  </div>
</section>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="ea-form-card">
  <div class="ea-form-body">
    <?= csrf_field() ?>

    <div class="ea-form-section">
      <h2 class="ea-form-title">Address Details</h2>
      <p class="ea-form-help">Make the necessary corrections and save the updated address information.</p>

      <div class="mb-3">
        <label class="form-label fw-semibold">Label</label>
        <input type="text" name="label" class="form-control" value="<?= e($label) ?>">
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Province *</label>
          <input type="text" name="province" class="form-control" value="<?= e($province) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">District *</label>
          <input type="text" name="district" class="form-control" value="<?= e($district) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">City *</label>
          <input type="text" name="city" class="form-control" value="<?= e($city) ?>" required>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Area</label>
          <input type="text" name="area" class="form-control" value="<?= e($area) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Street</label>
          <input type="text" name="street" class="form-control" value="<?= e($street) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Postal Code</label>
          <input type="text" name="postal_code" class="form-control" value="<?= e($postal_code) ?>">
        </div>
      </div>
    </div>

    <div class="ea-form-actions">
      <button class="btn btn-success">Update Address</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/user/addresses.php">Cancel</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
