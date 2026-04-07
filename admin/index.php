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
  'orders_total' => 0,
  'orders_unpaid' => 0,
];

try {
  $stats['users'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
  $stats['products'] = (int)db()->query("SELECT COUNT(*) FROM products")->fetchColumn();
  $stats['categories'] = (int)db()->query("SELECT COUNT(*) FROM categories")->fetchColumn();
  $stats['concerns_pending'] = (int)db()->query("SELECT COUNT(*) FROM patient_concerns WHERE status='pending'")->fetchColumn();
  $stats['orders_total'] = (int)db()->query("SELECT COUNT(*) FROM orders")->fetchColumn();
  $stats['orders_unpaid'] = (int)db()->query("SELECT COUNT(*) FROM orders WHERE payment_status IN ('unpaid','pending')")->fetchColumn();
} catch (Exception $e) {
  // dashboard remains usable if some tables are missing
}

/* Latest concerns */
$latestConcerns = [];
try {
  $latestConcerns = db()->query("
    SELECT pc.id, pc.disease_name, pc.symptoms, pc.severity, pc.status, pc.created_at, u.full_name
    FROM patient_concerns pc
    JOIN users u ON u.id = pc.user_id
    ORDER BY pc.id DESC
    LIMIT 6
  ")->fetchAll();
} catch (Exception $e) {}

/* Latest orders */
$latestOrders = [];
try {
  $latestOrders = db()->query("
    SELECT o.id, o.order_code, o.total_amount, o.payment_status, o.order_status, o.created_at, u.full_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
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
function badge_pay($st) {
  $st = strtolower((string)$st);
  if ($st === 'paid') return '<span class="badge text-bg-success">Paid</span>';
  if ($st === 'pending') return '<span class="badge text-bg-warning">Pending</span>';
  if ($st === 'failed') return '<span class="badge text-bg-danger">Failed</span>';
  return '<span class="badge text-bg-secondary">Unpaid</span>';
}
function badge_order($st) {
  $st = strtolower((string)$st);
  if ($st === 'delivered') return '<span class="badge text-bg-success">Delivered</span>';
  if ($st === 'shipped') return '<span class="badge text-bg-primary">Shipped</span>';
  if ($st === 'cancelled') return '<span class="badge text-bg-danger">Cancelled</span>';
  if ($st === 'confirmed') return '<span class="badge text-bg-info">Confirmed</span>';
  return '<span class="badge text-bg-secondary">Pending</span>';
}
?>

<style>
  .dash-hero{
    background: radial-gradient(1000px 520px at 10% 10%, rgba(25,135,84,.18), transparent 60%),
                radial-gradient(900px 520px at 90% 0%, rgba(220,53,69,.10), transparent 55%),
                linear-gradient(180deg, rgba(25,135,84,.06), rgba(255,255,255,0));
    border-radius: 1.25rem;
  }
  .icon-pill{
    width: 42px; height: 42px; border-radius: 999px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(25,135,84,.12);
  }
  .card-hover{ transition: transform .15s ease, box-shadow .15s ease; }
  .card-hover:hover{ transform: translateY(-2px); box-shadow: 0 .75rem 2rem rgba(0,0,0,.08); }
  .mini-muted{ color: rgba(0,0,0,.55); }
</style>

<!-- HERO -->
<section class="dash-hero p-4 p-md-5 mb-4 shadow-sm">
  <div class="row align-items-center g-4">
    <div class="col-lg-8">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="icon-pill"><i class="bi bi-speedometer2 fs-5 text-success"></i></div>
        <div>
          <div class="fw-bold fs-4 mb-0">Admin Dashboard</div>
          <div class="mini-muted">Manage consultation queue, products, orders and payments.</div>
        </div>
      </div>

      <div class="mt-3 d-flex flex-wrap gap-2">
        <a class="btn btn-success" href="<?= BASE_URL ?>/admin/categories_add.php">
          <i class="bi bi-plus-circle me-1"></i> Add Category
        </a>
        <a class="btn btn-outline-success" href="<?= BASE_URL ?>/admin/products_add.php">
          <i class="bi bi-plus-circle me-1"></i> Add Product
        </a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/categories_list.php">
          <i class="bi bi-grid-3x3-gap me-1"></i> Categories
        </a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/products_list.php">
          <i class="bi bi-bag-check me-1"></i> Products
        </a>
      </div>

      <div class="mt-4">
        <div class="small text-muted">Algorithm switches (demo control):</div>
        <div class="d-flex flex-wrap gap-2 mt-1">
          <span class="badge text-bg-light border">Master: <b><?= (ALGO_ENABLED ? 'ON' : 'OFF') ?></b></span>
          <span class="badge text-bg-light border">Cart Totals: <b><?= (ALGO_ENABLED && ALGO_CART_TOTALS ? 'ON' : 'OFF') ?></b></span>
          <span class="badge text-bg-light border">Severity: <b><?= (ALGO_ENABLED && ALGO_SEVERITY_SCORE ? 'ON' : 'OFF') ?></b></span>
          <span class="badge text-bg-light border">Symptom Match: <b><?= (ALGO_ENABLED && ALGO_SYMPTOM_MATCH ? 'ON' : 'OFF') ?></b></span>
          <span class="badge text-bg-light border">Product Recommend: <b><?= (ALGO_ENABLED && ALGO_PRODUCT_RECOMMEND ? 'ON' : 'OFF') ?></b></span>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="fw-bold mb-3"><i class="bi bi-graph-up-arrow text-success me-2"></i>Quick KPIs</div>

          <div class="row g-3 text-center">
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['users'] ?></div>
              <div class="mini-muted small">Users</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['concerns_pending'] ?></div>
              <div class="mini-muted small">Pending Concerns</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['products'] ?></div>
              <div class="mini-muted small">Products</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['categories'] ?></div>
              <div class="mini-muted small">Categories</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['orders_total'] ?></div>
              <div class="mini-muted small">Orders</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['orders_unpaid'] ?></div>
              <div class="mini-muted small">Unpaid/Pending</div>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/concerns_list.php">
              <i class="bi bi-clipboard2-heart me-1"></i> Consultation Queue
            </a>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/orders_list.php">
              <i class="bi bi-receipt me-1"></i> Orders
            </a>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/payments_list.php">
              <i class="bi bi-credit-card me-1"></i> Payments
            </a>
          </div>

          <div class="small text-muted mt-3">
            If a page is not created yet (404), we will create it next step.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- RECENT TABLES -->
<section class="mb-4">
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold"><i class="bi bi-clipboard2-heart text-success me-2"></i>Latest Concerns</div>
            <a class="small text-decoration-none" href="<?= BASE_URL ?>/admin/concerns_list.php">View all</a>
          </div>

          <?php if (!$latestConcerns): ?>
            <div class="alert alert-info mb-0">No concerns submitted yet.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
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
                      <td class="text-muted small"><?= e($c['created_at'] ?? '') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold"><i class="bi bi-receipt text-success me-2"></i>Latest Orders</div>
            <a class="small text-decoration-none" href="<?= BASE_URL ?>/admin/orders_list.php">View all</a>
          </div>

          <?php if (!$latestOrders): ?>
            <div class="alert alert-info mb-0">No orders yet.</div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($latestOrders as $o): ?>
                <div class="list-group-item px-0">
                  <div class="d-flex justify-content-between">
                    <div class="fw-semibold"><?= e($o['order_code']) ?></div>
                    <div class="fw-semibold">NPR <?= number_format((float)$o['total_amount'], 2) ?></div>
                  </div>
                  <div class="d-flex justify-content-between mt-1">
                    <div>
                      <?= badge_order($o['order_status'] ?? 'pending') ?>
                      <?= badge_pay($o['payment_status'] ?? 'unpaid') ?>
                    </div>
                    <div class="text-muted small"><?= e($o['full_name'] ?? '-') ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
