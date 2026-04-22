<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

csrf_init();

$errors = [];
$old = ['full_name'=>'', 'email'=>'', 'phone'=>''];
$nepalPhonePattern = '^98[0-9]{8}$';

if (is_post()) {
    csrf_verify();

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    $old = ['full_name'=>$full_name, 'email'=>$email, 'phone'=>$phone];

    if ($full_name === '') {
        $errors[] = "Full name is required.";
    } elseif (mb_strlen($full_name) < 2) {
        $errors[] = "Full name must be at least 2 characters.";
    }

    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if ($phone === '') {
        $errors[] = "Phone number is required.";
    } elseif (!is_valid_nepal_phone($phone)) {
        $errors[] = "Enter a valid Nepal phone number starting with 98.";
    }

    if (trim($password) === '' || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (trim($confirm) === '') {
        $errors[] = "Confirm password is required.";
    } elseif ($password !== $confirm) {
        $errors[] = "Confirm password does not match.";
    }

    if (!$errors) {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $emailExists = $stmt->fetchColumn();

        $stmt = db()->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $phoneExists = $stmt->fetchColumn();

        if ($emailExists) {
            $errors[] = "Email is already registered.";
        }

        if ($phoneExists) {
            $errors[] = "Phone number is already registered.";
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = db()->prepare("
                INSERT INTO users (full_name, email, phone, password_hash, role, status)
                VALUES (?, ?, ?, ?, 'user', 'active')
            ");
            $stmt->execute([$full_name, $email, $phone, $hash]);

            redirect('/public/login.php?registered=1');
        }
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-auth-shell">
  <div class="ea-auth-card p-4 p-lg-5">
    <div class="text-center mb-4">
      <div class="ea-auth-logo mx-auto mb-3"><i class="bi bi-flower1"></i></div>
      <h1 class="mb-2" style="font-size:clamp(2.3rem,4vw,3.2rem);">Create Account</h1>
      <p class="ea-subtle mb-0">Join eAyurvedic to explore consultation and medicine ordering.</p>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name']) ?>" minlength="2" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($old['email']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Phone Number</label>
        <input type="text" name="phone" class="form-control" value="<?= e($old['phone']) ?>" pattern="<?= e($nepalPhonePattern) ?>" inputmode="numeric" maxlength="10" placeholder="98XXXXXXXX" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <div class="input-group">
          <input type="password" name="password" id="regPassword" class="form-control" minlength="6" required>
          <button class="btn btn-outline-secondary" type="button" onclick="togglePwd(['regPassword', 'regConfirmPassword'], this)"><i class="bi bi-eye"></i></button>
        </div>
        <div class="form-text">Minimum 6 characters</div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">Confirm Password</label>
        <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control" required>
      </div>

      <button class="btn btn-success ea-auth-submit w-100 btn-lg">Create Account</button>

      <div class="mt-4 text-center">
        <span class="ea-subtle">Already have an account?</span>
        <a href="<?= BASE_URL ?>/public/login.php" class="fw-semibold text-decoration-none">Login</a>
      </div>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
