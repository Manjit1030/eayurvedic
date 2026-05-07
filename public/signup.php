<?php
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-auth-shell">
  <div class="w-100" style="max-width: 920px;">
    <div class="text-center mb-4">
      <div class="ea-auth-logo mx-auto mb-3"><i class="bi bi-person-plus"></i></div>
      <h1 class="mb-2" style="font-size:clamp(2.3rem,4vw,3.2rem);">Signup</h1>
      <p class="ea-subtle mb-0">Choose the account type that fits how you want to use eAyurvedic.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <a class="text-decoration-none text-reset d-block h-100" href="<?= BASE_URL ?>/public/register.php">
          <div class="ea-quick-card ea-card-hover h-100">
            <span class="ea-icon-pill"><i class="bi bi-person"></i></span>
            <h3>Signup as User</h3>
            <p>Create a patient account to submit health concerns, view solutions, shop medicines, and manage orders.</p>
            <span class="btn btn-success">Continue</span>
          </div>
        </a>
      </div>

      <div class="col-md-6">
        <a class="text-decoration-none text-reset d-block h-100" href="<?= BASE_URL ?>/public/doctor_register.php">
          <div class="ea-quick-card ea-card-hover h-100">
            <span class="ea-icon-pill"><i class="bi bi-heart-pulse"></i></span>
            <h3>Signup as Doctor</h3>
            <p>Create a doctor account and submit professional registration for admin verification.</p>
            <span class="btn btn-success">Continue</span>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
