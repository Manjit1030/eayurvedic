<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('admin');
csrf_init();

$orderStatusOptions = [
  'pending'   => 'Pending',
  'shipped'   => 'Shipped',
  'delivered' => 'Delivered',
];

$paymentStatusOptions = [
  'unpaid' => 'Unpaid',
  'paid'   => 'Paid',
];

function normalize_admin_order_status($status) {
  $status = strtolower(trim((string)$status));
  // Handle legacy 'shipping' if it's still floating around in the DB
  if ($status === 'shipping') {
    return 'shipped';
  }
  return in_array($status, ['pending', 'shipped', 'delivered'], true) ? $status : 'pending';
}

function normalize_admin_payment_status($status) {
  $status = strtolower(trim((string)$status));
  return in_array($status, ['paid', 'pending', 'unpaid'], true) ? $status : 'unpaid';
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
  $newOrderStatus = normalize_admin_order_status($_POST['order_status'] ?? 'pending');
  $newPaymentStatus = normalize_admin_payment_status($_POST['payment_status'] ?? 'unpaid');

  if ($orderId > 0) {
    $stmt = db()->prepare("SELECT order_status, payment_status FROM orders WHERE id=? LIMIT 1");
    $stmt->execute([$orderId]);
    $currentOrder = $stmt->fetch();

    if ($currentOrder) {
      $currentOrderStatus = normalize_admin_order_status($currentOrder['order_status']);
      $currentPaymentStatus = normalize_admin_payment_status($currentOrder['payment_status']);

      // Final status locking:
      // 1. If current order status is 'delivered', it cannot be changed.
      // 2. If current payment status is 'paid', it cannot be changed.
      $finalOrderStatus = $currentOrderStatus === 'delivered' ? 'delivered' : $newOrderStatus;
      $finalPaymentStatus = $currentPaymentStatus === 'paid' ? 'paid' : $newPaymentStatus;

      $stmt = db()->prepare("
        UPDATE orders
        SET order_status = ?, payment_status = ?
        WHERE id = ?
      ");
      $stmt->execute([$finalOrderStatus, $finalPaymentStatus, $orderId]);
      $_SESSION['order_success_message'] = 'Order updated successfully.';
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
  if ($st === 'shipped')   return '<span class="badge text-bg-info">Shipped</span>';
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

<!-- Single shared form placed OUTSIDE the table to avoid invalid HTML -->
<form method="post" id="order-shared-form" style="display:none;">
  <?= csrf_field() ?>
  <input type="hidden" name="order_id"       id="shared-order-id"       value="">
  <input type="hidden" name="order_status"   id="shared-order-status"   value="">
  <input type="hidden" name="payment_status" id="shared-payment-status" value="">
</form>

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
              $currentOrderStatus   = normalize_admin_order_status($order['order_status'] ?? 'pending');
              $currentPaymentStatus = normalize_admin_payment_status($order['payment_status'] ?? ($isCashOrder ? 'unpaid' : 'paid'));
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
                  onchange="submitOrderUpdate(<?= (int)$order['id'] ?>, this.value, '<?= e($currentPaymentStatus) ?>')"
                  <?= $currentOrderStatus === 'delivered' ? 'disabled' : '' ?>
                >
                  <?php foreach ($orderStatusOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $currentOrderStatus === $value ? 'selected' : '' ?>>
                      <?= e($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <select
                  class="form-select form-select-sm"
                  aria-label="Payment status"
                  onchange="submitOrderUpdate(<?= (int)$order['id'] ?>, '<?= e($currentOrderStatus) ?>', this.value)"
                  <?= $currentPaymentStatus === 'paid' ? 'disabled' : '' ?>
                >
                  <?php foreach ($paymentStatusOptions as $value => $label): ?>
                    <?php
                      $isSelected = ($currentPaymentStatus === $value) || ($currentPaymentStatus === 'pending' && $value === 'unpaid');
                    ?>
                    <option value="<?= e($value) ?>" <?= $isSelected ? 'selected' : '' ?>>
                      <?= e($label) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
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

<script>
function submitOrderUpdate(orderId, orderStatus, paymentStatus) {
  document.getElementById('shared-order-id').value       = orderId;
  document.getElementById('shared-order-status').value   = orderStatus;
  document.getElementById('shared-payment-status').value = paymentStatus;
  document.getElementById('order-shared-form').submit();
}
</script>

<<<<<<< HEAD
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
=======
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
>>>>>>> 528f9600adde267f635884e5b6ba7c8521299fdd
