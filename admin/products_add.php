<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');
csrf_init();

/* Fetch active categories */
$cats = db()->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll();

$errors = [];
$name = '';
$price = '';
$stock = '';
$category_id = 0;
$description = '';

if (is_post()) {
    csrf_verify();

    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($category_id <= 0) $errors[] = "Category is required.";
    if ($name === '') $errors[] = "Product name is required.";
    if ($price <= 0) $errors[] = "Valid price is required.";
    if ($stock < 0) $errors[] = "Stock cannot be negative.";

    /* Slug (basic) */
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $name), '-'));

    /* Ensure unique slug */
    if (!$errors) {
        $chk = db()->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
        $chk->execute([$slug]);
        if ($chk->fetch()) {
            $slug .= '-' . time();
        }
    }

    /* Image upload (safe) */
    $imagePath = null;

    if (empty($_FILES['image']['name'])) {
        $errors[] = "Product image is required.";
    } else {
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['image']['type'], $allowedMime)) {
            $errors[] = "Only JPG, PNG, WEBP images are allowed.";
        }

        if ($_FILES['image']['size'] > $maxSize) {
            $errors[] = "Image must be under 2MB.";
        }

        if (!$errors) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $errors[] = "Invalid image extension.";
            } else {
                $uploadDir = __DIR__ . '/../public/uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newName = uniqid('prod_', true) . '.' . $ext;
                $target = $uploadDir . $newName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $errors[] = "Image upload failed. Check folder permissions.";
                } else {
                    $imagePath = 'uploads/products/' . $newName; // stored relative to /public
                }
            }
        }
    }

    /* Insert product */
    if (!$errors) {
        $stmt = db()->prepare("
            INSERT INTO products
            (category_id, name, slug, description, price, stock, status, main_image)
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?)
        ");

        $stmt->execute([
            $category_id,
            $name,
            $slug,
            $description ?: null,
            $price,
            $stock,
            $imagePath
        ]);

        redirect('/admin/products_list.php');
    }
}
?>

<h1 class="h4 fw-bold mb-3">Add Product (Medicine)</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card shadow-sm">
  <div class="card-body">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label class="form-label">Category *</label>
      <select name="category_id" class="form-select" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $category_id==(int)$c['id']?'selected':'' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Medicine Name *</label>
      <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Price (NPR) *</label>
      <input type="number" step="0.01" name="price" class="form-control" value="<?= e((string)$price) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Stock *</label>
      <input type="number" name="stock" class="form-control" value="<?= e((string)$stock) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?= e($description) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Product Image *</label>
      <input type="file" name="image" class="form-control" accept="image/*" required>
      <div class="form-text">JPG, PNG, WEBP only (max 2MB)</div>
    </div>

    <button class="btn btn-success">Save Product</button>
    <a href="<?= BASE_URL ?>/admin/products_list.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
