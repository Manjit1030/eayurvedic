<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('admin');
csrf_init();

$errors = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Invalid category ID");
}

$stmt = db()->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    die("Category not found");
}

$name = $category['name'];
$description = $category['description'];
$status = $category['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = ($_POST['status'] === 'inactive') ? 'inactive' : 'active';

    if ($name === '') {
        $errors[] = "Category name is required";
    }

    if (!$errors) {
        $check = db()->prepare("SELECT id FROM categories WHERE name = ? AND id != ? LIMIT 1");
        $check->execute([$name, $id]);

        if ($check->fetch()) {
            $errors[] = "Category name already exists";
        }
    }

    if (!$errors) {
        $update = db()->prepare("
             UPDATE categories
             SET name = ?, description = ?, status = ?
             WHERE id = ?
        ");
        $update->execute([
            $name,
            $description ?: null,
            $status,
            $id
        ]);

        redirect('/admin/categories_list.php');
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Edit Category</h1>
    <p class="ea-page-subtitle">Update the category details without affecting the underlying product and store logic.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/categories_list.php">Back to Categories</a>
  </div>
</section>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= e($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="ea-form-card">
  <div class="ea-form-body">
    <?= csrf_field() ?>

    <div class="ea-form-section">
      <h2 class="ea-form-title">Category Information</h2>
      <p class="ea-form-help">Keep the category title and status clear so it remains easy to use in product administration.</p>

      <div class="mb-3">
        <label class="form-label fw-semibold">Category Name *</label>
        <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" class="form-control" rows="4"><?= e($description) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select">
          <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div class="ea-form-actions">
      <button class="btn btn-success">Update Category</button>
      <a href="<?= BASE_URL ?>/admin/categories_list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
