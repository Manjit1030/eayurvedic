<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');
csrf_init();

$products = db()->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    ORDER BY p.id DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 fw-bold mb-0">Products</h1>
  <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/admin/products_add.php">+ Add Product</a>
</div>

<?php if (!$products): ?>
  <div class="alert alert-info">No products added yet.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
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
            <td style="width:80px;">
              <?php if (!empty($p['main_image'])): ?>
                <img src="<?= BASE_URL ?>/public/<?= e($p['main_image']) ?>"
                     class="img-thumbnail"
                     style="width:60px;height:60px;object-fit:cover;"
                     alt="Product">
              <?php else: ?>
                <span class="text-muted small">No Image</span>
              <?php endif; ?>
            </td>
            <td><?= e($p['name']) ?></td>
            <td><?= e($p['category_name']) ?></td>
            <td>NPR <?= e($p['price']) ?></td>
            <td><?= (int)$p['stock'] ?></td>
            <td><?= e($p['status']) ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary"
                 href="<?= BASE_URL ?>/admin/products_edit.php?id=<?= (int)$p['id'] ?>">
                Edit
              </a>

              <form method="post" action="<?= BASE_URL ?>/admin/products_delete.php"
                    class="d-inline"
                    onsubmit="return confirm('Delete this product?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
