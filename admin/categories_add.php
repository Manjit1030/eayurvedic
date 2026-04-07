<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

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
        // unique check
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
?>

<h1 class="h4 fw-bold mb-3">Add Category</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="card shadow-sm">
  <div class="card-body">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label class="form-label">Category Name *</label>
      <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?= e($description) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>

    <button class="btn btn-success">Save Category</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/categories_list.php">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
