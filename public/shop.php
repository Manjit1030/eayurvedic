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

<h1 class="h4 fw-bold mb-3">Shop Ayurvedic Medicines</h1>

<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-5">
    <input type="text" name="q" class="form-control" placeholder="Search medicines..." value="<?= e($q) ?>">
  </div>

  <div class="col-12 col-md-4">
    <select name="cat" class="form-select">
      <option value="0">All Categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cat==(int)$c['id']?'selected':'' ?>>
          <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12 col-md-3 d-grid">
    <button class="btn btn-success"><i class="bi bi-funnel"></i> Filter</button>
  </div>
</form>

<?php if (!$products): ?>
  <div class="alert alert-info">No products found.</div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($products as $p): ?>
      <?php $stock = (int)$p['stock']; ?>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card shadow-sm h-100">
          <?php if (!empty($p['main_image'])): ?>
            <img src="<?= BASE_URL ?>/public/<?= e($p['main_image']) ?>"
                 class="card-img-top"
                 style="height: 180px; object-fit: cover;"
                 alt="Product">
          <?php endif; ?>

          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start">
              <h5 class="card-title mb-1"><?= e($p['name']) ?></h5>
              <span class="badge text-bg-success"><?= e($p['category_name']) ?></span>
            </div>

            <p class="small text-muted mb-2">Stock: <?= $stock ?></p>
            <p class="fw-bold mb-2">NPR <?= e($p['price']) ?></p>

            <p class="card-text small">
              <?= e(mb_strimwidth(strip_tags($p['description'] ?? ''), 0, 110, '...')) ?>
            </p>

            <div class="mt-auto">
              <div class="d-grid gap-2">
                <a class="btn btn-outline-success btn-sm"
                   href="<?= BASE_URL ?>/public/product.php?id=<?= (int)$p['id'] ?>">
                  <i class="bi bi-eye"></i> View Details
                </a>

                <?php if ($stock <= 0): ?>
                  <button class="btn btn-secondary btn-sm" disabled>
                    <i class="bi bi-x-circle"></i> Out of Stock
                  </button>

                <?php else: ?>
                  <?php if (!$u): ?>
                    <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/public/login.php">
                      <i class="bi bi-box-arrow-in-right"></i> Login to Buy
                    </a>

                  <?php elseif (($u['role'] ?? '') !== 'user'): ?>
                    <button class="btn btn-secondary btn-sm" disabled>Only users can purchase</button>

                  <?php else: ?>
                    <form method="post" action="<?= BASE_URL ?>/public/cart_add.php" class="d-flex gap-2">
                      <?= csrf_field() ?>
                      <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                      <input type="number" name="qty" min="1" max="<?= $stock ?>" value="1"
                             class="form-control form-control-sm" style="width:80px;">
                      <button class="btn btn-success btn-sm">
                        <i class="bi bi-cart-plus"></i>
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

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
