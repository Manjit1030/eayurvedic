<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/auth.php';

auth_init();
$u = current_user();
$currentRole = $u['role'] ?? '';
$userHasOrdersPage = file_exists(__DIR__ . '/../../user/orders.php');
$userHasProfilePage = file_exists(__DIR__ . '/../../user/profile.php');
$adminHasOrdersPage = file_exists(__DIR__ . '/../../admin/orders_list.php');
$adminHasPaymentsPage = file_exists(__DIR__ . '/../../admin/payments_list.php');
$brandHref = BASE_URL . '/public/index.php';
$brandLabel = 'eAyurvedic';

if ($currentRole === 'user') {
  $brandHref = BASE_URL . '/user/index.php';
} elseif ($currentRole === 'admin') {
  $brandHref = BASE_URL . '/admin/index.php';
  $brandLabel = 'eAyurvedic Admin';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root{
      --ea-forest: #1a472a;
      --ea-forest-deep: #12311d;
      --ea-gold: #c9a84c;
      --ea-cream: #faf7f2;
      --ea-white: #ffffff;
      --ea-text: #1c1c1c;
      --ea-muted: #6f6a61;
      --ea-border: rgba(26, 71, 42, 0.12);
      --ea-shadow: 0 4px 24px rgba(0,0,0,0.07);
      --ea-radius: 16px;
      --ea-radius-sm: 8px;
    }

    html, body {
      background: var(--ea-cream);
      color: var(--ea-text);
      font-family: "DM Sans", sans-serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background-image:
        radial-gradient(circle at top left, rgba(201, 168, 76, 0.10), transparent 24%),
        radial-gradient(circle at bottom right, rgba(26, 71, 42, 0.08), transparent 22%);
    }

    main.container {
      flex: 1;
      max-width: 1240px;
      padding-top: 2rem;
      padding-bottom: 2rem;
    }

    h1, h2, h3, h4, h5, h6, .font-display, .navbar-brand span {
      font-family: "Cormorant Garamond", serif;
      letter-spacing: 0.02em;
    }

    a {
      color: var(--ea-forest);
    }

    a:hover {
      color: var(--ea-forest-deep);
    }

    .navbar.ea-navbar {
      position: sticky;
      top: 0;
      z-index: 1030;
      background: rgba(26, 71, 42, 0.96);
      backdrop-filter: blur(14px);
      box-shadow: 0 10px 30px rgba(18, 49, 29, 0.18);
    }

    .ea-navbar .container {
      max-width: 1240px;
    }

    .ea-brand-mark {
      width: 42px;
      height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 14px;
      background: linear-gradient(145deg, rgba(201, 168, 76, 0.24), rgba(201, 168, 76, 0.08));
      color: var(--ea-gold);
      box-shadow: inset 0 0 0 1px rgba(201, 168, 76, 0.22);
    }

    .ea-navbar .navbar-brand {
      color: var(--ea-gold);
      font-size: 1.95rem;
      font-weight: 700;
    }

    .ea-navbar .navbar-brand:hover,
    .ea-navbar .navbar-brand:focus {
      color: #ead9a4;
    }

    .ea-navbar .nav-link {
      position: relative;
      color: rgba(255,255,255,0.88);
      font-weight: 500;
      padding: 0.75rem 0.95rem;
      transition: color 0.2s ease;
    }

    .ea-navbar .nav-link:hover,
    .ea-navbar .nav-link:focus,
    .ea-navbar .nav-link.show {
      color: #fff;
    }

    .ea-navbar .nav-link::after {
      content: "";
      position: absolute;
      left: 0.95rem;
      right: 0.95rem;
      bottom: 0.38rem;
      height: 2px;
      border-radius: 999px;
      background: var(--ea-gold);
      transform: scaleX(0);
      transform-origin: center;
      transition: transform 0.2s ease;
    }

    .ea-navbar .nav-link:hover::after,
    .ea-navbar .nav-link:focus::after,
    .ea-navbar .nav-link.show::after {
      transform: scaleX(1);
    }

    .ea-navbar .navbar-toggler {
      border: 1px solid rgba(255,255,255,0.18);
      padding: 0.5rem 0.7rem;
    }

    .ea-navbar .navbar-toggler:focus {
      box-shadow: 0 0 0 0.2rem rgba(201, 168, 76, 0.28);
    }

    .ea-btn-gold,
    .btn.ea-btn-gold {
      background: linear-gradient(135deg, #d6b45c, var(--ea-gold));
      border-color: transparent;
      color: #1f1b14;
      font-weight: 600;
      border-radius: var(--ea-radius-sm);
      padding: 0.72rem 1.2rem;
      box-shadow: 0 10px 20px rgba(201, 168, 76, 0.18);
    }

    .ea-btn-gold:hover,
    .ea-btn-gold:focus,
    .btn.ea-btn-gold:hover,
    .btn.ea-btn-gold:focus {
      background: linear-gradient(135deg, #e0c06d, #d3af4c);
      color: #1f1b14;
      transform: translateY(-1px);
    }

    .btn,
    .form-control,
    .form-select,
    .input-group-text,
    .dropdown-menu {
      border-radius: var(--ea-radius-sm);
    }

    .btn {
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .btn-success,
    .btn.btn-success {
      background: var(--ea-forest);
      border-color: var(--ea-forest);
    }

    .btn-success:hover,
    .btn-success:focus,
    .btn.btn-success:hover,
    .btn.btn-success:focus {
      background: var(--ea-forest-deep);
      border-color: var(--ea-forest-deep);
      transform: translateY(-1px);
    }

    .btn-outline-success {
      color: var(--ea-forest);
      border-color: rgba(26, 71, 42, 0.32);
    }

    .btn-outline-success:hover,
    .btn-outline-success:focus {
      background: var(--ea-forest);
      border-color: var(--ea-forest);
      color: #fff;
    }

    .btn-outline-secondary {
      color: var(--ea-text);
      border-color: rgba(28, 28, 28, 0.16);
    }

    .btn-outline-secondary:hover,
    .btn-outline-secondary:focus {
      background: #f1ebe0;
      border-color: #f1ebe0;
      color: var(--ea-text);
    }

    .card,
    .alert,
    .dropdown-menu,
    .table,
    .list-group-item,
    .form-check,
    .modal-content {
      border-color: var(--ea-border);
    }

    .card,
    .alert,
    .dropdown-menu,
    .table-responsive,
    .list-group,
    .ea-surface {
      box-shadow: var(--ea-shadow);
    }

    .card,
    .alert,
    .table-responsive,
    .list-group,
    .ea-surface {
      border-radius: var(--ea-radius);
      overflow: hidden;
    }

    .card {
      background: var(--ea-white);
      border: 1px solid var(--ea-border);
    }

    .ea-card-hover {
      transition: transform 0.22s ease, box-shadow 0.22s ease;
    }

    .ea-card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 18px 40px rgba(18, 49, 29, 0.10);
    }

    .ea-badge-soft,
    .ea-icon-pill {
      background: rgba(201, 168, 76, 0.16);
      color: var(--ea-forest);
    }

    .ea-icon-pill {
      width: 52px;
      height: 52px;
      border-radius: 18px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      box-shadow: inset 0 0 0 1px rgba(201, 168, 76, 0.15);
    }

    .ea-section-heading {
      font-size: clamp(2rem, 4vw, 3.2rem);
      line-height: 0.95;
    }

    .ea-subtle {
      color: var(--ea-muted);
    }

    .ea-form-control:focus,
    .form-control:focus,
    .form-select:focus {
      border-color: rgba(26, 71, 42, 0.45);
      box-shadow: 0 0 0 0.22rem rgba(26, 71, 42, 0.12);
    }

    .input-group-text {
      background: #f7f2e8;
      border-color: rgba(26, 71, 42, 0.14);
      color: var(--ea-forest);
    }

    .badge.text-bg-success {
      background-color: var(--ea-forest) !important;
    }

    .dropdown-menu {
      border: 1px solid rgba(26, 71, 42, 0.12);
      padding: 0.55rem;
      background: rgba(255,255,255,0.98);
    }

    .dropdown-item {
      border-radius: 10px;
      padding: 0.65rem 0.8rem;
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
      background: #f6f1e7;
      color: var(--ea-forest-deep);
    }

    .table > :not(caption) > * > * {
      padding: 1rem;
      border-bottom-color: rgba(26, 71, 42, 0.08);
    }

    .table-light,
    .table-light > th,
    .table-light > td,
    .table > thead {
      --bs-table-bg: #f6f1e7;
      --bs-table-striped-bg: #f6f1e7;
      color: var(--ea-text);
    }

    .alert-info {
      background: rgba(201, 168, 76, 0.12);
      color: #5f4b11;
      border: 1px solid rgba(201, 168, 76, 0.22);
    }

    .alert-secondary {
      background: rgba(26, 71, 42, 0.06);
      color: var(--ea-text);
      border: 1px solid rgba(26, 71, 42, 0.10);
    }

    .alert-warning {
      background: rgba(255, 193, 7, 0.11);
      border-color: rgba(255, 193, 7, 0.22);
    }

    .alert-danger {
      background: rgba(220, 53, 69, 0.09);
      border-color: rgba(220, 53, 69, 0.15);
    }

    .ea-user-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(255,255,255,0.14);
      color: #fff;
      border-radius: 999px;
      padding: 0.45rem 0.8rem;
      border: 1px solid rgba(255,255,255,0.14);
    }

    .ea-user-badge i {
      color: var(--ea-gold);
    }

    .ea-page-head {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .ea-page-kicker {
      margin-bottom: 0.4rem;
      color: var(--ea-gold);
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.22em;
      text-transform: uppercase;
    }

    .ea-page-title {
      margin-bottom: 0.25rem;
      font-size: clamp(2rem, 3vw, 3rem);
      line-height: 0.95;
    }

    .ea-page-subtitle {
      margin-bottom: 0;
      color: var(--ea-muted);
      max-width: 52rem;
    }

    .ea-page-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      align-items: center;
    }

    .ea-panel {
      background: var(--ea-white);
      border: 1px solid var(--ea-border);
      border-radius: 24px;
      box-shadow: var(--ea-shadow);
      padding: 1.5rem;
    }

    .ea-panel + .ea-panel {
      margin-top: 1.5rem;
    }

    .ea-panel-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 0.9rem;
      margin-bottom: 1.25rem;
    }

    .ea-panel-title {
      margin-bottom: 0.2rem;
      font-size: 1.8rem;
    }

    .ea-panel-subtitle {
      margin-bottom: 0;
      color: var(--ea-muted);
      font-size: 0.96rem;
    }

    .ea-stat-card {
      height: 100%;
      background: var(--ea-white);
      border: 1px solid var(--ea-border);
      border-radius: 22px;
      box-shadow: var(--ea-shadow);
      padding: 1.35rem;
    }

    .ea-stat-icon {
      width: 52px;
      height: 52px;
      border-radius: 18px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(201, 168, 76, 0.18);
      color: var(--ea-forest);
      margin-bottom: 0.9rem;
      font-size: 1.2rem;
    }

    .ea-stat-label {
      color: var(--ea-muted);
      text-transform: uppercase;
      letter-spacing: 0.14em;
      font-size: 0.74rem;
      font-weight: 600;
      margin-bottom: 0.35rem;
    }

    .ea-stat-value {
      font-size: clamp(1.8rem, 3vw, 2.4rem);
      font-weight: 600;
      line-height: 1;
    }

    .ea-quick-card {
      height: 100%;
      background: var(--ea-white);
      border: 1px solid var(--ea-border);
      border-radius: 22px;
      box-shadow: var(--ea-shadow);
      padding: 1.5rem;
    }

    .ea-quick-card h3 {
      margin-top: 1rem;
      margin-bottom: 0.5rem;
      font-size: 1.7rem;
    }

    .ea-quick-card p {
      color: var(--ea-muted);
      margin-bottom: 1rem;
    }

    .ea-table-wrap {
      background: var(--ea-white);
      border: 1px solid var(--ea-border);
      border-radius: 22px;
      box-shadow: var(--ea-shadow);
      overflow: hidden;
    }

    .ea-table {
      margin-bottom: 0;
    }

    .ea-table thead th {
      background: #f6f1e7;
      color: var(--ea-text);
      border-bottom: 1px solid rgba(26, 71, 42, 0.10);
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 600;
      white-space: nowrap;
    }

    .ea-table tbody tr:hover {
      background: rgba(26, 71, 42, 0.02);
    }

    .ea-table td {
      vertical-align: middle;
    }

    .ea-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      align-items: center;
    }

    .ea-thumb,
    .ea-thumb-placeholder {
      width: 64px;
      height: 64px;
      border-radius: 16px;
      object-fit: cover;
      border: 1px solid rgba(26, 71, 42, 0.08);
      background: linear-gradient(135deg, rgba(201,168,76,0.16), rgba(26,71,42,0.08));
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--ea-gold);
    }

    .ea-meta {
      color: var(--ea-muted);
      font-size: 0.92rem;
    }

    .ea-empty-state {
      background: rgba(201, 168, 76, 0.10);
      border: 1px solid rgba(201, 168, 76, 0.22);
      border-radius: 22px;
      box-shadow: var(--ea-shadow);
      padding: 2rem;
      text-align: center;
    }

    .ea-empty-state h3 {
      margin-top: 1rem;
      margin-bottom: 0.45rem;
      font-size: 1.8rem;
    }

    .ea-empty-state p {
      margin-bottom: 0;
      color: #6f5a26;
    }

    .ea-note-card {
      background: #fcfbf8;
      border: 1px solid rgba(26,71,42,0.08);
      border-radius: 18px;
      padding: 1rem;
    }

    .ea-form-card {
      background: var(--ea-white);
      border: 1px solid var(--ea-border);
      border-radius: 24px;
      box-shadow: var(--ea-shadow);
      overflow: hidden;
    }

    .ea-form-body {
      padding: 1.5rem;
    }

    .ea-form-section + .ea-form-section {
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(26, 71, 42, 0.08);
    }

    .ea-form-title {
      margin-bottom: 0.25rem;
      font-size: 1.6rem;
    }

    .ea-form-help {
      color: var(--ea-muted);
      font-size: 0.95rem;
      margin-bottom: 1rem;
    }

    .ea-form-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      align-items: center;
      margin-top: 1.5rem;
    }

    .ea-shell-grid {
      display: grid;
      grid-template-columns: 280px minmax(0, 1fr);
      gap: 1.5rem;
      align-items: start;
    }

    .ea-sidebar {
      position: sticky;
      top: 104px;
      background: linear-gradient(180deg, #12311d, #1a472a 60%, #245c38);
      color: #faf7f2;
      border-radius: 28px;
      padding: 1.5rem;
      box-shadow: 0 22px 45px rgba(18, 49, 29, 0.20);
    }

    .ea-sidebar a {
      color: rgba(250,247,242,0.9);
      text-decoration: none;
    }

    .ea-sidebar-link {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      padding: 0.95rem 1rem;
      border-radius: 16px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.08);
      transition: all 0.2s ease;
    }

    .ea-sidebar-link:hover {
      background: rgba(255,255,255,0.10);
      transform: translateX(3px);
    }

    .ea-sidebar-link.is-disabled {
      opacity: 0.55;
      cursor: not-allowed;
    }

    .ea-sidebar-link.is-disabled:hover {
      transform: none;
      background: rgba(255,255,255,0.06);
    }

    .ea-dashboard-banner {
      background:
        radial-gradient(circle at top left, rgba(201,168,76,0.18), transparent 24%),
        linear-gradient(135deg, #fdfaf3, #fff);
      border: 1px solid rgba(26, 71, 42, 0.08);
      border-radius: 24px;
      box-shadow: var(--ea-shadow);
      padding: 2rem;
      margin-bottom: 1.5rem;
    }

    .ea-auth-shell {
      min-height: 72vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .ea-auth-card {
      width: 100%;
      max-width: 460px;
      background: #fff;
      border: 1px solid rgba(26, 71, 42, 0.08);
      border-radius: 28px;
      box-shadow: var(--ea-shadow);
    }

    .ea-auth-logo {
      width: 72px;
      height: 72px;
      border-radius: 22px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(201,168,76,0.2), rgba(26,71,42,0.12));
      color: var(--ea-gold);
      font-size: 2rem;
    }

    .ea-auth-submit:hover,
    .ea-auth-submit:focus {
      background: var(--ea-gold) !important;
      border-color: var(--ea-gold) !important;
      color: var(--ea-forest) !important;
    }

    @media (max-width: 991.98px) {
      .ea-navbar .navbar-collapse {
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 18px;
        background: rgba(255,255,255,0.06);
      }

      .ea-navbar .nav-link::after {
        left: 0.9rem;
        right: auto;
        width: 40px;
      }

      .ea-shell-grid {
        grid-template-columns: 1fr;
      }

      .ea-sidebar {
        position: static;
      }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg ea-navbar navbar-dark">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-3" href="<?= e($brandHref) ?>">
      <span class="ea-brand-mark"><i class="bi bi-flower1"></i></span>
      <span><?= e($brandLabel) ?></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
            aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <?php if (!$u): ?>
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
        <?php elseif ($currentRole === 'user'): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/user/index.php">
              <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/user/concerns_list.php">
              <i class="bi bi-clipboard2-heart me-1"></i>My Concerns
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/user/solutions.php">
              <i class="bi bi-chat-left-text me-1"></i>Solutions
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/user/addresses.php">
              <i class="bi bi-geo-alt me-1"></i>Addresses
            </a>
          </li>
          <?php if ($userHasOrdersPage): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/user/orders.php">
                <i class="bi bi-receipt me-1"></i>Orders
              </a>
            </li>
          <?php endif; ?>
          <?php if ($userHasProfilePage): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/user/profile.php">
                <i class="bi bi-person me-1"></i>Profile
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/public/shop.php">
              <i class="bi bi-bag-heart me-1"></i>Shop
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/public/cart.php">
              <i class="bi bi-cart3 me-1"></i>Cart
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/admin/index.php">
              <i class="bi bi-speedometer2 me-1"></i>Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/admin/concerns_list.php">
              <i class="bi bi-clipboard2-heart me-1"></i>Concerns
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/admin/categories_list.php">
              <i class="bi bi-grid-3x3-gap me-1"></i>Categories
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/admin/products_list.php">
              <i class="bi bi-bag-check me-1"></i>Products
            </a>
          </li>
          <?php if ($adminHasOrdersPage): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/admin/orders_list.php">
                <i class="bi bi-receipt me-1"></i>Orders
              </a>
            </li>
          <?php endif; ?>
          <?php if ($adminHasPaymentsPage): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/admin/payments_list.php">
                <i class="bi bi-credit-card me-1"></i>Payments
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

        <?php if (!$u): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/public/login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
          </li>

          <li class="nav-item">
            <a class="btn ea-btn-gold btn-sm fw-semibold" href="<?= BASE_URL ?>/public/register.php">
              <i class="bi bi-person-plus me-1"></i>Register
            </a>
          </li>

        <?php else: ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
               href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="ea-user-badge">
                <i class="bi bi-person-circle"></i><?= e($u['full_name'] ?? 'User') ?>
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (($u['role'] ?? '') === 'user'): ?>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/user/index.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/user/concerns_list.php">
                    <i class="bi bi-clipboard2-heart me-2"></i>My Concerns
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/user/solutions.php">
                    <i class="bi bi-chat-left-text me-2"></i>Solutions
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/user/addresses.php">
                    <i class="bi bi-geo-alt me-2"></i>My Addresses
                  </a>
                </li>
                <?php if ($userHasOrdersPage): ?>
                  <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/user/orders.php">
                      <i class="bi bi-receipt me-2"></i>My Orders
                    </a>
                  </li>
                <?php endif; ?>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/public/cart.php">
                    <i class="bi bi-cart3 me-2"></i>Cart
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
              <?php elseif (($u['role'] ?? '') === 'admin'): ?>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/admin/concerns_list.php">
                    <i class="bi bi-clipboard2-heart me-2"></i>Concerns
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/admin/categories_list.php">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Categories
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>/admin/products_list.php">
                    <i class="bi bi-bag-check me-2"></i>Products
                  </a>
                </li>
                <?php if ($adminHasOrdersPage): ?>
                  <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin/orders_list.php">
                      <i class="bi bi-receipt me-2"></i>Orders
                    </a>
                  </li>
                <?php endif; ?>
                <?php if ($adminHasPaymentsPage): ?>
                  <li>
                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin/payments_list.php">
                      <i class="bi bi-credit-card me-2"></i>Payments
                    </a>
                  </li>
                <?php endif; ?>
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
