<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/algorithms.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');
csrf_init();

$user = current_user();

/* Load user addresses */
$stmt = db()->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC");
$stmt->execute([$user['id']]);
$addresses = $stmt->fetchAll();

/* Get cart */
$stmt = db()->prepare("SELECT id FROM carts WHERE user_id=? LIMIT 1");
$stmt->execute([$user['id']]);
$cart = $stmt->fetch();

if (!$cart) {
  echo '<div class="alert alert-info">Your cart is empty. <a href="'.BASE_URL.'/public/shop.php">Go shopping</a></div>';
  require_once __DIR__ . '/../app/includes/footer.php';
  exit;
}

$cart_id = (int)$cart['id'];

/* Load cart items */
$stmt = db()->prepare("
  SELECT ci.qty, ci.price_at_time, p.name, p.stock
  FROM cart_items ci
  JOIN products p ON p.id = ci.product_id
  WHERE ci.cart_id=?
  ORDER BY ci.id DESC
");
$stmt->execute([$cart_id]);
$items = $stmt->fetchAll();

if (!$items) {
  echo '<div class="alert alert-info">Your cart is empty. <a href="'.BASE_URL.'/public/shop.php">Go shopping</a></div>';
  require_once __DIR__ . '/../app/includes/footer.php';
  exit;
}

/* Subtotal */
$subtotal = 0;
foreach ($items as $it) {
  $subtotal += ((float)$it['price_at_time'] * (int)$it['qty']);
}

/* ✅ Algorithm #1 used here */
$totals = algo_cart_totals($subtotal);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 fw-bold mb-0">Checkout</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>/public/cart.php">
    <i class="bi bi-arrow-left"></i> Back to Cart
  </a>
</div>

<div class="alert alert-secondary">
  <b>Algorithm Mode:</b> <?= e($totals['mode']) ?>
  <span class="text-muted"> (Toggle <code>ALGO_ENABLED</code> in config.php)</span>
</div>

<div class="row g-4">

  <!-- LEFT: Address + Place Order -->
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>Delivery Address</h2>

        <?php if (!$addresses): ?>
          <div class="alert alert-warning mb-0">
            No address found. Please add an address first:
            <a href="<?= BASE_URL ?>/user/add_address.php">Add Address</a>
          </div>
        <?php else: ?>

          <form method="post" action="<?= BASE_URL ?>/public/place_order.php">
            <?= csrf_field() ?>

            <?php foreach ($addresses as $a): ?>
              <div class="form-check border rounded p-3 mb-2">
                <input class="form-check-input" type="radio" name="address_id"
                       value="<?= (int)$a['id'] ?>" id="addr<?= (int)$a['id'] ?>"
                       <?= ((int)$a['is_default'] === 1) ? 'checked' : '' ?> required>
                <label class="form-check-label w-100" for="addr<?= (int)$a['id'] ?>">
                  <div class="fw-semibold"><?= e($a['label'] ?? 'Address') ?></div>
                  <div class="text-muted small">
                    <?= e($a['street'] ?? '') ?>,
                    <?= e($a['area'] ?? '') ?>,
                    <?= e($a['city'] ?? '') ?>,
                    <?= e($a['district'] ?? '') ?>,
                    <?= e($a['province'] ?? '') ?>
                    <?= e($a['postal_code'] ?? '') ?>
                  </div>
                </label>
              </div>
            <?php endforeach; ?>

            <div class="mt-3">
              <label class="form-label fw-semibold">Order Notes (optional)</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="Any special delivery notes..."></textarea>
            </div>

            <hr class="my-4">

            <div class="mb-4">
              <h2 class="h6 fw-bold mb-3"><i class="bi bi-credit-card me-2"></i>Payment Method</h2>
              <div class="form-check border rounded p-3 mb-2">
                <input class="form-check-input" type="radio" name="payment_method" value="cash" id="payCash" checked required>
                <label class="form-check-label w-100" for="payCash">
                  <div class="fw-semibold">Cash on Delivery</div>
                  <div class="text-muted small">Pay when you receive your ayurvedic products.</div>
                </label>
              </div>
              <div class="form-check border rounded p-3 mb-2">
                <input class="form-check-input" type="radio" name="payment_method" value="khalti" id="payKhalti" required>
                <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="payKhalti">
                  <div>
                    <div class="fw-semibold">Khalti Payment</div>
                    <div class="text-muted small">Pay securely using Khalti Wallet.</div>
                  </div>
                  <img src="https://khalti.com/static/img/logo1.png" alt="Khalti" style="height: 24px;">
                </label>
              </div>
            </div>

            <div class="mt-4 d-grid">
              <button class="btn btn-success btn-lg">
                <i class="bi bi-bag-check me-2"></i> Place Order
              </button>
              <div class="text-muted small mt-2">
                Payment (eSewa/Khalti) will be after order is created.
              </div>
            </div>
          </form>

        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RIGHT: Summary -->
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Order Summary</h2>

        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= e($it['name']) ?></div>
                    <div class="text-muted small">Qty: <?= (int)$it['qty'] ?></div>
                  </td>
                  <td class="text-end">
                    NPR <?= number_format(((float)$it['price_at_time'] * (int)$it['qty']), 2) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <hr>

        <div class="d-flex justify-content-between mb-2">
          <div class="text-muted">Subtotal</div>
          <div class="fw-semibold">NPR <?= number_format($totals['subtotal'], 2) ?></div>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <div class="text-muted">Shipping</div>
          <div class="fw-semibold">NPR <?= number_format($totals['shipping'], 2) ?></div>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <div class="text-muted">Tax</div>
          <div class="fw-semibold">NPR <?= number_format($totals['tax'], 2) ?></div>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <div class="text-muted">Discount</div>
          <div class="fw-semibold">- NPR <?= number_format($totals['discount'], 2) ?></div>
        </div>

        <hr>

        <div class="d-flex justify-content-between">
          <div class="fw-bold">Total</div>
          <div class="fw-bold fs-5">NPR <?= number_format($totals['total'], 2) ?></div>
        </div>

        <div class="small text-muted mt-2">
          Free shipping above NPR 1500 (when algorithm enabled).
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
