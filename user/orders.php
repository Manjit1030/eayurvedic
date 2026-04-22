<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');

$u = current_user();

$orders = [];
try {
  $stmt = db()->prepare("
    SELECT id, order_code, total_amount, payment_status, order_status, created_at
    FROM orders
    WHERE user_id=?
    ORDER BY id DESC
  ");
  $stmt->execute([$u['id']]);
  $orders = $stmt->fetchAll();
} catch (Exception $e) {}

function user_order_badge_pay($st) {
  $st = strtolower((string)$st);
  if ($st === 'paid') return '<span class="badge text-bg-success">Paid</span>';
  if ($st === 'pending') return '<span class="badge text-bg-warning">Pending</span>';
  if ($st === 'failed') return '<span class="badge text-bg-danger">Failed</span>';
  if ($st === 'refunded') return '<span class="badge text-bg-info">Refunded</span>';
  return '<span class="badge text-bg-secondary">Unpaid</span>';
}

function user_order_badge_status($st) {
  $st = strtolower((string)$st);
  if ($st === 'delivered') return '<span class="badge text-bg-success">Delivered</span>';
  if ($st === 'shipped') return '<span class="badge text-bg-primary">Shipped</span>';
  if ($st === 'confirmed') return '<span class="badge text-bg-info">Confirmed</span>';
  if ($st === 'cancelled') return '<span class="badge text-bg-danger">Cancelled</span>';
  return '<span class="badge text-bg-secondary">Pending</span>';
}
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">User Panel</div>
    <h1 class="ea-page-title">My Orders</h1>
    <p class="ea-page-subtitle">Review your order history, payment status, and fulfillment progress in one place.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-outline-success" href="<?= BASE_URL ?>/public/shop.php"><i class="bi bi-bag-heart me-1"></i>Shop Medicines</a>
  </div>
</section>

<?php if (!$orders): ?>
  <div class="ea-empty-state">
    <span class="ea-icon-pill"><i class="bi bi-receipt"></i></span>
    <h3>No orders found.</h3>
    <p>Your medicine orders will appear here after you complete checkout.</p>
  </div>
<?php else: ?>
  <div class="d-flex flex-column gap-4">
    <?php foreach ($orders as $order): ?>
      <div class="ea-panel">
        <div class="ea-panel-header">
          <div>
            <h2 class="ea-panel-title mb-1"><?= e($order['order_code']) ?></h2>
            <p class="ea-panel-subtitle">Placed on <?= e(date('M j, Y, g:i a', strtotime($order['created_at']))) ?></p>
          </div>
          <div class="text-md-end">
            <div class="fw-semibold fs-5">NPR <?= number_format((float)$order['total_amount'], 2) ?></div>
          </div>
        </div>

        <div class="row g-3 align-items-center">
          <div class="col-md-4">
            <div class="ea-note-card h-100">
              <div class="fw-semibold mb-2">Payment Status</div>
              <div><?= user_order_badge_pay($order['payment_status'] ?? 'unpaid') ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ea-note-card h-100">
              <div class="fw-semibold mb-2">Order Status</div>
              <div><?= user_order_badge_status($order['order_status'] ?? 'pending') ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="ea-note-card h-100 d-flex flex-column justify-content-between">
              <div>
                <div class="fw-semibold mb-2">Order Code</div>
                <div class="ea-meta"><?= e($order['order_code']) ?></div>
              </div>
              <div class="mt-3">
                <a class="btn btn-outline-success btn-sm w-100" href="<?= BASE_URL ?>/public/order_success.php?code=<?= urlencode($order['order_code']) ?>">
                  <i class="bi bi-eye me-1"></i>View Details
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
