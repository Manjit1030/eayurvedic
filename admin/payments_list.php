<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');

$payments = [];
try {
  $payments = db()->query("
    SELECT *
    FROM (
      SELECT
        p.id AS payment_id,
        p.txn_ref,
        p.amount,
        p.gateway AS payment_method,
        p.status AS payment_status,
        p.paid_at,
        p.created_at,
        p.created_at AS sort_created,
        o.id AS order_id,
        o.order_code,
        o.payment_status AS order_payment_status,
        u.full_name,
        u.email
      FROM payments p
      JOIN orders o ON o.id = p.order_id
      JOIN users u ON u.id = o.user_id

      UNION ALL

      SELECT
        NULL AS payment_id,
        NULL AS txn_ref,
        o.total_amount AS amount,
        o.payment_method AS payment_method,
        o.payment_status AS payment_status,
        NULL AS paid_at,
        o.created_at AS created_at,
        o.created_at AS sort_created,
        o.id AS order_id,
        o.order_code,
        o.payment_status AS order_payment_status,
        u.full_name,
        u.email
      FROM orders o
      JOIN users u ON u.id = o.user_id
      WHERE o.payment_method = 'cash'
        AND NOT EXISTS (
          SELECT 1 FROM payments p2 WHERE p2.order_id = o.id
        )
    ) payment_history
    ORDER BY sort_created DESC, order_id DESC
  ")->fetchAll();
} catch (Exception $e) {
  $payments = [];
}

function admin_payment_badge($st) {
  $st = strtolower((string)$st);
  if ($st === 'success' || $st === 'paid') return '<span class="badge text-bg-success">Paid</span>';
  if ($st === 'initiated' || $st === 'pending') return '<span class="badge text-bg-warning">Pending</span>';
  if ($st === 'failed') return '<span class="badge text-bg-danger">Failed</span>';
  if ($st === 'refunded') return '<span class="badge text-bg-info">Refunded</span>';
  return '<span class="badge text-bg-secondary">Unpaid</span>';
}
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Payment History</h1>
    <p class="ea-page-subtitle">Review payment attempts, order references, customer details, amounts, and payment status.</p>
  </div>
</section>

<?php if (!$payments): ?>
  <div class="ea-empty-state">
    <span class="ea-icon-pill"><i class="bi bi-credit-card"></i></span>
    <h3>No payments found</h3>
    <p>Payment activity will appear here after orders are placed or gateway payments are initiated.</p>
  </div>
<?php else: ?>
  <div class="ea-table-wrap">
    <div class="table-responsive shadow-none">
      <table class="table ea-table align-middle mb-0">
        <thead>
          <tr>
            <th>Payment/Transaction ID</th>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Payment Method</th>
            <th>Payment Status</th>
            <th>Date/Time</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $payment): ?>
            <tr>
              <td>
                <?php if (!empty($payment['txn_ref'])): ?>
                  <div class="fw-semibold"><?= e((string)$payment['txn_ref']) ?></div>
                  <div class="ea-meta">Payment ID: <?= (int)$payment['payment_id'] ?></div>
                <?php elseif (!empty($payment['payment_id'])): ?>
                  <div class="fw-semibold">Payment #<?= (int)$payment['payment_id'] ?></div>
                <?php else: ?>
                  <div class="fw-semibold">Cash on Delivery</div>
                  <div class="ea-meta">No gateway transaction</div>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-semibold">#<?= e((string)$payment['order_code']) ?></div>
                <div class="ea-meta">ID: <?= (int)$payment['order_id'] ?></div>
              </td>
              <td>
                <div class="fw-semibold"><?= e((string)($payment['full_name'] ?? '-')) ?></div>
                <div class="ea-meta"><?= e((string)($payment['email'] ?? '-')) ?></div>
              </td>
              <td class="fw-semibold">NPR <?= number_format((float)$payment['amount'], 2) ?></td>
              <td><?= e(strtoupper((string)$payment['payment_method'])) ?></td>
              <td><?= admin_payment_badge($payment['payment_status'] ?? 'unpaid') ?></td>
              <td class="ea-meta"><?= e(date('M j, Y, g:i a', strtotime((string)$payment['created_at']))) ?></td>
              <td>
                <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/public/order_success.php?code=<?= urlencode((string)$payment['order_code']) ?>">
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
