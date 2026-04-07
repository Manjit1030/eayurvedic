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

/* 1️⃣ Get ID safely */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Invalid category ID");
}

/* 2️⃣ Fetch category */
$stmt = db()->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    die("Category not found");
}

/* 3️⃣ Assign existing values */
$name = $category['name'];
$description = $category['description'];
$status = $category['status'];

/* 4️⃣ Handle form submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = ($_POST['status'] === 'inactive') ? 'inactive' : 'active';

    if ($name === '') {
        $errors[] = "Category name is required";
    }

    /* Unique name check (except this ID) */
    if (!$errors) {
        $check = db()->prepare(
            "SELECT id FROM categories WHERE name = ? AND id != ? LIMIT 1"
        );
        $check->execute([$name, $id]);

        if ($check->fetch()) {
            $errors[] = "Category name already exists";
        }
    }

    /* Update */
    if (!$errors) {
        $update = db()->prepare(
            "UPDATE categories 
             SET name = ?, description = ?, status = ? 
             WHERE id = ?"
        );
        $update->execute([
            $name,
            $description ?: null,
            $status,
            $id
        ]);

        redirect('/admin/categories_list.php');
    }
}
?>

<h1 class="h4 fw-bold mb-3">Edit Category</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= e($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="card shadow-sm">
  <div class="card-body">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label class="form-label">Category Name *</label>
      <input type="text"
             name="name"
             class="form-control"
             value="<?= e($name) ?>"
             required>
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description"
                class="form-control"
                rows="3"><?= e($description) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>
          Active
        </option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>
          Inactive
        </option>
      </select>
    </div>

    <button class="btn btn-success">Update Category</button>
    <a href="<?= BASE_URL ?>/admin/categories_list.php"
       class="btn btn-secondary">
       Cancel
    </a>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
