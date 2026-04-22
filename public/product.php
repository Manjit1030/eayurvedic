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

<style>
  .product-shell {
    background: #fff;
    border-radius: 28px;
    box-shadow: var(--ea-shadow);
    border: 1px solid rgba(26, 71, 42, 0.08);
    overflow: hidden;
  }

  .product-media,
  .product-media-placeholder {
    min-height: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(201,168,76,0.16), rgba(26,71,42,0.08));
  }

  .product-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    min-height: 420px;
  }

  .product-price {
    color: var(--ea-gold);
    font-size: 1.7rem;
    font-weight: 700;
  }

  .product-stock {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 999px;
    padding: 0.55rem 0.95rem;
    background: rgba(26, 71, 42, 0.08);
    color: var(--ea-forest);
    font-weight: 500;
  }

  .product-detail-box {
    background: #fcfbf8;
    border: 1px solid rgba(26, 71, 42, 0.08);
    border-radius: 18px;
  }

  .product-qty {
    max-width: 120px;
  }
</style>

<section class="mb-4">
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/public/shop.php">
    <i class="bi bi-arrow-left me-1"></i>Back to Shop
  </a>
</section>

<section class="product-shell">
  <div class="row g-0">
    <div class="col-lg-5">
      <?php if (!empty($p['main_image'])): ?>
        <div class="product-media">
          <img src="<?= BASE_URL ?>/public/<?= e($p['main_image']) ?>" alt="Product">
        </div>
      <?php else: ?>
        <div class="product-media-placeholder d-flex align-items-center justify-content-center">
          <i class="bi bi-capsule-pill" style="font-size:4rem;color:var(--ea-gold);"></i>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-7">
      <div class="p-4 p-lg-5">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <span class="badge rounded-pill" style="background:rgba(201,168,76,0.18);color:var(--ea-forest);"><?= e($p['category_name']) ?></span>
          <span class="product-stock"><i class="bi bi-box-seam"></i>Stock: <?= $stock ?></span>
        </div>

        <h1 class="mb-3" style="font-size:clamp(2.3rem,4vw,3.8rem);"><?= e($p['name']) ?></h1>
        <div class="product-price mb-4">NPR <?= e($p['price']) ?></div>

        <div class="product-detail-box p-4 mb-4">
          <h2 class="h3 mb-3">Product Details</h2>
          <p class="ea-subtle mb-0"><?= nl2br(e($p['description'] ?? '')) ?></p>
        </div>

        <div class="d-flex gap-2 mt-3 flex-wrap">
          <?php if ($stock <= 0): ?>
            <button class="btn btn-secondary btn-lg w-100" disabled>
              <i class="bi bi-x-circle me-1"></i>Out of Stock
            </button>

          <?php else: ?>
            <?php if (!$u): ?>
              <a class="btn btn-success btn-lg w-100" href="<?= BASE_URL ?>/public/login.php">
                <i class="bi bi-box-arrow-in-right me-1"></i>Login to Buy
              </a>

            <?php elseif (($u['role'] ?? '') !== 'user'): ?>
              <button class="btn btn-secondary btn-lg w-100" disabled>
                Only users can purchase
              </button>

            <?php else: ?>
              <form method="post" action="<?= BASE_URL ?>/public/cart_add.php" class="w-100">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                <div class="d-flex flex-column flex-sm-row gap-3 align-items-stretch">
                  <input type="number"
                         name="qty"
                         min="1"
                         max="<?= $stock ?>"
                         value="1"
                         class="form-control product-qty"
                         required>

                  <button class="btn btn-success btn-lg flex-grow-1">
                    <i class="bi bi-cart-plus me-1"></i>Add to Cart
                  </button>
                </div>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
