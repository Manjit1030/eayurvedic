<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');

$stats = [
  'users' => 0,
  'products' => 0,
  'categories' => 0,
  'concerns_pending' => 0,
  'orders' => 0,
  'payments_success' => 0,
  'payments_pending' => 0,
];

try {
  $stats['users'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
  $stats['products'] = (int)db()->query("SELECT COUNT(*) FROM products")->fetchColumn();
  $stats['categories'] = (int)db()->query("SELECT COUNT(*) FROM categories")->fetchColumn();
  $stats['concerns_pending'] = (int)db()->query("SELECT COUNT(*) FROM patient_concerns WHERE status='pending'")->fetchColumn();
  $stats['orders'] = (int)db()->query("SELECT COUNT(*) FROM orders")->fetchColumn();
  $stats['payments_success'] = (int)db()->query("SELECT COUNT(*) FROM orders WHERE payment_status='paid'")->fetchColumn();
  $stats['payments_pending'] = (int)db()->query("SELECT COUNT(*) FROM orders WHERE payment_status IN ('unpaid','pending')")->fetchColumn();
} catch (Exception $e) {
  // keep dashboard usable
}

$latestConcerns = [];
try {
  $latestConcerns = db()->query("
    SELECT pc.id, pc.disease_name, pc.severity, pc.status, pc.created_at, u.full_name
    FROM patient_concerns pc
    JOIN users u ON u.id = pc.user_id
    ORDER BY pc.id DESC
    LIMIT 6
  ")->fetchAll();
} catch (Exception $e) {}

$latestProducts = [];
try {
  $latestProducts = db()->query("
    SELECT p.id, p.name, p.price, p.stock, p.status, p.main_image, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    ORDER BY p.id DESC
    LIMIT 6
  ")->fetchAll();
} catch (Exception $e) {}

function badge_sev($sev) {
  $sev = strtolower((string)$sev);
  if ($sev === 'severe') return '<span class="badge text-bg-danger">Severe</span>';
  if ($sev === 'moderate') return '<span class="badge text-bg-warning">Moderate</span>';
  return '<span class="badge text-bg-success">Mild</span>';
}

function badge_cstatus($st) {
  $st = strtolower((string)$st);
  if ($st === 'solution_provided') return '<span class="badge text-bg-success">Solution Provided</span>';
  if ($st === 'reviewed') return '<span class="badge text-bg-primary">Reviewed</span>';
  return '<span class="badge text-bg-secondary">Pending</span>';
}
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Admin Dashboard</h1>
    <p class="ea-page-subtitle">Monitor the store, manage the consultation queue, and keep categories and medicines organized from one unified admin workspace.</p>
  </div>
</section>

<div class="row g-4 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-people"></i></div>
      <div class="ea-stat-label">Users</div>
      <div class="ea-stat-value"><?= (int)$stats['users'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-capsule-pill"></i></div>
      <div class="ea-stat-label">Products</div>
      <div class="ea-stat-value"><?= (int)$stats['products'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-grid-3x3-gap"></i></div>
      <div class="ea-stat-label">Categories</div>
      <div class="ea-stat-value"><?= (int)$stats['categories'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-heart-pulse"></i></div>
      <div class="ea-stat-label">Pending Concerns</div>
      <div class="ea-stat-value"><?= (int)$stats['concerns_pending'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-receipt"></i></div>
      <div class="ea-stat-label">Total Orders</div>
      <div class="ea-stat-value"><?= (int)$stats['orders'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-credit-card"></i></div>
      <div class="ea-stat-label">Successful Payments</div>
      <div class="ea-stat-value"><?= (int)$stats['payments_success'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-hourglass-split"></i></div>
      <div class="ea-stat-label">Pending Payments</div>
      <div class="ea-stat-value"><?= (int)$stats['payments_pending'] ?></div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="ea-panel h-100">
      <div class="ea-panel-header">
        <div>
          <h2 class="ea-panel-title">Recent Concerns</h2>
          <p class="ea-panel-subtitle">Latest patient submissions waiting for review or already updated with a solution.</p>
        </div>
        <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/admin/concerns_list.php">View All</a>
      </div>

      <?php if (!$latestConcerns): ?>
        <div class="ea-empty-state">
          <span class="ea-icon-pill"><i class="bi bi-clipboard2-heart"></i></span>
          <h3>No concerns yet</h3>
          <p>Submitted patient concerns will appear here once the consultation feature is used.</p>
        </div>
      <?php else: ?>
        <div class="ea-table-wrap shadow-none">
          <div class="table-responsive shadow-none">
            <table class="table ea-table align-middle mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Disease</th>
                  <th>Severity</th>
                  <th>Status</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($latestConcerns as $c): ?>
                  <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td><?= e($c['full_name'] ?? '-') ?></td>
                    <td><?= e($c['disease_name'] ?? '-') ?></td>
                    <td><?= badge_sev($c['severity'] ?? 'mild') ?></td>
                    <td><?= badge_cstatus($c['status'] ?? 'pending') ?></td>
                    <td class="ea-meta"><?= e($c['created_at'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="ea-panel h-100">
      <div class="ea-panel-header">
        <div>
          <h2 class="ea-panel-title">Latest Products</h2>
          <p class="ea-panel-subtitle">Recently added or updated medicines from the store catalog.</p>
        </div>
        <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/admin/products_list.php">View All</a>
      </div>

      <?php if (!$latestProducts): ?>
        <div class="ea-empty-state">
          <span class="ea-icon-pill"><i class="bi bi-capsule-pill"></i></span>
          <h3>No products yet</h3>
          <p>Add medicines to build the storefront and make the product catalog available.</p>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($latestProducts as $product): ?>
            <div class="p-3 rounded-4" style="background:#fcfbf8;border:1px solid rgba(26,71,42,0.08);">
              <div class="d-flex align-items-center gap-3">
                <?php if (!empty($product['main_image'])): ?>
                  <img src="<?= BASE_URL ?>/public/<?= e($product['main_image']) ?>" class="ea-thumb" alt="Product">
                <?php else: ?>
                  <span class="ea-thumb-placeholder"><i class="bi bi-image"></i></span>
                <?php endif; ?>
                <div class="flex-grow-1">
                  <div class="fw-semibold"><?= e($product['name']) ?></div>
                  <div class="ea-meta"><?= e($product['category_name']) ?> • Stock: <?= (int)$product['stock'] ?></div>
                </div>
                <div class="text-end">
                  <div class="fw-semibold">NPR <?= number_format((float)$product['price'], 2) ?></div>
                  <span class="badge <?= ($product['status'] ?? '') === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                    <?= e($product['status']) ?>
                  </span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
