<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

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

    if ($email === '' || $password === '') {
        $errors[] = "Email and password are required.";
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = "Invalid email or password.";
        } elseif (($user['status'] ?? 'active') !== 'active') {
            $errors[] = "Your account is blocked/inactive.";
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
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-6 col-lg-5">

    <div class="card shadow-sm border-0">
      <div class="card-body p-4">

        <div class="text-center mb-3">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white"
               style="width:56px;height:56px;">
            <i class="bi bi-shield-lock-fill fs-3"></i>
          </div>
          <h1 class="h4 fw-bold mt-3 mb-1">Login</h1>
          <p class="text-muted mb-0">Choose login type then enter credentials.</p>
        </div>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- ✅ Role toggle -->
        <div class="d-flex justify-content-center mb-3">
          <div class="btn-group" role="group" aria-label="Login as">
            <input type="radio" class="btn-check" name="login_as" id="loginUser" value="user"
                   form="loginForm" <?= $login_as === 'user' ? 'checked' : '' ?>>
            <label class="btn btn-outline-success" for="loginUser">
              <i class="bi bi-person"></i> Login as User
            </label>

            <input type="radio" class="btn-check" name="login_as" id="loginAdmin" value="admin"
                   form="loginForm" <?= $login_as === 'admin' ? 'checked' : '' ?>>
            <label class="btn btn-outline-success" for="loginAdmin">
              <i class="bi bi-shield-lock"></i> Login as Admin
            </label>
          </div>
        </div>

        <form method="post" id="loginForm">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-key"></i></span>
              <input type="password" name="password" id="loginPassword" class="form-control" required>
              <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('loginPassword', this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <button class="btn btn-success w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i> Login
          </button>

          <div class="text-center mt-3">
            <span class="text-muted">New user?</span>
            <a href="<?= BASE_URL ?>/public/register.php" class="fw-semibold text-success text-decoration-none">
              Create account
            </a>
          </div>
        </form>

      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
