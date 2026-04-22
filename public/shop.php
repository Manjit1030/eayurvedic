<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

auth_init();
csrf_init();

$u = current_user();

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);

/* Category dropdown */
$cats = db()->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll();

/* Build SQL */
$sql = "
  SELECT p.*, c.name AS category_name
  FROM products p
  JOIN categories c ON c.id = p.category_id
  WHERE p.status='active'
";
$params = [];

if ($cat > 0) {
    $sql .= " AND p.category_id = ? ";
    $params[] = $cat;
}

if ($q !== '') {
    $sql .= " AND (
        LOWER(p.name) LIKE ? OR
        LOWER(COALESCE(p.description,'')) LIKE ? OR
        LOWER(COALESCE(p.tags,'')) LIKE ?
    ) ";
    $like = "%" . mb_strtolower($q) . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY p.id DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<style>
  .shop-shell {
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    gap: 1.75rem;
    align-items: start;
  }

  .shop-sidebar,
  .shop-toolbar,
  .shop-product-card {
    background: #fff;
    border: 1px solid rgba(26, 71, 42, 0.08);
    border-radius: 22px;
    box-shadow: var(--ea-shadow);
  }

  .shop-sidebar {
    padding: 1.5rem;
    position: sticky;
    top: 110px;
  }

  .shop-category-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-decoration: none;
    color: var(--ea-text);
    padding: 0.9rem 1rem;
    border-radius: 14px;
    background: #fcfbf8;
    border: 1px solid rgba(26, 71, 42, 0.06);
    transition: all 0.2s ease;
  }

  .shop-category-link:hover,
  .shop-category-link.active {
    background: rgba(26, 71, 42, 0.06);
    color: var(--ea-forest);
    transform: translateX(3px);
  }

  .shop-toolbar {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .shop-search {
    border-width: 1px;
  }

  .shop-search:focus {
    border-color: var(--ea-forest);
    box-shadow: 0 0 0 0.22rem rgba(26, 71, 42, 0.12);
  }

  .shop-product-card {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
    height: 100%;
  }

  .shop-product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 40px rgba(18, 49, 29, 0.10);
  }

  .shop-product-image,
  .shop-product-placeholder {
    height: 180px;
    object-fit: cover;
    background: linear-gradient(135deg, rgba(201,168,76,0.16), rgba(26,71,42,0.08));
  }

  .shop-price {
    color: var(--ea-forest);
    font-weight: 700;
    font-size: 1.15rem;
  }

  .shop-category-badge {
    background: rgba(201, 168, 76, 0.18);
    color: var(--ea-forest);
  }

  .shop-qty-input {
    max-width: 90px;
  }

  .shop-product-body {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    padding: 1.5rem;
  }

  .shop-product-summary {
    flex: 1 1 auto;
  }

  .shop-card-actions {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(26, 71, 42, 0.08);
  }

  .shop-add-form {
    display: flex;
    gap: 0.75rem;
    align-items: center;
  }

  .shop-add-form .btn {
    flex: 1 1 auto;
  }

  @media (max-width: 575.98px) {
    .shop-add-form {
      flex-direction: column;
      align-items: stretch;
    }

    .shop-qty-input {
      max-width: none;
    }
  }

  @media (max-width: 991.98px) {
    .shop-shell {
      grid-template-columns: 1fr;
    }

    .shop-sidebar {
      position: static;
    }
  }
</style>

<section class="mb-4">
  <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;color:var(--ea-gold);">Ayurvedic Store</p>
  <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
    <div>
      <h1 class="mb-2" style="font-size:clamp(2.2rem,4vw,3.5rem);">Shop Ayurvedic Medicines</h1>
      <p class="ea-subtle mb-0">Browse premium herbal remedies with category filtering and a cleaner purchase flow.</p>
    </div>
    <div class="small ea-subtle"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?> found</div>
  </div>
</section>

<section class="shop-shell">
  <aside class="shop-sidebar">
    <div class="d-flex align-items-center gap-3 mb-4">
      <span class="ea-icon-pill"><i class="bi bi-funnel"></i></span>
      <div>
        <h2 class="h3 mb-1">Filters</h2>
        <div class="ea-subtle small">Search by keyword or browse by category.</div>
      </div>
    </div>

    <div class="d-grid gap-2">
      <a class="shop-category-link <?= $cat === 0 ? 'active' : '' ?>" href="<?= BASE_URL ?>/public/shop.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">
        <span>All Categories</span>
        <i class="bi bi-arrow-up-right"></i>
      </a>
      <?php foreach ($cats as $c): ?>
        <a class="shop-category-link <?= $cat==(int)$c['id'] ? 'active' : '' ?>" href="<?= BASE_URL ?>/public/shop.php?cat=<?= (int)$c['id'] ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">
          <span><?= e($c['name']) ?></span>
          <i class="bi bi-chevron-right"></i>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>

  <div>
    <div class="shop-toolbar">
      <form class="row g-3 align-items-end" method="get">
        <div class="col-lg-5">
          <label class="form-label fw-semibold">Search</label>
          <input type="text" name="q" class="form-control shop-search" placeholder="Search medicines..." value="<?= e($q) ?>">
        </div>

        <div class="col-lg-4">
          <label class="form-label fw-semibold">Category</label>
          <select name="cat" class="form-select">
            <option value="0">All Categories</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $cat==(int)$c['id']?'selected':'' ?>>
                <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-lg-3 d-grid">
          <button class="btn btn-success"><i class="bi bi-search me-1"></i>Filter Results</button>
        </div>
      </form>
    </div>

    <?php if (!$products): ?>
      <div class="alert alert-info">No products found.</div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($products as $p): ?>
          <?php $stock = (int)$p['stock']; ?>
          <div class="col-md-6 col-xl-4">
            <div class="shop-product-card">
              <?php if (!empty($p['main_image'])): ?>
                <img src="<?= BASE_URL ?>/public/<?= e($p['main_image']) ?>"
                     class="w-100 shop-product-image"
                     alt="Product">
              <?php else: ?>
                <div class="shop-product-placeholder d-flex align-items-center justify-content-center">
                  <i class="bi bi-capsule" style="font-size:2.25rem;color:var(--ea-gold);"></i>
                </div>
              <?php endif; ?>

              <div class="shop-product-body">
                <div class="shop-product-summary">
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <h3 class="mb-0"><?= e($p['name']) ?></h3>
                    <span class="badge rounded-pill shop-category-badge"><?= e($p['category_name']) ?></span>
                  </div>

                  <div class="ea-subtle small mb-2">Stock: <?= $stock ?></div>
                  <div class="shop-price mb-3">NPR <?= e($p['price']) ?></div>

                  <p class="ea-subtle mb-0"><?= e(mb_strimwidth(strip_tags($p['description'] ?? ''), 0, 110, '...')) ?></p>
                </div>

                <div class="shop-card-actions">
                  <div class="d-grid gap-2">
                    <a class="btn btn-outline-success"
                       href="<?= BASE_URL ?>/public/product.php?id=<?= (int)$p['id'] ?>">
                      <i class="bi bi-eye me-1"></i>View Details
                    </a>

                    <?php if ($stock <= 0): ?>
                      <button class="btn btn-secondary" disabled>
                        <i class="bi bi-x-circle me-1"></i>Out of Stock
                      </button>

                    <?php else: ?>
                      <?php if (!$u): ?>
                        <a class="btn btn-success" href="<?= BASE_URL ?>/public/login.php">
                          <i class="bi bi-box-arrow-in-right me-1"></i>Login to Buy
                        </a>

                      <?php elseif (($u['role'] ?? '') !== 'user'): ?>
                        <button class="btn btn-secondary" disabled>Only users can purchase</button>

                      <?php else: ?>
                        <form method="post" action="<?= BASE_URL ?>/public/cart_add.php" class="shop-add-form">
                          <?= csrf_field() ?>
                          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                          <input type="number" name="qty" min="1" max="<?= $stock ?>" value="1"
                                 class="form-control shop-qty-input" required>
                          <button class="btn btn-success">
                            <i class="bi bi-cart-plus me-1"></i>Add to Cart
                          </button>
                        </form>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
