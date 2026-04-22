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

<style>
  .checkout-shell {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) 400px;
    gap: 1.5rem;
    align-items: start;
  }

  .checkout-panel,
  .checkout-summary {
    background: #fff;
    border: 1px solid rgba(26, 71, 42, 0.08);
    border-radius: 24px;
    box-shadow: var(--ea-shadow);
  }

  .checkout-address-option,
  .checkout-payment-option {
    border: 1px solid rgba(26, 71, 42, 0.08);
    border-radius: 18px;
    background: #fcfbf8;
    transition: border-color 0.2s ease, background 0.2s ease;
  }

  .checkout-address-option:has(.form-check-input:checked),
  .checkout-payment-option:has(.form-check-input:checked) {
    border-color: rgba(26, 71, 42, 0.28);
    background: rgba(26, 71, 42, 0.04);
  }

  .form-floating > .form-control,
  .form-floating > .form-control-plaintext,
  .form-floating > .form-select {
    min-height: calc(3.5rem + 2px);
  }

  .khalti-label {
    background: #5c2d91;
    color: #fff;
    border-radius: 14px;
    padding: 0.4rem 0.75rem;
    font-weight: 600;
    font-size: 0.9rem;
  }

  .checkout-summary-item + .checkout-summary-item {
    border-top: 1px solid rgba(26, 71, 42, 0.08);
  }

  .checkout-total {
    color: var(--ea-gold);
    font-size: 1.4rem;
    font-weight: 700;
  }

  @media (max-width: 991.98px) {
    .checkout-shell {
      grid-template-columns: 1fr;
    }
  }
</style>

<section class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
  <div>
    <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;color:var(--ea-gold);">Checkout</p>
    <h1 class="mb-2" style="font-size:clamp(2.2rem,4vw,3.4rem);">Complete your wellness order</h1>
    <p class="ea-subtle mb-0">Choose your delivery address and payment method, then confirm the order.</p>
  </div>
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/public/cart.php">
    <i class="bi bi-arrow-left me-1"></i>Back to Cart
  </a>
</section>

<section class="checkout-shell">
  <div class="checkout-panel p-4 p-lg-5">
    <h2 class="h2 mb-4">Delivery & Payment</h2>

    <?php if (!$addresses): ?>
      <div class="alert alert-warning mb-0">
        No address found. Please add an address first:
        <a href="<?= BASE_URL ?>/user/add_address.php">Add Address</a>
      </div>
    <?php else: ?>

      <form method="post" action="<?= BASE_URL ?>/public/place_order.php">
        <?= csrf_field() ?>

        <div class="mb-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="ea-icon-pill"><i class="bi bi-geo-alt"></i></span>
            <div>
              <h3 class="mb-0">Choose Address</h3>
              <div class="ea-subtle small">Select the destination for delivery.</div>
            </div>
          </div>

          <?php foreach ($addresses as $a): ?>
            <div class="form-check checkout-address-option p-3 mb-3">
              <input class="form-check-input" type="radio" name="address_id"
                     value="<?= (int)$a['id'] ?>" id="addr<?= (int)$a['id'] ?>"
                     <?= ((int)$a['is_default'] === 1) ? 'checked' : '' ?> required>
              <label class="form-check-label w-100 ms-2" for="addr<?= (int)$a['id'] ?>">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                  <div class="fw-semibold"><?= e($a['label'] ?? 'Address') ?></div>
                  <?php if ((int)$a['is_default'] === 1): ?>
                    <span class="badge rounded-pill" style="background:rgba(201,168,76,0.18);color:var(--ea-forest);">Default</span>
                  <?php endif; ?>
                </div>
                <div class="ea-subtle small mt-1">
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
        </div>

        <div class="mb-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="ea-icon-pill"><i class="bi bi-journal-text"></i></span>
            <div>
              <h3 class="mb-0">Order Notes</h3>
              <div class="ea-subtle small">Optional delivery instructions.</div>
            </div>
          </div>

          <div class="form-floating">
            <textarea name="notes" class="form-control" placeholder="Any special delivery notes..." style="height: 120px"></textarea>
            <label>Any special delivery notes...</label>
          </div>
        </div>

        <div class="mb-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="ea-icon-pill"><i class="bi bi-credit-card"></i></span>
            <div>
              <h3 class="mb-0">Payment Method</h3>
              <div class="ea-subtle small">Choose how you want to pay.</div>
            </div>
          </div>

          <div class="form-check checkout-payment-option p-3 mb-3">
            <input class="form-check-input" type="radio" name="payment_method" value="cash" id="payCash" checked required>
            <label class="form-check-label w-100 ms-2" for="payCash">
              <div class="fw-semibold">Cash on Delivery</div>
              <div class="ea-subtle small">Pay when you receive your ayurvedic products.</div>
            </label>
          </div>

          <div class="form-check checkout-payment-option p-3 mb-2">
            <input class="form-check-input" type="radio" name="payment_method" value="khalti" id="payKhalti" required>
            <label class="form-check-label d-flex justify-content-between align-items-center gap-3 w-100 ms-2" for="payKhalti">
              <div>
                <div class="fw-semibold">Khalti Payment</div>
                <div class="ea-subtle small">Pay securely using Khalti Wallet.</div>
              </div>
              <span class="khalti-label">Khalti</span>
            </label>
          </div>
        </div>

        <div class="d-grid">
          <button class="btn btn-success btn-lg">
            <i class="bi bi-bag-check me-2"></i>Place Order
          </button>
          <div class="small ea-subtle mt-2">
            Payment (eSewa/Khalti) will be after order is created.
          </div>
        </div>
      </form>

    <?php endif; ?>
  </div>

  <aside class="checkout-summary p-4">
    <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;color:var(--ea-gold);">Order Summary</p>
    <h2 class="h2 mb-4">Your Basket</h2>

    <div class="mb-4">
      <?php foreach ($items as $it): ?>
        <div class="checkout-summary-item py-3 d-flex justify-content-between gap-3">
          <div>
            <div class="fw-semibold"><?= e($it['name']) ?></div>
            <div class="ea-subtle small">Qty: <?= (int)$it['qty'] ?></div>
          </div>
          <div class="fw-semibold">
            NPR <?= number_format(((float)$it['price_at_time'] * (int)$it['qty']), 2) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-between mb-3">
      <span class="ea-subtle">Subtotal</span>
      <strong>NPR <?= number_format($totals['subtotal'], 2) ?></strong>
    </div>

    <div class="d-flex justify-content-between mb-3">
      <span class="ea-subtle">Shipping</span>
      <strong>NPR <?= number_format($totals['shipping'], 2) ?></strong>
    </div>

    <div class="d-flex justify-content-between mb-3">
      <span class="ea-subtle">Tax</span>
      <strong>NPR <?= number_format($totals['tax'], 2) ?></strong>
    </div>

    <div class="d-flex justify-content-between mb-3">
      <span class="ea-subtle">Discount</span>
      <strong>- NPR <?= number_format($totals['discount'], 2) ?></strong>
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Total</span>
      <span class="checkout-total">NPR <?= number_format($totals['total'], 2) ?></span>
    </div>

  </aside>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
