<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');

$orders = [];
try {
  $orders = db()->query("
    SELECT
      o.id,
      o.order_code,
      o.total_amount,
      o.payment_method,
      o.payment_status,
      o.order_status,
      o.created_at,
      u.full_name,
      u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
  ")->fetchAll();
} catch (Exception $e) {
  $orders = [];
}

function admin_order_badge_pay($st) {
  $st = strtolower((string)$st);
  if ($st === 'paid') return '<span class="badge text-bg-success">Paid</span>';
  if ($st === 'pending') return '<span class="badge text-bg-warning">Pending</span>';
  if ($st === 'failed') return '<span class="badge text-bg-danger">Failed</span>';
  if ($st === 'refunded') return '<span class="badge text-bg-info">Refunded</span>';
  return '<span class="badge text-bg-secondary">Unpaid</span>';
}

function admin_order_badge_status($st) {
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
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Order History</h1>
    <p class="ea-page-subtitle">Review all customer medicine orders, payment progress, fulfillment status, and order dates.</p>
  </div>
</section>

<?php if (!$orders): ?>
  <div class="ea-empty-state">
    <span class="ea-icon-pill"><i class="bi bi-receipt"></i></span>
    <h3>No orders found</h3>
    <p>Customer orders will appear here after checkout is completed.</p>
  </div>
<?php else: ?>
  <div class="ea-table-wrap">
    <div class="table-responsive shadow-none">
      <table class="table ea-table align-middle mb-0">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Total</th>
            <th>Order Status</th>
            <th>Payment Status</th>
            <th>Payment Method</th>
            <th>Date/Time</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>
                <div class="fw-semibold">#<?= e((string)$order['order_code']) ?></div>
                <div class="ea-meta">ID: <?= (int)$order['id'] ?></div>
              </td>
              <td class="fw-semibold"><?= e((string)($order['full_name'] ?? '-')) ?></td>
              <td><?= e((string)($order['email'] ?? '-')) ?></td>
              <td class="fw-semibold">NPR <?= number_format((float)$order['total_amount'], 2) ?></td>
              <td><?= admin_order_badge_status($order['order_status'] ?? 'pending') ?></td>
              <td><?= admin_order_badge_pay($order['payment_status'] ?? 'unpaid') ?></td>
              <td><?= e(strtoupper((string)$order['payment_method'])) ?></td>
              <td class="ea-meta"><?= e(date('M j, Y, g:i a', strtotime((string)$order['created_at']))) ?></td>
              <td>
                <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/public/order_success.php?code=<?= urlencode((string)$order['order_code']) ?>">
                  <i class="bi bi-eye me-1"></i>View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
