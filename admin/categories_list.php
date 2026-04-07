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

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 fw-bold mb-0">Categories</h1>
  <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/admin/categories_add.php">+ Add Category</a>
</div>

<?php if (isset($_GET['err']) && $_GET['err'] === 'has_products'): ?>
  <div class="alert alert-warning">
    Cannot delete this category because products are linked to it.
  </div>
<?php endif; ?>

<?php if (!$cats): ?>
  <div class="alert alert-info">No categories yet.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Status</th>
          <th>Created</th>
          <th style="width: 200px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cats as $c): ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><?= e($c['name']) ?></td>
            <td><?= e($c['status']) ?></td>
            <td><?= e($c['created_at']) ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary"
                 href="<?= BASE_URL ?>/admin/categories_edit.php?id=<?= (int)$c['id'] ?>">
                Edit
              </a>

              <form method="post"
                    action="<?= BASE_URL ?>/admin/categories_delete.php"
                    class="d-inline"
                    onsubmit="return confirm('Delete this category?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
