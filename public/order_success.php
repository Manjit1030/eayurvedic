<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

$code = $_GET['code'] ?? '';

if (!$code) {
    echo '<div class="alert alert-danger">Invalid order request.</div>';
    require_once __DIR__ . '/../app/includes/footer.php';
    exit;
}

/* Load Order */
$stmt = db()->prepare("
    SELECT o.*, u.full_name, ad.label, ad.street, ad.area, ad.city
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN user_addresses ad ON ad.id = o.address_id
    WHERE o.order_code=? LIMIT 1
");
$stmt->execute([$code]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="alert alert-danger">Order not found.</div>';
    require_once __DIR__ . '/../app/includes/footer.php';
    exit;
}

/* Load Items */
$stmt = db()->prepare("SELECT * FROM order_items WHERE order_id=?");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 text-center p-4 mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white mb-3 mx-auto" style="width:72px;height:72px;">
                <i class="bi bi-check-lg fs-1"></i>
            </div>
            <h1 class="h3 fw-bold">Order Placed Successfully!</h1>
            <p class="text-muted">Order Code: <span class="fw-bold text-dark">#<?= e($order['order_code']) ?></span></p>
            <p class="mb-0 small">Your payment was successful and your order has been placed.</p>
        </div>

        <div class="row g-4 text-start">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold border-bottom pb-2 mb-3">Order Information</h2>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Payment Status</span>
                            <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                <?= strtoupper(e($order['payment_status'])) ?>
                            </span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Payment Method</span>
                            <span class="fw-semibold small"><?= strtoupper(e($order['payment_method'])) ?></span>
                        </div>
                        <div class="mb-0">
                            <span class="text-muted small d-block">Order Date</span>
                            <span class="small"><?= date('M j, Y, g:i a', strtotime($order['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold border-bottom pb-2 mb-3">Shipping Address</h2>
                        <div class="small">
                            <strong><?= e($order['label'] ?: 'Home') ?></strong><br>
                            <?= e($order['street']) ?>, <?= e($order['area']) ?><br>
                            <?= e($order['city']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h6 fw-bold border-bottom pb-2 mb-3">Items Ordered</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr class="text-muted small">
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= e($it['product_name']) ?></td>
                                <td class="text-center small"><?= (int)$it['qty'] ?></td>
                                <td class="text-end small">NPR <?= number_format($it['line_total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Final Total</td>
                                <td class="text-end fs-6 text-success">NPR <?= number_format($order['total_amount'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="<?= BASE_URL ?>/user/index.php" class="btn btn-outline-success">
                <i class="bi bi-speedometer2 me-1"></i> Go to Dashboard
            </a>
            <a href="<?= BASE_URL ?>/public/shop.php" class="btn btn-success ms-2">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
