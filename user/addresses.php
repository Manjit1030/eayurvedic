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

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">User Panel</div>
    <h1 class="ea-page-title">My Addresses</h1>
    <p class="ea-page-subtitle">Manage delivery addresses used during checkout and order placement.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-success" href="<?= BASE_URL ?>/user/add_address.php"><i class="bi bi-plus-circle me-1"></i>Add Address</a>
  </div>
</section>

<?php if (!$addresses): ?>
  <div class="ea-empty-state">
    <span class="ea-icon-pill"><i class="bi bi-geo-alt"></i></span>
    <h3>No addresses found</h3>
    <p>Add a delivery address so your checkout process is ready when you place an order.</p>
  </div>
<?php else: ?>
  <div class="ea-table-wrap">
    <div class="table-responsive shadow-none">
      <table class="table ea-table align-middle mb-0">
        <thead>
          <tr>
            <th>Label</th>
            <th>Province</th>
            <th>District</th>
            <th>City</th>
            <th>Area</th>
            <th>Street</th>
            <th>Postal</th>
            <th style="width: 190px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($addresses as $a): ?>
            <tr>
              <td class="fw-semibold"><?= e($a['label'] ?? '') ?></td>
              <td><?= e($a['province'] ?? '') ?></td>
              <td><?= e($a['district'] ?? '') ?></td>
              <td><?= e($a['city'] ?? '') ?></td>
              <td><?= e($a['area'] ?? '') ?></td>
              <td><?= e($a['street'] ?? '') ?></td>
              <td><?= e($a['postal_code'] ?? '') ?></td>
              <td>
                <div class="ea-actions">
                  <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/user/edit_address.php?id=<?= (int)$a['id'] ?>">Edit</a>
                  <form method="post" action="<?= BASE_URL ?>/user/delete_address.php" onsubmit="return confirm('Delete this address?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
