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

$stmt = db()->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user['id']]);
$addresses = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 fw-bold mb-0">My Addresses</h2>
  <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/user/add_address.php">+ Add Address</a>
</div>

<?php if (!$addresses): ?>
  <div class="alert alert-info">No addresses found. Add one first.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Label</th>
          <th>Province</th>
          <th>District</th>
          <th>City</th>
          <th>Area</th>
          <th>Street</th>
          <th>Postal</th>
          <th style="width: 180px;">Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($addresses as $a): ?>
          <tr>
            <td><?= e($a['label'] ?? '') ?></td>
            <td><?= e($a['province'] ?? '') ?></td>
            <td><?= e($a['district'] ?? '') ?></td>
            <td><?= e($a['city'] ?? '') ?></td>
            <td><?= e($a['area'] ?? '') ?></td>
            <td><?= e($a['street'] ?? '') ?></td>
            <td><?= e($a['postal_code'] ?? '') ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary"
                 href="<?= BASE_URL ?>/user/edit_address.php?id=<?= (int)$a['id'] ?>">
                Edit
              </a>

              <form method="post"
                    action="<?= BASE_URL ?>/user/delete_address.php"
                    class="d-inline"
                    onsubmit="return confirm('Delete this address?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>

    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
