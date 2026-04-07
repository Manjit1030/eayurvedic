<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');

$u = current_user();

/* Stats */
$stats = [
  'addresses' => 0,
  'concerns' => 0,
  'solutions' => 0,
  'orders' => 0,
  'cart_items' => 0,
];

try {
  $stmt = db()->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id=?");
  $stmt->execute([$u['id']]);
  $stats['addresses'] = (int)$stmt->fetchColumn();

  $stmt = db()->prepare("SELECT COUNT(*) FROM patient_concerns WHERE user_id=?");
  $stmt->execute([$u['id']]);
  $stats['concerns'] = (int)$stmt->fetchColumn();

  // Solutions count (join via concerns)
  $stmt = db()->prepare("
    SELECT COUNT(*)
    FROM solutions s
    JOIN patient_concerns pc ON pc.id = s.concern_id
    WHERE pc.user_id=?
  ");
  $stmt->execute([$u['id']]);
  $stats['solutions'] = (int)$stmt->fetchColumn();

  $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
  $stmt->execute([$u['id']]);
  $stats['orders'] = (int)$stmt->fetchColumn();

  // Cart items count
  $stmt = db()->prepare("SELECT id FROM carts WHERE user_id=? LIMIT 1");
  $stmt->execute([$u['id']]);
  $cart = $stmt->fetch();
  if ($cart) {
    $stmt = db()->prepare("SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE cart_id=?");
    $stmt->execute([(int)$cart['id']]);
    $stats['cart_items'] = (int)$stmt->fetchColumn();
  }
} catch (Exception $e) {
  // keep dashboard functional even if some tables missing
}

/* Recent concerns */
$recentConcerns = [];
try {
  $stmt = db()->prepare("
    SELECT id, disease_name, symptoms, severity, status, created_at
    FROM patient_concerns
    WHERE user_id=?
    ORDER BY id DESC
    LIMIT 5
  ");
  $stmt->execute([$u['id']]);
  $recentConcerns = $stmt->fetchAll();
} catch (Exception $e) {}

/* Recent orders */
$recentOrders = [];
try {
  $stmt = db()->prepare("
    SELECT id, order_code, total_amount, payment_status, order_status, created_at
    FROM orders
    WHERE user_id=?
    ORDER BY id DESC
    LIMIT 5
  ");
  $stmt->execute([$u['id']]);
  $recentOrders = $stmt->fetchAll();
} catch (Exception $e) {}

function badge_severity($sev) {
  $sev = strtolower((string)$sev);
  if ($sev === 'severe') return '<span class="badge text-bg-danger">Severe</span>';
  if ($sev === 'moderate') return '<span class="badge text-bg-warning">Moderate</span>';
  return '<span class="badge text-bg-success">Mild</span>';
}
function badge_concern_status($st) {
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
    background: radial-gradient(1000px 500px at 10% 10%, rgba(25,135,84,.18), transparent 60%),
                radial-gradient(900px 500px at 90% 0%, rgba(13,110,253,.12), transparent 55%),
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
    <div class="col-lg-7">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="icon-pill"><i class="bi bi-person-badge fs-5 text-success"></i></div>
        <div>
          <div class="fw-bold fs-4 mb-0">Welcome, <?= e($u['full_name'] ?? 'User') ?></div>
          <div class="mini-muted">Manage your concerns, view solutions, and order Ayurvedic medicines.</div>
        </div>
      </div>

      <div class="mt-3 d-flex flex-wrap gap-2">
        <a class="btn btn-success" href="<?= BASE_URL ?>/user/concerns_add.php">
          <i class="bi bi-plus-circle me-1"></i> Add Concern
        </a>
        <a class="btn btn-outline-success" href="<?= BASE_URL ?>/public/shop.php">
          <i class="bi bi-bag-heart me-1"></i> Shop Medicines
        </a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/public/cart.php">
          <i class="bi bi-cart3 me-1"></i> Cart (<?= (int)$stats['cart_items'] ?>)
        </a>
      </div>

      <div class="mt-4">
        <div class="small text-muted">Algorithm switches (for demo):</div>
        <div class="d-flex flex-wrap gap-2 mt-1">
          <span class="badge text-bg-light border">
            Master: <b><?= (ALGO_ENABLED ? 'ON' : 'OFF') ?></b>
          </span>
          <span class="badge text-bg-light border">
            Cart Totals: <b><?= (ALGO_ENABLED && ALGO_CART_TOTALS ? 'ON' : 'OFF') ?></b>
          </span>
          <span class="badge text-bg-light border">
            Severity: <b><?= (ALGO_ENABLED && ALGO_SEVERITY_SCORE ? 'ON' : 'OFF') ?></b>
          </span>
          <span class="badge text-bg-light border">
            Symptom Match: <b><?= (ALGO_ENABLED && ALGO_SYMPTOM_MATCH ? 'ON' : 'OFF') ?></b>
          </span>
          <span class="badge text-bg-light border">
            Product Recommend: <b><?= (ALGO_ENABLED && ALGO_PRODUCT_RECOMMEND ? 'ON' : 'OFF') ?></b>
          </span>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-bold"><i class="bi bi-graph-up-arrow text-success me-2"></i>Quick Stats</div>
            <a class="small text-decoration-none" href="<?= BASE_URL ?>/public/index.php">Home</a>
          </div>
          <div class="row g-3 text-center">
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['concerns'] ?></div>
              <div class="mini-muted small">Concerns</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['solutions'] ?></div>
              <div class="mini-muted small">Solutions</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['orders'] ?></div>
              <div class="mini-muted small">Orders</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['addresses'] ?></div>
              <div class="mini-muted small">Addresses</div>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/user/concerns_list.php">
              <i class="bi bi-clipboard2-heart me-1"></i> My Concerns
            </a>
            <a class="btn btn-outline-success" href="<?= BASE_URL ?>/user/solutions.php">
              <i class="bi bi-chat-left-text me-1"></i> My Solutions
            </a>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/user/orders.php">
              <i class="bi bi-receipt me-1"></i> My Orders
            </a>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/user/addresses.php">
              <i class="bi bi-geo-alt me-1"></i> My Addresses
            </a>
          </div>

          <div class="small text-muted mt-3">
            If any page shows “not found”, we’ll create it in the next steps.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- MAIN CARDS -->
<section class="mb-4">
  <div class="row g-3">
    <div class="col-md-6 col-lg-3">
      <div class="card card-hover h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="icon-pill mb-3"><i class="bi bi-clipboard2-plus fs-5 text-success"></i></div>
          <div class="fw-bold">Submit Concern</div>
          <div class="mini-muted small mb-3">Add symptoms, history, mental and digestive details.</div>
          <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/user/concerns_add.php">Add Now</a>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card card-hover h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="icon-pill mb-3"><i class="bi bi-chat-left-dots fs-5 text-success"></i></div>
          <div class="fw-bold">View Solutions</div>
          <div class="mini-muted small mb-3">Check admin recommendations for your submitted concerns.</div>
          <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/user/solutions.php">Open</a>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card card-hover h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="icon-pill mb-3"><i class="bi bi-bag-check fs-5 text-success"></i></div>
          <div class="fw-bold">Shop Medicines</div>
          <div class="mini-muted small mb-3">Browse, search and buy Ayurvedic medicines.</div>
          <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/public/shop.php">Shop</a>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card card-hover h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="icon-pill mb-3"><i class="bi bi-cart-check fs-5 text-success"></i></div>
          <div class="fw-bold">Cart & Checkout</div>
          <div class="mini-muted small mb-3">Review cart, choose address, and place orders.</div>
          <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>/public/cart.php">Open Cart</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- RECENT ACTIVITY -->
<section class="mb-4">
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold"><i class="bi bi-clipboard2-heart me-2 text-success"></i>Recent Concerns</div>
            <a class="small text-decoration-none" href="<?= BASE_URL ?>/user/concerns_list.php">View all</a>
          </div>

          <?php if (!$recentConcerns): ?>
            <div class="alert alert-info mb-0">No concerns submitted yet.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>ID</th>
                    <th>Disease</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentConcerns as $c): ?>
                    <tr>
                      <td><?= (int)$c['id'] ?></td>
                      <td><?= e($c['disease_name'] ?? '-') ?></td>
                      <td><?= badge_severity($c['severity'] ?? 'mild') ?></td>
                      <td><?= badge_concern_status($c['status'] ?? 'pending') ?></td>
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
            <div class="fw-bold"><i class="bi bi-receipt me-2 text-success"></i>Recent Orders</div>
            <a class="small text-decoration-none" href="<?= BASE_URL ?>/user/orders.php">View all</a>
          </div>

          <?php if (!$recentOrders): ?>
            <div class="alert alert-info mb-0">No orders yet.</div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($recentOrders as $o): ?>
                <div class="list-group-item px-0">
                  <div class="d-flex justify-content-between">
                    <div class="fw-semibold"><?= e($o['order_code']) ?></div>
                    <div class="fw-semibold">NPR <?= number_format((float)$o['total_amount'], 2) ?></div>
                  </div>
                  <div class="d-flex justify-content-between mt-1">
                    <div><?= badge_order($o['order_status'] ?? 'pending') ?> <?= badge_pay($o['payment_status'] ?? 'unpaid') ?></div>
                    <div class="text-muted small"><?= e($o['created_at'] ?? '') ?></div>
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
