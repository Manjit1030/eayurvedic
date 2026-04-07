<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/auth.php';

auth_init();
$u = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?></title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>/public/index.php">
      <i class="bi bi-heart-pulse-fill"></i>
      eAyurvedic
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
            aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <!-- Left links -->
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/public/index.php">
            <i class="bi bi-house-door me-1"></i>Home
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/public/shop.php">
            <i class="bi bi-bag-heart me-1"></i>Shop
          </a>
        </li>

        <?php if ($u && ($u['role'] ?? '') === 'user'): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/public/cart.php">
              <i class="bi bi-cart3 me-1"></i>Cart
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- Right links -->
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

        <?php if (!$u): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/public/login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
          </li>

          <li class="nav-item">
            <a class="btn btn-light btn-sm fw-semibold" href="<?= BASE_URL ?>/public/register.php">
              <i class="bi bi-person-plus me-1"></i>Register
            </a>
          </li>

        <?php else: ?>
          <?php if (($u['role'] ?? '') === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/admin/index.php">
                <i class="bi bi-speedometer2 me-1"></i>Admin Panel
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/user/index.php">
                <i class="bi bi-person-badge me-1"></i>Dashboard
              </a>
            </li>
          <?php endif; ?>

          <!-- User dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
               href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="badge bg-light text-success rounded-pill">
                <i class="bi bi-person-circle me-1"></i><?= e($u['full_name'] ?? 'User') ?>
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (($u['role'] ?? '') === 'user'): ?>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/user/addresses.php">
                    <i class="bi bi-geo-alt me-2"></i>My Addresses
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/public/cart.php">
                    <i class="bi bi-cart3 me-2"></i>My Cart
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>

              <li>
                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/public/logout.php">
                  <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
              </li>
            </ul>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
