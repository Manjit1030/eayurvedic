<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

auth_init();
csrf_init();

$errors = [];
$email = '';
$login_as = 'user'; // default

if (is_post()) {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $login_as = ($_POST['login_as'] ?? 'user') === 'admin' ? 'admin' : 'user';

    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if (trim($password) === '') {
        $errors[] = "Password is required.";
    }

    if (!$errors) {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = "Invalid email or password.";
        } elseif (($user['status'] ?? 'active') !== 'active') {
            $errors[] = "Your account is blocked or removed.";
        } elseif (!password_verify($password, $user['password_hash'])) {
            $errors[] = "Invalid email or password.";
        } else {
            // ✅ Enforce selected role
            $role = $user['role'] ?? 'user';

            if ($login_as === 'admin' && $role !== 'admin') {
                $errors[] = "This account is not an admin account.";
            } elseif ($login_as === 'user' && $role !== 'user') {
                $errors[] = "Please select 'Login as Admin' for this account.";
            } else {
                // Login success
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $role,
                    'status' => $user['status'] ?? 'active',
                ];

                // redirect based on chosen role
                if ($login_as === 'admin') {
                    redirect('/admin/index.php');
                } else {
                    redirect('/user/index.php');
                }
            }
        }
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-auth-shell">
  <div class="ea-auth-card p-4 p-lg-5">
    <div class="text-center mb-4">
      <div class="ea-auth-logo mx-auto mb-3"><i class="bi bi-flower1"></i></div>
      <h1 class="mb-2" style="font-size:clamp(2.3rem,4vw,3.2rem);">Welcome Back</h1>
      <p class="ea-subtle mb-0">Login to continue your Ayurvedic wellness journey.</p>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-center mb-4">
      <div class="btn-group" role="group" aria-label="Login as">
        <input type="radio" class="btn-check" name="login_as" id="loginUser" value="user"
               form="loginForm" <?= $login_as === 'user' ? 'checked' : '' ?>>
        <label class="btn btn-outline-success" for="loginUser">
          <i class="bi bi-person me-1"></i>User
        </label>

        <input type="radio" class="btn-check" name="login_as" id="loginAdmin" value="admin"
               form="loginForm" <?= $login_as === 'admin' ? 'checked' : '' ?>>
        <label class="btn btn-outline-success" for="loginAdmin">
          <i class="bi bi-shield-lock me-1"></i>Admin
        </label>
      </div>
    </div>

    <form method="post" id="loginForm">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-key"></i></span>
          <input type="password" name="password" id="loginPassword" class="form-control" required>
          <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('loginPassword', this)"><i class="bi bi-eye"></i></button>
        </div>
      </div>

      <button class="btn btn-success ea-auth-submit w-100 btn-lg">
        <i class="bi bi-box-arrow-in-right me-1"></i>Login
      </button>

      <div class="text-center mt-4">
        <span class="ea-subtle">New user?</span>
        <a href="<?= BASE_URL ?>/public/register.php" class="fw-semibold text-decoration-none">Create account</a>
      </div>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
