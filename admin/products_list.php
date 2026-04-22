<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');
csrf_init();

$successMessage = $_SESSION['product_success_message'] ?? null;
$errorMessage = $_SESSION['product_error_message'] ?? null;
unset($_SESSION['product_success_message'], $_SESSION['product_error_message']);

$products = db()->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    ORDER BY p.id DESC
")->fetchAll();
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Products</h1>
    <p class="ea-page-subtitle">Manage medicines, category assignment, image thumbnails, pricing, stock, and availability.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-success" href="<?= BASE_URL ?>/admin/products_add.php"><i class="bi bi-plus-circle me-1"></i>Add Product</a>
  </div>
</section>

<?php if ($successMessage): ?>
  <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage): ?>
  <div class="alert alert-danger"><?= e($errorMessage) ?></div>
<?php endif; ?>

<?php if (!$products): ?>
  <div class="ea-empty-state">
    <span class="ea-icon-pill"><i class="bi bi-capsule-pill"></i></span>
    <h3>No products added yet</h3>
    <p>Create your first medicine entry to start building the online store catalog.</p>
  </div>
<?php else: ?>
  <div class="ea-table-wrap">
    <div class="table-responsive shadow-none">
      <table class="table ea-table align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th style="width:220px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td>
                <?php if (!empty($p['main_image'])): ?>
                  <img src="<?= BASE_URL ?>/public/<?= e($p['main_image']) ?>" class="ea-thumb" alt="Product">
                <?php else: ?>
                  <span class="ea-thumb-placeholder"><i class="bi bi-image"></i></span>
                <?php endif; ?>
              </td>
              <td class="fw-semibold"><?= e($p['name']) ?></td>
              <td><?= e($p['category_name']) ?></td>
              <td class="fw-semibold">NPR <?= e($p['price']) ?></td>
              <td><?= (int)$p['stock'] ?></td>
              <td>
                <span class="badge <?= ($p['status'] ?? '') === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                  <?= e($p['status']) ?>
                </span>
              </td>
              <td>
                <div class="ea-actions">
                  <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/admin/products_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
                  <form method="post" action="<?= BASE_URL ?>/admin/products_delete.php" onsubmit="return confirm('Delete this product?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
