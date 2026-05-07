<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

csrf_init();

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if (is_post()) {
    csrf_verify();

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $old = ['full_name' => $full_name, 'email' => $email, 'phone' => $phone];

    if ($full_name === '') $errors[] = 'Name is required.';
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($phone === '') $errors[] = 'Phone number is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if ($confirm === '' || $password !== $confirm) $errors[] = 'Confirm password must match password.';

    if (!$errors) {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $errors[] = 'Email is already registered.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare("
            INSERT INTO users (full_name, email, phone, password_hash, role, status)
            VALUES (?, ?, ?, ?, 'doctor', 'active')
        ");
        $stmt->execute([$full_name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
        redirect('/public/login.php?doctor_registered=1');
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-auth-shell">
  <div class="ea-auth-card p-4 p-lg-5">
    <div class="text-center mb-4">
      <div class="ea-auth-logo mx-auto mb-3"><i class="bi bi-heart-pulse"></i></div>
      <h1 class="mb-2" style="font-size:clamp(2.2rem,4vw,3rem);">Doctor Signup</h1>
      <p class="ea-subtle mb-0">Create a doctor account, then submit your professional registration for admin verification.</p>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold">Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Phone Number</label>
        <input type="text" name="phone" class="form-control" value="<?= e($old['phone']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button class="btn btn-success ea-auth-submit w-100 btn-lg">Create Doctor Account</button>
      <div class="mt-4 text-center">
        <span class="ea-subtle">Already registered?</span>
        <a href="<?= BASE_URL ?>/public/login.php" class="fw-semibold text-decoration-none">Login</a>
      </div>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
