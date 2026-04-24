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
  .home-hero {
    position: relative;
    overflow: hidden;
    padding: 4rem 3rem;
    border-radius: 32px;
    background:
      radial-gradient(circle at top left, rgba(201, 168, 76, 0.22), transparent 28%),
      radial-gradient(circle at right top, rgba(255,255,255,0.12), transparent 30%),
      linear-gradient(135deg, #12311d 0%, #1a472a 55%, #245c38 100%);
    color: #faf7f2;
    box-shadow: 0 22px 45px rgba(18, 49, 29, 0.22);
  }

  .home-hero::before,
  .home-hero::after {
    content: "";
    position: absolute;
    pointer-events: none;
    opacity: 0.22;
    background-repeat: no-repeat;
    background-size: contain;
  }

  .home-hero::before {
    width: 240px;
    height: 240px;
    right: -10px;
    top: 10px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Cg fill='none' stroke='%23f4deb0' stroke-width='2.8' stroke-linecap='round'%3E%3Cpath d='M101 25c-10 22-12 45-4 69 11-6 20-15 27-27 8-14 10-28 7-42-11 2-22 3-30 0Z'/%3E%3Cpath d='M99 96c-14-16-31-26-52-31-2 18 4 34 17 46 11 11 24 17 40 19 2-11 0-22-5-34Z'/%3E%3Cpath d='M109 100c14-16 31-26 52-31 2 18-4 34-17 46-11 11-24 17-40 19-2-11 0-22 5-34Z'/%3E%3Cpath d='M101 95v77'/%3E%3C/g%3E%3C/svg%3E");
  }

  .home-hero::after {
    width: 180px;
    height: 180px;
    left: -10px;
    bottom: -8px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 160 160'%3E%3Cg fill='none' stroke='%23d6b45c' stroke-width='2.4' stroke-linecap='round'%3E%3Cpath d='M80 22c-8 17-9 36-3 55 9-5 17-12 22-22 7-10 8-23 5-34-8 2-17 2-24 1Z'/%3E%3Cpath d='M78 75c-12-13-26-22-44-26-2 15 3 27 13 37 9 9 20 14 34 16 2-9 1-18-3-27Z'/%3E%3Cpath d='M82 74c11-12 25-20 44-25 2 15-3 28-13 38-9 9-20 14-35 16-1-9 0-18 4-29Z'/%3E%3Cpath d='M80 75v55'/%3E%3C/g%3E%3C/svg%3E");
  }

  .hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.65rem 1rem;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.14);
    background: rgba(255,255,255,0.08);
    color: rgba(250,247,242,0.88);
    font-size: 0.92rem;
  }

  .hero-chip i {
    color: var(--ea-gold);
  }

  .hero-highlight-card {
    background: rgba(255,255,255,0.10);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 24px;
    backdrop-filter: blur(10px);
  }

  .hero-highlight-item + .hero-highlight-item {
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.1);
  }

  .stats-strip {
    margin-top: -2rem;
    position: relative;
    z-index: 2;
  }

  .stats-card {
    background: #fff;
    border-radius: 22px;
    padding: 1.4rem;
    box-shadow: var(--ea-shadow);
    border: 1px solid rgba(26, 71, 42, 0.08);
    height: 100%;
  }

  .stats-icon {
    width: 52px;
    height: 52px;
    border-radius: 999px;
    background: rgba(201, 168, 76, 0.18);
    color: var(--ea-forest);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
  }

  .feature-card,
  .category-card,
  .algorithm-card,
  .medicine-card,
  .step-card {
    height: 100%;
    background: #fff;
    border: 1px solid rgba(26, 71, 42, 0.08);
    border-radius: 22px;
    box-shadow: var(--ea-shadow);
  }

  .feature-card,
  .category-card,
  .medicine-card {
    transition: transform 0.22s ease, box-shadow 0.22s ease;
  }

  .feature-card:hover,
  .category-card:hover,
  .medicine-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 40px rgba(18, 49, 29, 0.10);
  }

  .step-badge {
    width: 54px;
    height: 54px;
    border-radius: 999px;
    background: var(--ea-forest);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: "Cormorant Garamond", serif;
    font-size: 1.5rem;
    box-shadow: 0 10px 24px rgba(26, 71, 42, 0.18);
  }

  .algorithm-card {
    border-left: 5px solid var(--ea-gold);
  }

  .category-card {
    position: relative;
    overflow: hidden;
  }

  .category-card::after {
    content: "";
    position: absolute;
    inset: auto -40px -40px auto;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(201,168,76,0.18), transparent 65%);
  }

  .medicine-card .card-img-top,
  .medicine-image-placeholder {
    height: 220px;
    object-fit: cover;
    background: linear-gradient(135deg, rgba(201,168,76,0.16), rgba(26,71,42,0.08));
  }

  .medicine-price {
    color: var(--ea-gold);
    font-size: 1.15rem;
    font-weight: 700;
  }

  .final-cta {
    background: linear-gradient(135deg, #e4c772, #c9a84c);
    color: var(--ea-forest);
    border-radius: 28px;
    box-shadow: 0 18px 36px rgba(201, 168, 76, 0.20);
  }

  .section-title {
    font-size: clamp(2rem, 3vw, 2.8rem);
  }

  @media (max-width: 991.98px) {
    .home-hero {
      padding: 2.5rem 1.5rem;
      border-radius: 24px;
    }

    .stats-strip {
      margin-top: 1.25rem;
    }
  }
</style>

<section class="home-hero mb-4">
  <div class="row align-items-center g-4">
    <div class="col-lg-7">
      <div class="d-flex flex-wrap gap-2 mb-4">
        <span class="hero-chip"><i class="bi bi-flower1"></i>Ayurvedic consultation + medicine store</span>
        <span class="hero-chip"><i class="bi bi-shield-check"></i>Trusted wellness journey</span>
      </div>

      <h1 class="ea-section-heading mb-3">A premium digital wellness experience for Ayurvedic care.</h1>
      <p class="lead mb-4" style="color:rgba(250,247,242,0.84);max-width:42rem;">
        Discover a clean digital Ayurvedic storefront focused on trusted herbal products, a guided wellness journey, and a calm shopping experience built around simplicity.
      </p>

      <div class="d-flex flex-wrap gap-3">
        <a class="btn ea-btn-gold btn-lg" href="<?= BASE_URL ?>/public/shop.php">Explore Medicines</a>

        <?php if (!$u): ?>
          <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/public/register.php">Start Your Account</a>
          <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/public/login.php">Login</a>
        <?php else: ?>
          <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/public/shop.php">Continue Browsing</a>
          <?php if (($u['role'] ?? '') === 'user'): ?>
            <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/public/cart.php">View Cart</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="hero-highlight-card p-4 p-lg-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="h2 mb-0" style="color:#fff;">Project Highlights</h2>
          <span class="badge rounded-pill" style="background:rgba(201,168,76,0.22);color:#fff;">Premium UI</span>
        </div>

        <div class="hero-highlight-item d-flex gap-3">
          <span class="ea-icon-pill"><i class="bi bi-clipboard2-pulse"></i></span>
          <div>
            <div class="fw-semibold fs-5 text-white">Guided Wellness Journey</div>
            <div style="color:rgba(250,247,242,0.78);">Learn about products, browse categories, and move from discovery to checkout through a calm, easy-to-follow storefront.</div>
          </div>
        </div>

        <div class="hero-highlight-item d-flex gap-3">
          <span class="ea-icon-pill"><i class="bi bi-bag-heart"></i></span>
          <div>
            <div class="fw-semibold fs-5 text-white">Store + Cart + Checkout</div>
            <div style="color:rgba(250,247,242,0.78);">Modern product browsing, clean order summaries, and a checkout experience that feels production-ready.</div>
          </div>
        </div>

        <div class="hero-highlight-item d-flex gap-3">
          <span class="ea-icon-pill"><i class="bi bi-diagram-3"></i></span>
          <div>
            <div class="fw-semibold fs-5 text-white">Built For Trust</div>
            <div style="color:rgba(250,247,242,0.78);">Clear product details, transparent pricing, and a simple path to account access help guests feel confident before they sign in.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="stats-strip mb-5">
  <div class="row g-3">
    <div class="col-6 col-lg-3">
      <div class="stats-card">
        <div class="stats-icon"><i class="bi bi-capsule-pill"></i></div>
        <div class="small text-uppercase ea-subtle mb-2">Products</div>
        <div class="display-6 fw-semibold"><?= (int)$stats['products'] ?></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stats-card">
        <div class="stats-icon"><i class="bi bi-grid-3x3-gap"></i></div>
        <div class="small text-uppercase ea-subtle mb-2">Categories</div>
        <div class="display-6 fw-semibold"><?= (int)$stats['categories'] ?></div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stats-card">
        <div class="stats-icon"><i class="bi bi-people"></i></div>
        <div class="small text-uppercase ea-subtle mb-2">Wellness Focus</div>
        <div class="display-6 fw-semibold">100%</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stats-card">
        <div class="stats-icon"><i class="bi bi-heart-pulse"></i></div>
        <div class="small text-uppercase ea-subtle mb-2">Natural Care</div>
        <div class="display-6 fw-semibold">24/7</div>
      </div>
    </div>
  </div>
</section>

<section class="mb-5">
  <div class="text-center mb-4">
    <p class="text-uppercase small fw-semibold" style="letter-spacing:.24em;color:var(--ea-gold);">Why Choose Us</p>
    <h2 class="section-title mb-2">A cleaner and more welcoming public storefront</h2>
    <p class="ea-subtle mb-0">The guest experience stays focused on discovery, trust, and an easy path into the store.</p>
  </div>

  <div class="row g-4">
    <div class="col-md-6 col-xl-3">
      <div class="feature-card p-4">
        <span class="ea-icon-pill mb-3"><i class="bi bi-flower1"></i></span>
        <h3 class="mb-2">Ayurvedic Identity</h3>
        <p class="ea-subtle mb-0">A calmer herbal look and feel makes the storefront more approachable and memorable for guests.</p>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="feature-card p-4">
        <span class="ea-icon-pill mb-3"><i class="bi bi-bag-heart"></i></span>
        <h3 class="mb-2">Easy Browsing</h3>
        <p class="ea-subtle mb-0">Guests can move quickly from homepage to categories, products, and product details without clutter.</p>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="feature-card p-4">
        <span class="ea-icon-pill mb-3"><i class="bi bi-shield-check"></i></span>
        <h3 class="mb-2">Clear Trust Signals</h3>
        <p class="ea-subtle mb-0">Readable pricing, stock information, and direct calls to action help visitors understand the store immediately.</p>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="feature-card p-4">
        <span class="ea-icon-pill mb-3"><i class="bi bi-person-plus"></i></span>
        <h3 class="mb-2">Simple Account Entry</h3>
        <p class="ea-subtle mb-0">Guests can create an account or sign in only when they are ready to continue into protected features.</p>
      </div>
    </div>
  </div>
</section>

<section class="mb-5">
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card border-0 p-4 p-lg-5 step-card">
        <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;color:var(--ea-gold);">Store Journey</p>
        <h2 class="section-title mb-4">How guests explore the store</h2>

        <div class="d-flex gap-3 mb-4">
          <span class="step-badge">1</span>
          <div>
            <h3 class="mb-1">Browse categories</h3>
            <p class="ea-subtle mb-0">Start with curated wellness categories and explore herbal products that match your interests.</p>
          </div>
        </div>

        <div class="d-flex gap-3 mb-4">
          <span class="step-badge">2</span>
          <div>
            <h3 class="mb-1">Review product details</h3>
            <p class="ea-subtle mb-0">Check descriptions, stock, and pricing on a dedicated product page before deciding to continue.</p>
          </div>
        </div>

        <div class="d-flex gap-3 mb-4">
          <span class="step-badge">3</span>
          <div>
            <h3 class="mb-1">Create an account or login</h3>
            <p class="ea-subtle mb-0">When a guest is ready to move forward, account access keeps the shopping flow secure and organized.</p>
          </div>
        </div>

        <div class="d-flex gap-3">
          <span class="step-badge">4</span>
          <div>
            <h3 class="mb-1">Continue to secure checkout</h3>
            <p class="ea-subtle mb-0">Logged-in users can manage cart, addresses, and orders from a more private dashboard experience.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card border-0 p-4 p-lg-5">
        <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;color:var(--ea-gold);">Brand Promise</p>
        <h2 class="section-title mb-4">What makes the storefront feel trustworthy</h2>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="algorithm-card p-4">
              <h3 class="mb-2">Clear Product Details</h3>
              <p class="ea-subtle mb-0"></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="algorithm-card p-4">
              <h3 class="mb-2">Protected User Features</h3>
              <p class="ea-subtle mb-0">Private concerns, addresses, and order history stay inside the logged-in user area only.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="algorithm-card p-4">
              <h3 class="mb-2">Focused Navigation</h3>
              <p class="ea-subtle mb-0">Guests only see browsing and account entry points, keeping the experience professional and uncluttered.</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="algorithm-card p-4">
              <h3 class="mb-2">Simple Next Steps</h3>
              <p class="ea-subtle mb-0">When private features require access, the interface points guests cleanly toward login or registration.</p>
            </div>
          </div>
        </div>

        <div class="mt-4 p-3 rounded-4" style="background:#f6f1e7;">
          <div class="small ea-subtle">The public experience stays focused on products, categories, and safe account entry instead of internal workflows.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="mb-5">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
      <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;color:var(--ea-gold);">Featured Categories</p>
      <h2 class="section-title mb-1">Browse your wellness collection</h2>
      <p class="ea-subtle mb-0"></p>
    </div>
    <a class="btn btn-outline-success" href="<?= BASE_URL ?>/public/shop.php">View Shop</a>
  </div>

  <?php if (!$cats): ?>
    <div class="alert alert-info"></div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($cats as $c): ?>
        <div class="col-md-6 col-xl-4">
          <div class="category-card p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <span class="ea-icon-pill"><i class="bi bi-leaf"></i></span>
              <span class="badge rounded-pill" style="background:rgba(201,168,76,0.18);color:var(--ea-forest);">Active</span>
            </div>
            <h3 class="mb-2"><?= e($c['name']) ?></h3>
            <p class="ea-subtle mb-0"><?= e(mb_strimwidth(strip_tags($c['description'] ?? ''), 0, 120, '...')) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="mb-5">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
      <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;color:var(--ea-gold);">Latest Medicines</p>
      <h2 class="section-title mb-1">Fresh additions to the store</h2>
      <p class="ea-subtle mb-0">Product cards now feel more premium.</p>
    </div>
    <a class="btn btn-success" href="<?= BASE_URL ?>/public/shop.php">Shop Now</a>
  </div>

  <?php if (!$products): ?>
    <div class="alert alert-info"></div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($products as $p): ?>
        <div class="col-sm-6 col-xl-4">
          <div class="medicine-card card border-0">
            <?php if (!empty($p['main_image'])): ?>
              <img src="<?= BASE_URL ?>/public/<?= e($p['main_image']) ?>" class="card-img-top" alt="Product">
            <?php else: ?>
              <div class="medicine-image-placeholder d-flex align-items-center justify-content-center">
                <i class="bi bi-capsule-pill" style="font-size:2.5rem;color:var(--ea-gold);"></i>
              </div>
            <?php endif; ?>

            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                <h3 class="mb-0"><?= e($p['name']) ?></h3>
                <span class="badge rounded-pill" style="background:rgba(201,168,76,0.18);color:var(--ea-forest);"><?= e($p['category_name']) ?></span>
              </div>
              <div class="ea-subtle mb-3">Stock: <?= (int)$p['stock'] ?></div>
              <div class="medicine-price mb-4">NPR <?= e($p['price']) ?></div>

              <div class="mt-auto d-grid gap-2">
                <a class="btn btn-outline-success" href="<?= BASE_URL ?>/public/product.php?id=<?= (int)$p['id'] ?>">View Details</a>
                <?php if (!$u): ?>
                  <a class="btn btn-success" href="<?= BASE_URL ?>/public/login.php">Login to Purchase</a>
                <?php elseif (($u['role'] ?? '') === 'user'): ?>
                  <a class="btn btn-success" href="<?= BASE_URL ?>/public/shop.php">Add via Shop</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="final-cta p-4 p-lg-5 mb-4">
  <div class="row align-items-center g-3">
    <div class="col-lg-8">
      <p class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.24em;">Ready To Present</p>
      <h2 class="mb-2" style="font-size:clamp(2rem,3vw,3rem);">Begin with the store, then continue into a secure account when needed.</h2>
      <p class="mb-0" style="max-width:42rem;color:rgba(26,71,42,0.82);">The public experience stays focused on product discovery and a polished first impression, while private features remain safely behind login.</p>
    </div>
    <div class="col-lg-4 text-lg-end">
      <?php if (!$u): ?>
        <a class="btn btn-dark btn-lg px-4" style="background:var(--ea-forest);border-color:var(--ea-forest);" href="<?= BASE_URL ?>/public/register.php">Create Account</a>
      <?php else: ?>
        <a class="btn btn-dark btn-lg px-4" style="background:var(--ea-forest);border-color:var(--ea-forest);" href="<?= BASE_URL ?>/public/shop.php">Start Shopping</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
