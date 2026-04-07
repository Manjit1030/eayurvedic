<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

auth_init();
csrf_init();

$u = current_user();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid product");

$stmt = db()->prepare("
  SELECT p.*, c.name AS category_name
  FROM products p
  JOIN categories c ON c.id = p.category_id
  WHERE p.id = ? AND p.status='active'
  LIMIT 1
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) die("Product not found");

$stock = (int)$p['stock'];
?>

<div class="row g-4">
  <div class="col-md-5">
    <?php if (!empty($p['main_image'])): ?>
      <img src="<?= BASE_URL ?>/public/<?= e($p['main_image']) ?>"
           class="img-fluid rounded shadow-sm" alt="Product">
    <?php endif; ?>
  </div>

  <div class="col-md-7">
    <h1 class="h4 fw-bold mb-2"><?= e($p['name']) ?></h1>

    <div class="mb-2">
      <span class="badge text-bg-success"><?= e($p['category_name']) ?></span>
    </div>

    <p class="fw-bold fs-5 mb-2">NPR <?= e($p['price']) ?></p>
    <p class="text-muted mb-3">Stock: <?= $stock ?></p>

    <h6 class="fw-bold">Description</h6>
    <p><?= nl2br(e($p['description'] ?? '')) ?></p>

    <div class="d-flex gap-2 mt-3 flex-wrap">
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/public/shop.php">
        <i class="bi bi-arrow-left"></i> Back to Shop
      </a>

      <?php if ($stock <= 0): ?>
        <button class="btn btn-secondary" disabled>
          <i class="bi bi-x-circle"></i> Out of Stock
        </button>

      <?php else: ?>
        <?php if (!$u): ?>
          <a class="btn btn-success" href="<?= BASE_URL ?>/public/login.php">
            <i class="bi bi-box-arrow-in-right"></i> Login to Buy
          </a>

        <?php elseif (($u['role'] ?? '') !== 'user'): ?>
          <button class="btn btn-secondary" disabled>
            Only users can purchase
          </button>

        <?php else: ?>
          <form method="post" action="<?= BASE_URL ?>/public/cart_add.php" class="d-flex gap-2 align-items-center">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

            <input type="number"
                   name="qty"
                   min="1"
                   max="<?= $stock ?>"
                   value="1"
                   class="form-control"
                   style="width: 110px;"
                   required>

            <button class="btn btn-success">
              <i class="bi bi-cart-plus"></i> Add to Cart
            </button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
