<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');

$u = current_user();

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

  $stmt = db()->prepare("SELECT id FROM carts WHERE user_id=? LIMIT 1");
  $stmt->execute([$u['id']]);
  $cart = $stmt->fetch();
  if ($cart) {
    $stmt = db()->prepare("SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE cart_id=?");
    $stmt->execute([(int)$cart['id']]);
    $stats['cart_items'] = (int)$stmt->fetchColumn();
  }
} catch (Exception $e) {}

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

$recentAddresses = [];
try {
  $stmt = db()->prepare("
    SELECT label, city, district, province, area, street
    FROM user_addresses
    WHERE user_id=?
    ORDER BY id DESC
    LIMIT 2
  ");
  $stmt->execute([$u['id']]);
  $recentAddresses = $stmt->fetchAll();
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

<div class="ea-dashboard-banner">
  <div class="ea-page-head mb-0">
    <div>
      <div class="ea-page-kicker">User Dashboard</div>
      <h1 class="ea-page-title">Welcome, <?= e($u['full_name'] ?? 'User') ?></h1>
      <p class="ea-page-subtitle">Manage your concerns, solutions, addresses, and medicine orders in one place.</p>
    </div>
    <div class="ea-page-actions">
      <a class="btn btn-success" href="<?= BASE_URL ?>/user/concerns_add.php"><i class="bi bi-plus-circle me-1"></i>Add Concern</a>
      <a class="btn btn-outline-success" href="<?= BASE_URL ?>/public/shop.php"><i class="bi bi-bag-heart me-1"></i>Shop Medicines</a>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/public/cart.php"><i class="bi bi-cart3 me-1"></i>Cart (<?= (int)$stats['cart_items'] ?>)</a>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6 col-xl-4">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-clipboard2-heart"></i></div>
      <div class="ea-stat-label">My Concerns</div>
      <div class="ea-stat-value"><?= (int)$stats['concerns'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-4">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-receipt"></i></div>
      <div class="ea-stat-label">My Orders</div>
      <div class="ea-stat-value"><?= (int)$stats['orders'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-4">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-geo-alt"></i></div>
      <div class="ea-stat-label">My Addresses</div>
      <div class="ea-stat-value"><?= (int)$stats['addresses'] ?></div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="ea-quick-card">
      <span class="ea-icon-pill"><i class="bi bi-clipboard2-plus"></i></span>
      <h3>Submit Concern</h3>
      <p>Add symptoms, health history, and other details for admin review.</p>
      <a class="btn btn-success" href="<?= BASE_URL ?>/user/concerns_add.php">Add Now</a>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-quick-card">
      <span class="ea-icon-pill"><i class="bi bi-chat-left-dots"></i></span>
      <h3>My Solutions</h3>
      <p>Track diagnosis updates and read the latest treatment guidance.</p>
      <a class="btn btn-outline-success" href="<?= BASE_URL ?>/user/solutions.php">Open</a>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-quick-card">
      <span class="ea-icon-pill"><i class="bi bi-bag-check"></i></span>
      <h3>Shop Medicines</h3>
      <p>Browse Ayurvedic products in the same design system as your account pages.</p>
      <a class="btn btn-outline-success" href="<?= BASE_URL ?>/public/shop.php">Shop</a>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="ea-quick-card">
      <span class="ea-icon-pill"><i class="bi bi-map"></i></span>
      <h3>Manage Addresses</h3>
      <p>Keep delivery addresses updated for faster checkout and order placement.</p>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/user/addresses.php">My Addresses</a>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="ea-panel h-100">
      <div class="ea-panel-header">
        <div>
          <h2 class="ea-panel-title">Recent Concerns</h2>
          <p class="ea-panel-subtitle">See the latest concerns you submitted and their review status.</p>
        </div>
        <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/user/concerns_list.php">View All</a>
      </div>

      <?php if (!$recentConcerns): ?>
        <div class="ea-empty-state">
          <span class="ea-icon-pill"><i class="bi bi-clipboard2-heart"></i></span>
          <h3>No concerns submitted</h3>
          <p>Use the “Add Concern” action to submit your first health concern.</p>
        </div>
      <?php else: ?>
        <div class="ea-table-wrap shadow-none">
          <div class="table-responsive shadow-none">
            <table class="table ea-table align-middle mb-0">
              <thead>
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
          <h2 class="ea-panel-title">Recent Orders</h2>
          <p class="ea-panel-subtitle">Track your latest medicine purchases and payment states.</p>
        </div>
        <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/user/orders.php">View All</a>
      </div>

      <?php if (!$recentOrders): ?>
        <div class="ea-empty-state">
          <span class="ea-icon-pill"><i class="bi bi-receipt"></i></span>
          <h3>No orders yet</h3>
          <p>Your order activity will appear here after you complete checkout.</p>
        </div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($recentOrders as $o): ?>
            <div class="p-3 rounded-4" style="background:#fcfbf8;border:1px solid rgba(26,71,42,0.08);">
              <div class="d-flex justify-content-between gap-3">
                <div class="fw-semibold"><?= e($o['order_code']) ?></div>
                <div class="fw-semibold">NPR <?= number_format((float)$o['total_amount'], 2) ?></div>
              </div>
              <div class="d-flex flex-wrap justify-content-between gap-2 mt-2">
                <div><?= badge_order($o['order_status'] ?? 'pending') ?> <?= badge_pay($o['payment_status'] ?? 'unpaid') ?></div>
                <div class="ea-meta"><?= e($o['created_at'] ?? '') ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-4 mt-1">
  <div class="col-lg-7">
    <div class="ea-panel h-100">
      <div class="ea-panel-header">
        <div>
          <h2 class="ea-panel-title">Saved Addresses</h2>
          <p class="ea-panel-subtitle">A quick view of your latest delivery locations used during checkout.</p>
        </div>
        <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/user/addresses.php">Manage</a>
      </div>

      <?php if (!$recentAddresses): ?>
        <div class="ea-empty-state">
          <span class="ea-icon-pill"><i class="bi bi-geo-alt"></i></span>
          <h3>No addresses saved</h3>
          <p>Add a delivery address so ordering medicines is faster next time.</p>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($recentAddresses as $address): ?>
            <div class="col-md-6">
              <div class="ea-note-card h-100">
                <div class="fw-semibold mb-2"><?= e($address['label'] ?: 'Address') ?></div>
                <div class="ea-meta">
                  <?= e($address['city'] ?? '') ?><?= !empty($address['district']) ? ', ' . e($address['district']) : '' ?>
                  <?= !empty($address['province']) ? ', ' . e($address['province']) : '' ?>
                </div>
                <?php if (!empty($address['area']) || !empty($address['street'])): ?>
                  <div class="ea-meta mt-1">
                    <?= e(trim(($address['area'] ?? '') . ' ' . ($address['street'] ?? ''))) ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
