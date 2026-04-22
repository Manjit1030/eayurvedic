<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('admin');
csrf_init();

$errors = [];
$name = '';
$description = '';
$status = 'active';

if (is_post()) {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($name === '') $errors[] = "Category name is required.";

    if (!$errors) {
        $chk = db()->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
        $chk->execute([$name]);
        if ($chk->fetch()) {
            $errors[] = "Category name already exists.";
        } else {
            $ins = db()->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, ?)");
            $ins->execute([$name, $description ?: null, $status]);

            redirect('/admin/categories_list.php');
        }
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Add Category</h1>
    <p class="ea-page-subtitle">Create a category for medicines and keep the catalog structured and easy to manage.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/categories_list.php">Back to Categories</a>
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
      <h2 class="ea-form-title">Category Information</h2>
      <p class="ea-form-help">Use short, clear names so the category is easy to recognize throughout the store and dashboard.</p>

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
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div class="ea-form-actions">
      <button class="btn btn-success">Save Category</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/categories_list.php">Cancel</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
