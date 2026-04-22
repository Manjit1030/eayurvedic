<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('admin');
csrf_init();

$orderStatusOptions = [
  'pending' => 'Pending',
  'shipping' => 'Shipping',
  'delivered' => 'Delivered',
];

$paymentStatusOptions = [
  'unpaid' => 'Unpaid',
  'paid' => 'Paid',
];

function normalize_admin_order_status($status) {
  $status = strtolower(trim((string)$status));
  if ($status === 'shipped') {
    return 'shipping';
  }
  return in_array($status, ['pending', 'shipping', 'delivered'], true) ? $status : 'pending';
}

function normalize_admin_payment_status($status) {
  $status = strtolower(trim((string)$status));
  return in_array($status, ['paid', 'unpaid'], true) ? $status : 'unpaid';
}

function normalize_admin_payment_method($method) {
  $method = strtolower(trim((string)$method));
  if (in_array($method, ['cash', 'cod'], true)) {
    return 'cash';
  }
  if (in_array($method, ['khalti', 'online'], true)) {
    return 'khalti';
  }
  return $method;
}

if (is_post()) {
  csrf_verify();

  $orderId = (int)($_POST['order_id'] ?? 0);
  $orderStatus = normalize_admin_order_status($_POST['order_status'] ?? 'pending');

  if ($orderId > 0 && isset($orderStatusOptions[$orderStatus])) {
    $stmt = db()->prepare("SELECT payment_method, payment_status FROM orders WHERE id=? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if ($order) {
      $paymentMethod = normalize_admin_payment_method($order['payment_method'] ?? '');

      if ($paymentMethod === 'cash') {
        $paymentStatus = normalize_admin_payment_status($_POST['payment_status'] ?? ($order['payment_status'] ?? 'unpaid'));

        if (isset($paymentStatusOptions[$paymentStatus])) {
          $stmt = db()->prepare("
            UPDATE orders
            SET order_status = ?, payment_status = ?
            WHERE id = ?
          ");
          $stmt->execute([$orderStatus, $paymentStatus, $orderId]);
          $_SESSION['order_success_message'] = 'Order updated successfully.';
        }
      } else {
        $stmt = db()->prepare("
          UPDATE orders
          SET order_status = ?
          WHERE id = ?
        ");
        $stmt->execute([$orderStatus, $orderId]);
        $_SESSION['order_success_message'] = 'Order updated successfully.';
      }
    }
  }

  redirect('/admin/orders_list.php');
}

$successMessage = $_SESSION['order_success_message'] ?? null;
unset($_SESSION['order_success_message']);

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

require_once __DIR__ . '/../app/includes/header.php';

function admin_order_badge_pay($st) {
  $st = normalize_admin_payment_status($st);
  if ($st === 'paid') return '<span class="badge text-bg-success">Paid</span>';
  return '<span class="badge text-bg-secondary">Unpaid</span>';
}

function admin_order_badge_status($st) {
  $st = normalize_admin_order_status($st);
  if ($st === 'delivered') return '<span class="badge text-bg-success">Delivered</span>';
  if ($st === 'shipping') return '<span class="badge text-bg-info">Shipping</span>';
  return '<span class="badge text-bg-warning">Pending</span>';
}
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Order Management</h1>
    <p class="ea-page-subtitle">Manage customer medicine orders, shipping progress, payment progress, and delivery status.</p>
  </div>
</section>

<?php if ($successMessage): ?>
  <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

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
            <?php
              $normalizedPaymentMethod = normalize_admin_payment_method($order['payment_method'] ?? '');
              $isCashOrder = $normalizedPaymentMethod === 'cash';
              $currentOrderStatus = normalize_admin_order_status($order['order_status'] ?? 'pending');
              $currentPaymentStatus = normalize_admin_payment_status($order['payment_status'] ?? ($isCashOrder ? 'unpaid' : 'paid'));
              $formId = 'order-update-' . (int)$order['id'];
              $orderStatusInputId = 'order-status-input-' . (int)$order['id'];
              $paymentStatusInputId = 'payment-status-input-' . (int)$order['id'];
            ?>
            <tr>
              <td>
                <div class="fw-semibold">#<?= e((string)$order['order_code']) ?></div>
                <div class="ea-meta">ID: <?= (int)$order['id'] ?></div>
              </td>
              <td class="fw-semibold"><?= e((string)($order['full_name'] ?? '-')) ?></td>
              <td><?= e((string)($order['email'] ?? '-')) ?></td>
              <td class="fw-semibold">NPR <?= number_format((float)$order['total_amount'], 2) ?></td>
              <td>
                <select
                  class="form-select form-select-sm"
                  aria-label="Order status"
                  onchange="document.getElementById('<?= e($orderStatusInputId) ?>').value = this.value"
                >
                  <?php foreach ($orderStatusOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $currentOrderStatus === $value ? 'selected' : '' ?>>
                      <?= e($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <?php if ($isCashOrder): ?>
                  <select
                    class="form-select form-select-sm"
                    aria-label="Payment status"
                    onchange="document.getElementById('<?= e($paymentStatusInputId) ?>').value = this.value"
                  >
                    <?php foreach ($paymentStatusOptions as $value => $label): ?>
                      <option value="<?= e($value) ?>" <?= $currentPaymentStatus === $value ? 'selected' : '' ?>>
                        <?= e($label) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php else: ?>
                  <?= admin_order_badge_pay($currentPaymentStatus) ?>
                <?php endif; ?>
              </td>
              <td><?= e(strtoupper((string)$order['payment_method'])) ?></td>
              <td class="ea-meta"><?= e(date('M j, Y, g:i a', strtotime((string)$order['created_at']))) ?></td>
              <td>
                <form method="post" id="<?= e($formId) ?>" class="d-inline-block me-2 mb-1">
                  <?= csrf_field() ?>
                  <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                  <input type="hidden" name="order_status" id="<?= e($orderStatusInputId) ?>" value="<?= e($currentOrderStatus) ?>">
                  <?php if ($isCashOrder): ?>
                    <input type="hidden" name="payment_status" id="<?= e($paymentStatusInputId) ?>" value="<?= e($currentPaymentStatus) ?>">
                  <?php endif; ?>
                  <button class="btn btn-sm btn-outline-success">Update</button>
                </form>
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
