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

<h1 class="h4 fw-bold mb-3">My Cart</h1>

<?php if (!$cart || !$items): ?>
  <div class="alert alert-info">
    Your cart is empty. <a href="<?= BASE_URL ?>/public/shop.php">Go shopping</a>
  </div>
<?php else: ?>

<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th>Item</th>
        <th style="width:120px;">Price</th>
        <th style="width:150px;">Qty</th>
        <th style="width:120px;">Total</th>
        <th style="width:120px;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <?php $lineTotal = (float)$it['price_at_time'] * (int)$it['qty']; ?>
        <tr>
          <td>
            <div class="d-flex gap-2 align-items-center">
              <?php if (!empty($it['main_image'])): ?>
                <img src="<?= BASE_URL ?>/public/<?= e($it['main_image']) ?>"
                     style="width:60px;height:60px;object-fit:cover;"
                     class="img-thumbnail" alt="Product">
              <?php endif; ?>
              <div>
                <div class="fw-semibold"><?= e($it['name']) ?></div>
                <div class="text-muted small">Stock: <?= (int)$it['stock'] ?></div>
              </div>
            </div>
          </td>

          <td>NPR <?= e($it['price_at_time']) ?></td>

          <td>
            <form method="post" action="<?= BASE_URL ?>/public/cart_update.php" class="d-flex gap-2">
              <?= csrf_field() ?>
              <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
              <input type="number" min="1" max="<?= (int)$it['stock'] ?>"
                     name="qty" class="form-control form-control-sm"
                     value="<?= (int)$it['qty'] ?>" style="width:80px;">
              <button class="btn btn-sm btn-outline-primary">Update</button>
            </form>
          </td>

          <td>NPR <?= number_format($lineTotal, 2) ?></td>

          <td>
            <form method="post" action="<?= BASE_URL ?>/public/cart_remove.php"
                  onsubmit="return confirm('Remove this item?');">
              <?= csrf_field() ?>
              <input type="hidden" name="cart_item_id" value="<?= (int)$it['cart_item_id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card shadow-sm">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div class="fw-bold">Subtotal</div>
    <div class="fw-bold">NPR <?= number_format($subtotal, 2) ?></div>
  </div>
</div>

<div class="mt-3 d-flex gap-2">
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/public/shop.php">Continue Shopping</a>
  <a class="btn btn-success" href="<?= BASE_URL ?>/public/checkout.php" aria-disabled="true">Checkout (next)</a>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
