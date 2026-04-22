<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');
csrf_init();

$stmt = db()->query("SELECT * FROM categories ORDER BY id DESC");
$cats = $stmt->fetchAll();
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Categories</h1>
    <p class="ea-page-subtitle">Manage the herbal product categories used across the store and admin product forms.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-success" href="<?= BASE_URL ?>/admin/categories_add.php"><i class="bi bi-plus-circle me-1"></i>Add Category</a>
  </div>
</section>

<?php if (isset($_GET['err']) && $_GET['err'] === 'has_products'): ?>
  <div class="alert alert-warning">Cannot delete this category because products are linked to it.</div>
<?php endif; ?>

<?php if (!$cats): ?>
  <div class="ea-empty-state">
    <span class="ea-icon-pill"><i class="bi bi-grid-3x3-gap"></i></span>
    <h3>No categories yet</h3>
    <p>Create your first category to organize medicines in the store.</p>
  </div>
<?php else: ?>
  <div class="ea-table-wrap">
    <div class="table-responsive shadow-none">
      <table class="table ea-table align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Created</th>
            <th style="width: 220px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cats as $c): ?>
            <tr>
              <td><?= (int)$c['id'] ?></td>
              <td class="fw-semibold"><?= e($c['name']) ?></td>
              <td>
                <span class="badge <?= ($c['status'] ?? '') === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                  <?= e($c['status']) ?>
                </span>
              </td>
              <td class="ea-meta"><?= e($c['created_at']) ?></td>
              <td>
                <div class="ea-actions">
                  <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/admin/categories_edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
                  <form method="post" action="<?= BASE_URL ?>/admin/categories_delete.php" onsubmit="return confirm('Delete this category?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
