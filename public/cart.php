<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');
csrf_init();

$user = current_user();

/* Find cart */
$stmt = db()->prepare("SELECT id FROM carts WHERE user_id=? LIMIT 1");
$stmt->execute([$user['id']]);
$cart = $stmt->fetch();

$items = [];
$subtotal = 0;

if ($cart) {
    $cart_id = (int)$cart['id'];

    $stmt = db()->prepare("
        SELECT ci.id AS cart_item_id, ci.qty, ci.price_at_time,
               p.id AS product_id, p.name, p.main_image, p.stock
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.id DESC
    ");
    $stmt->execute([$cart_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $it) {
        $subtotal += ((float)$it['price_at_time'] * (int)$it['qty']);
    }
}
?>

<style>
  .cart-shell {
    display: grid;
    grid-template-columns: minmax(0, 1.65fr) 360px;
    gap: 1.5rem;
    align-items: start;
  }

  .cart-table-wrap,
  .cart-summary {
    background: #fff;
    border: 1px solid rgba(26, 71, 42, 0.08);
    border-radius: 24px;
    box-shadow: var(--ea-shadow);
  }

  .cart-thumb,
  .cart-thumb-placeholder {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(201,168,76,0.16), rgba(26,71,42,0.08));
    border: 1px solid rgba(26, 71, 42, 0.08);
  }

  .cart-qty {
    max-width: 92px;
  }

  .cart-summary-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .cart-total {
    color: var(--ea-gold);
    font-size: 1.35rem;
    font-weight: 700;
  }

  @media (max-width: 991.98px) {
    .cart-shell {
      grid-template-columns: 1fr;
    }
  }
</style>

<section class="mb-4 d-flex flex-wrap align-items-end justify-content-between gap-3">
  <div>
    <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;color:var(--ea-gold);">Cart</p>
    <h1 class="mb-2" style="font-size:clamp(2.2rem,4vw,3.4rem);">Your selected medicines</h1>
    <p class="ea-subtle mb-0">Review quantities, update items, and continue to your premium checkout flow.</p>
  </div>
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/public/shop.php">Continue Shopping</a>
</section>

<?php if (!$cart || !$items): ?>
  <div class="alert alert-info">
    Your cart is empty. <a href="<?= BASE_URL ?>/public/shop.php">Go shopping</a>
  </div>
<?php else: ?>

<section class="cart-shell">
  <div class="cart-table-wrap p-3 p-lg-4">
    <div class="table-responsive shadow-none">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Item</th>
            <th style="width:140px;">Price</th>
            <th style="width:180px;">Quantity</th>
            <th style="width:140px;">Total</th>
            <th style="width:130px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <?php $lineTotal = (float)$it['price_at_time'] * (int)$it['qty']; ?>
            <tr>
              <td>
                <div class="d-flex gap-3 align-items-center">
                  <?php if (!empty($it['main_image'])): ?>
                    <img src="<?= BASE_URL ?>/public/<?= e($it['main_image']) ?>"
                         class="cart-thumb" alt="Product">
                  <?php else: ?>
                    <div class="cart-thumb-placeholder d-flex align-items-center justify-content-center">
                      <i class="bi bi-capsule" style="color:var(--ea-gold);"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <div class="fw-semibold"><?= e($it['name']) ?></div>
                    <div class="ea-subtle small">Stock: <?= (int)$it['stock'] ?></div>
                  </div>
                </div>
              </td>

              <td class="fw-semibold">NPR <?= e($it['price_at_time']) ?></td>

              <td>
                <form method="post" action="<?= BASE_URL ?>/public/cart_update.php" class="d-flex gap-2 align-items-center">
                  <?= csrf_field() ?>
                  <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
                  <input type="number" min="1" max="<?= (int)$it['stock'] ?>"
                         name="qty" class="form-control cart-qty"
                         value="<?= (int)$it['qty'] ?>">
                  <button class="btn btn-outline-success">Update</button>
                </form>
              </td>

              <td class="fw-semibold">NPR <?= number_format($lineTotal, 2) ?></td>

              <td>
                <form method="post" action="<?= BASE_URL ?>/public/cart_remove.php"
                      onsubmit="return confirm('Remove this item?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
                  <button class="btn btn-outline-danger">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <aside class="cart-summary p-4">
    <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.2em;color:var(--ea-gold);">Order Summary</p>
    <h2 class="h2 mb-4">Totals</h2>

    <div class="cart-summary-row">
      <span class="ea-subtle">Subtotal</span>
      <strong>NPR <?= number_format($subtotal, 2) ?></strong>
    </div>
    <div class="cart-summary-row">
      <span class="ea-subtle">Shipping</span>
      <strong>NPR 0.00</strong>
    </div>
    <div class="cart-summary-row">
      <span class="ea-subtle">Tax</span>
      <strong>NPR 0.00</strong>
    </div>

    <hr class="my-4">

    <div class="cart-summary-row mb-4">
      <span class="fw-semibold">Total</span>
      <span class="cart-total">NPR <?= number_format($subtotal, 2) ?></span>
    </div>

    <a class="btn btn-success btn-lg w-100 mb-3" href="<?= BASE_URL ?>/public/checkout.php" aria-disabled="true">
      Proceed to Checkout
    </a>
    <div class="small ea-subtle">You can still update items before confirming the final order on the checkout page.</div>
  </aside>
</section>

<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
