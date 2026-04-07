<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');
csrf_init();

/* categories */
$cats = db()->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll();

$errors = [];
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid product id.");

/* product */
$stmt = db()->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) die("Product not found.");

$category_id  = (int)$p['category_id'];
$name         = $p['name'];
$price        = (float)$p['price'];
$stock        = (int)$p['stock'];
$description  = $p['description'] ?? '';
$status       = $p['status'] ?? 'active';
$currentImage = $p['main_image'] ?? null;

if (is_post()) {
    csrf_verify();

    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

    if ($category_id <= 0) $errors[] = "Category is required.";
    if ($name === '') $errors[] = "Product name is required.";
    if ($price <= 0) $errors[] = "Valid price is required.";
    if ($stock < 0) $errors[] = "Stock cannot be negative.";

    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $name), '-'));

    // Ensure unique slug except current product
    if (!$errors) {
        $chk = db()->prepare("SELECT id FROM products WHERE slug=? AND id != ? LIMIT 1");
        $chk->execute([$slug, $id]);
        if ($chk->fetch()) $slug .= '-' . time();
    }

    // Optional image update
    $imagePath = $currentImage;

    if (!empty($_FILES['image']['name'])) {
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 2 * 1024 * 1024;

        if (!in_array($_FILES['image']['type'], $allowedMime)) $errors[] = "Only JPG, PNG, WEBP allowed.";
        if ($_FILES['image']['size'] > $maxSize) $errors[] = "Image must be under 2MB.";

        if (!$errors) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $errors[] = "Invalid image extension.";
            } else {
                $uploadDir = __DIR__ . '/../public/uploads/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $newName = uniqid('prod_', true) . '.' . $ext;
                $target = $uploadDir . $newName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $errors[] = "Image upload failed.";
                } else {
                    // delete old file
                    if ($currentImage && file_exists(__DIR__ . '/../public/' . $currentImage)) {
                        @unlink(__DIR__ . '/../public/' . $currentImage);
                    }
                    $imagePath = 'uploads/products/' . $newName;
                }
            }
        }
    }

    if (!$errors) {
        $upd = db()->prepare("
            UPDATE products
            SET category_id=?, name=?, slug=?, description=?, price=?, stock=?, status=?, main_image=?
            WHERE id=?
        ");
        $upd->execute([
            $category_id,
            $name,
            $slug,
            $description ?: null,
            $price,
            $stock,
            $status,
            $imagePath,
            $id
        ]);

        redirect('/admin/products_list.php');
    }
}
?>

<h1 class="h4 fw-bold mb-3">Edit Product</h1>

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
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?= e($description) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Update Image (optional)</label>
      <input type="file" name="image" class="form-control" accept="image/*">
      <div class="form-text">Leave blank to keep existing image.</div>

      <?php if ($currentImage): ?>
        <div class="mt-2">
          <img src="<?= BASE_URL ?>/public/<?= e($currentImage) ?>" class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;">
        </div>
      <?php endif; ?>
    </div>

    <button class="btn btn-success">Update Product</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/products_list.php">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
