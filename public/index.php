<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/includes/header.php';

auth_init();
$u = current_user();

/* ==============================
   Quick stats (safe)
============================== */
$stats = [
  'users' => 0,
  'products' => 0,
  'categories' => 0,
  'concerns' => 0
];

try {
  $pdo = db();

  $stats['users']      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
  $stats['products']   = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
  $stats['categories'] = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
  $stats['concerns']   = (int)$pdo->query("SELECT COUNT(*) FROM patient_concerns")->fetchColumn();
} catch (\PDOException $e) {
  // keep defaults
} catch (\Exception $e) {
  // keep defaults
} catch (\Error $e) {
  // keep defaults
}

/* ==============================
   Featured categories
============================== */
$cats = [];
try {
  $cats = db()->query("SELECT id, name, description FROM categories WHERE status='active' ORDER BY id DESC LIMIT 6")->fetchAll();
} catch (\Exception $e) {}

/* ==============================
   Latest products
============================== */
$products = [];
try {
  $products = db()->query("
    SELECT p.id, p.name, p.price, p.stock, p.main_image, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.status='active'
    ORDER BY p.id DESC
    LIMIT 6
  ")->fetchAll();
} catch (\Exception $e) {}
?>

<style>
  .hero-wrap{
    background: radial-gradient(1200px 600px at 15% 20%, rgba(25,135,84,.22), transparent 55%),
                radial-gradient(900px 500px at 85% 10%, rgba(13,110,253,.14), transparent 55%),
                linear-gradient(180deg, rgba(25,135,84,.06), rgba(255,255,255,0));
    border-radius: 1.25rem;
  }
  .badge-soft-success{ background: rgba(25,135,84,.12); color: #198754; }
  .icon-pill{
    width: 44px; height: 44px; border-radius: 999px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(25,135,84,.12);
  }
  .card-hover{ transition: transform .15s ease, box-shadow .15s ease; }
  .card-hover:hover{ transform: translateY(-2px); box-shadow: 0 .75rem 2rem rgba(0,0,0,.08); }
  .mini-muted{ color: rgba(0,0,0,.55); }
  .product-img{ height:180px; object-fit:cover; }
</style>

<!-- HERO -->
<section class="hero-wrap p-4 p-md-5 mb-4 shadow-sm">
  <div class="row align-items-center g-4">
    <div class="col-lg-7">
      <div class="d-inline-flex align-items-center gap-2 mb-3">
        <span class="badge rounded-pill badge-soft-success px-3 py-2">
          Ayurvedic Consultation + Medicine Store
        </span>
        <span class="badge rounded-pill text-bg-light px-3 py-2">
          Secure • Responsive • Role-Based
        </span>
      </div>

      <h1 class="display-6 fw-bold mb-2">
        eAyurvedic – Consultation + Online Medicine Store
      </h1>
      <p class="lead mini-muted mb-4">
        Submit your health concern, get admin-reviewed Ayurvedic guidance, and purchase medicines from the shop —
        all in one system.
      </p>

      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-success btn-lg" href="<?= BASE_URL ?>/public/shop.php">
          Explore Shop
        </a>

        <?php if (!$u): ?>
          <a class="btn btn-outline-success btn-lg" href="<?= BASE_URL ?>/public/register.php">Create Account</a>
          <a class="btn btn-outline-secondary btn-lg" href="<?= BASE_URL ?>/public/login.php">Login</a>
        <?php else: ?>
          <?php if (($u['role'] ?? '') === 'admin'): ?>
            <a class="btn btn-outline-secondary btn-lg" href="<?= BASE_URL ?>/admin/index.php">Go to Admin Panel</a>
          <?php else: ?>
            <a class="btn btn-outline-secondary btn-lg" href="<?= BASE_URL ?>/user/index.php">Go to Dashboard</a>
            <a class="btn btn-outline-success btn-lg" href="<?= BASE_URL ?>/public/cart.php">View Cart</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="mt-4 d-flex flex-wrap gap-3 small">
        <div class="d-flex align-items-center gap-2">
          <span class="badge text-bg-success">✓</span> CRUD + Role-based Admin/User
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge text-bg-success">✓</span> Algorithm toggle (WITH / WITHOUT)
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge text-bg-success">✓</span> Consultation flow + Shop flow
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Quick Highlights</h2>

          <div class="d-flex gap-3 mb-3">
            <div class="icon-pill">🩺</div>
            <div>
              <div class="fw-semibold">Concerns → Admin Solution</div>
              <div class="mini-muted small">User submits symptoms; admin reviews & replies.</div>
            </div>
          </div>

          <div class="d-flex gap-3 mb-3">
            <div class="icon-pill">🛍️</div>
            <div>
              <div class="fw-semibold">Shop + Cart + Orders</div>
              <div class="mini-muted small">Browse medicines, cart CRUD, checkout & place order.</div>
            </div>
          </div>

          <div class="d-flex gap-3">
            <div class="icon-pill">🔒</div>
            <div>
              <div class="fw-semibold">Security Basics</div>
              <div class="mini-muted small">Prepared statements, sessions, role checks, CSRF.</div>
            </div>
          </div>

          <hr class="my-4">

          <div class="row g-3 text-center">
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['products'] ?></div>
              <div class="mini-muted small">Products</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['categories'] ?></div>
              <div class="mini-muted small">Categories</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['users'] ?></div>
              <div class="mini-muted small">Users</div>
            </div>
            <div class="col-6">
              <div class="fw-bold fs-4"><?= (int)$stats['concerns'] ?></div>
              <div class="mini-muted small">Concerns</div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="mb-5">
  <div class="text-center mb-4">
    <h2 class="h4 fw-bold mb-2">What the system provides</h2>
    <p class="mini-muted mb-0">Clear modules for user flow + admin management.</p>
  </div>

  <div class="row g-3">
    <div class="col-md-6 col-lg-3">
      <div class="card card-hover h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="icon-pill mb-3">👤</div>
          <h3 class="h6 fw-bold">User Dashboard</h3>
          <p class="mini-muted small mb-0">Addresses, concerns, solutions, orders, cart & checkout.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-hover h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="icon-pill mb-3">🛠️</div>
          <h3 class="h6 fw-bold">Admin Panel</h3>
          <p class="mini-muted small mb-0">Categories, products, consultation queue & solutions.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-hover h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="icon-pill mb-3">🧠</div>
          <h3 class="h6 fw-bold">Algorithms</h3>
          <p class="mini-muted small mb-0">Cart totals, severity score, symptom→solution suggestions.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-hover h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="icon-pill mb-3">📦</div>
          <h3 class="h6 fw-bold">Orders</h3>
          <p class="mini-muted small mb-0">Place order from checkout, store order items & totals.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="mb-5">
  <div class="row g-3 align-items-stretch">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">How Consultation Works</h2>

          <div class="d-flex gap-3 mb-3">
            <div class="icon-pill">1</div>
            <div>
              <div class="fw-semibold">User submits symptoms</div>
              <div class="mini-muted small">Symptoms, mental condition, digestive issues, old treatment.</div>
            </div>
          </div>

          <div class="d-flex gap-3 mb-3">
            <div class="icon-pill">2</div>
            <div>
              <div class="fw-semibold">Severity is calculated (optional)</div>
              <div class="mini-muted small">Algorithm classifies mild / moderate / severe.</div>
            </div>
          </div>

          <div class="d-flex gap-3 mb-3">
            <div class="icon-pill">3</div>
            <div>
              <div class="fw-semibold">Admin reviews and identifies disease</div>
              <div class="mini-muted small">Admin sets disease title + solution details.</div>
            </div>
          </div>

          <div class="d-flex gap-3">
            <div class="icon-pill">4</div>
            <div>
              <div class="fw-semibold">User receives solution</div>
              <div class="mini-muted small">Solution status updates to “solution_provided”.</div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4">
          <h2 class="h5 fw-bold mb-3">Algorithms Implemented</h2>

          <div class="row g-3">
            <div class="col-md-6">
              <div class="p-3 rounded border">
                <div class="fw-semibold">Algorithm #1: Cart Totals</div>
                <div class="mini-muted small">Subtotal + shipping + tax → final payable.</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded border">
                <div class="fw-semibold">Algorithm #2: Severity Score</div>
                <div class="mini-muted small">Classifies patient condition from symptoms.</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded border">
                <div class="fw-semibold">Algorithm #3: Symptom → Suggestions</div>
                <div class="mini-muted small">Decision-support categories for admin.</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded border">
                <div class="fw-semibold">Algorithm Mode Toggle</div>
                <div class="mini-muted small">Show WITH vs WITHOUT algorithm outputs.</div>
              </div>
            </div>
          </div>

          <div class="mt-3 small mini-muted">
            Note: Suggestions are decision-support only; admin remains final decision-maker.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURED CATEGORIES -->
<section class="mb-5">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <div>
      <h2 class="h5 fw-bold mb-1">Featured Categories</h2>
      <div class="mini-muted small">Pulled live from your database.</div>
    </div>
    <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/public/shop.php">View Shop</a>
  </div>

  <?php if (!$cats): ?>
    <div class="alert alert-info">Add categories from Admin Panel to show here.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($cats as $c): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card card-hover border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="icon-pill">📁</div>
                <span class="badge text-bg-light">Active</span>
              </div>
              <h3 class="h6 fw-bold mb-1"><?= e($c['name']) ?></h3>
              <p class="mini-muted small mb-0">
                <?= e(mb_strimwidth(strip_tags($c['description'] ?? ''), 0, 120, '...')) ?>
              </p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- LATEST PRODUCTS -->
<section class="mb-5">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <div>
      <h2 class="h5 fw-bold mb-1">Latest Medicines</h2>
      <div class="mini-muted small">Pulled live from your products table.</div>
    </div>
    <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/public/shop.php">Shop Now</a>
  </div>

  <?php if (!$products): ?>
    <div class="alert alert-info">Add products from Admin Panel to show here.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($products as $p): ?>
        <div class="col-12 col-sm-6 col-lg-4">
          <div class="card card-hover border-0 shadow-sm h-100">
            <?php if (!empty($p['main_image'])): ?>
              <img src="<?= BASE_URL ?>/public/<?= e($p['main_image']) ?>" class="card-img-top product-img" alt="Product">
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start">
                <h3 class="h6 fw-bold mb-1"><?= e($p['name']) ?></h3>
                <span class="badge text-bg-success"><?= e($p['category_name']) ?></span>
              </div>
              <div class="mini-muted small mb-2">Stock: <?= (int)$p['stock'] ?></div>
              <div class="fw-bold mb-2">NPR <?= e($p['price']) ?></div>

              <div class="mt-auto d-grid gap-2">
                <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/public/product.php?id=<?= (int)$p['id'] ?>">
                  View Details
                </a>
                <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/public/shop.php">
                  Add via Shop
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- FINAL CTA -->
<section class="mb-4">
  <div class="p-4 p-md-5 bg-success text-white rounded-4 shadow-sm">
    <div class="row align-items-center g-3">
      <div class="col-lg-8">
        <h2 class="h3 fw-bold mb-1">Ready to explore eAyurvedic?</h2>
        <p class="mb-0 opacity-75">
          Start with Shop, or submit a health concern and receive a solution from admin.
        </p>
      </div>
      <div class="col-lg-4 text-lg-end">
        <a class="btn btn-light btn-lg" href="<?= BASE_URL ?>/public/shop.php">Start Shopping</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
