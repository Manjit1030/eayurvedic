<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

$code = $_GET['code'] ?? '';
?>

<div class="row justify-content-center mt-5">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 text-center p-5">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger text-white mb-4 mx-auto" style="width:72px;height:72px;">
                <i class="bi bi-x-lg fs-1"></i>
            </div>
            <h1 class="h3 fw-bold text-danger">Payment Failed</h1>
            <p class="text-muted mb-4">
                We're sorry, but your payment could not be processed at this time. 
                If any amount was deducted, it will be refunded according to your bank/wallet statement.
            </p>

            <?php if ($code): ?>
                <div class="alert alert-secondary py-2 small mb-4">
                    Order Reference: <strong>#<?= e($code) ?></strong>
                </div>
            <?php endif; ?>

            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="<?= BASE_URL ?>/public/cart.php" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-cart me-1"></i> View Cart
                </a>
                <a href="<?= BASE_URL ?>/public/checkout.php" class="btn btn-success px-4">
                    <i class="bi bi-arrow-repeat me-1"></i> Try Again
                </a>
            </div>
            
            <p class="mt-4 small text-muted">
                Need help? <a href="#" class="text-success">Contact Support</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
