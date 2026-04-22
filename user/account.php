<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();

$sessionUser = current_user();
if (($sessionUser['role'] ?? '') !== 'user') {
    redirect('/admin/index.php');
}

auth_init();
csrf_init();

$userId = (int)($sessionUser['id'] ?? 0);
$profileErrors = [];
$passwordErrors = [];
$deleteErrors = [];
$profileSuccess = null;
$passwordSuccess = null;
$account = null;

$profileData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
];

try {
    $stmt = db()->prepare("SELECT id, full_name, email, phone, password_hash, role, status, created_at FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$userId]);
    $account = $stmt->fetch();
} catch (Exception $e) {
    $account = null;
}

if (!$account || ($account['role'] ?? '') !== 'user') {
    logout_user();
    redirect('/public/login.php');
}

$profileData['full_name'] = (string)($account['full_name'] ?? '');
$profileData['email'] = (string)($account['email'] ?? '');
$profileData['phone'] = (string)($account['phone'] ?? '');
$joinedAt = (string)($account['created_at'] ?? '');

if (is_post()) {
    csrf_verify();
    $formAction = (string)($_POST['account_action'] ?? '');

    if ($formAction === 'update_profile') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));

        $profileData['full_name'] = $fullName;
        $profileData['email'] = $email;
        $profileData['phone'] = $phone;

        if ($fullName === '') {
            $profileErrors[] = 'Full name is required.';
        }

        if ($email === '') {
            $profileErrors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = 'Enter a valid email address.';
        }

        if ($phone === '') {
            $profileErrors[] = 'Phone number is required.';
        } elseif (!is_valid_nepal_phone($phone)) {
            $profileErrors[] = 'Phone number must be exactly 10 digits and start with 98.';
        }

        if (!$profileErrors) {
            $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetchColumn()) {
                $profileErrors[] = 'Email is already registered.';
            }

            $stmt = db()->prepare("SELECT id FROM users WHERE phone = ? AND id <> ? LIMIT 1");
            $stmt->execute([$phone, $userId]);
            if ($stmt->fetchColumn()) {
                $profileErrors[] = 'Phone number is already registered.';
            }
        }

        if (!$profileErrors) {
            $stmt = db()->prepare("
                UPDATE users
                SET full_name = ?, email = ?, phone = ?
                WHERE id = ? AND role = 'user'
                LIMIT 1
            ");
            $stmt->execute([$fullName, $email, $phone, $userId]);

            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['email'] = $email;

            $profileSuccess = 'Your profile information has been updated.';
            $profileData['full_name'] = $fullName;
            $profileData['email'] = $email;
            $profileData['phone'] = $phone;
        }
    } elseif ($formAction === 'change_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (trim($currentPassword) === '') {
            $passwordErrors[] = 'Current password is required.';
        } elseif (!password_verify($currentPassword, (string)($account['password_hash'] ?? ''))) {
            $passwordErrors[] = 'Current password is incorrect.';
        }

        if (strlen($newPassword) < 6) {
            $passwordErrors[] = 'New password must be at least 6 characters.';
        }

        if ($confirmPassword !== $newPassword) {
            $passwordErrors[] = 'Confirm password does not match.';
        }

        if (!$passwordErrors) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = db()->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'user' LIMIT 1");
            $stmt->execute([$newHash, $userId]);
            $passwordSuccess = 'Your password has been changed successfully.';
        }
    } elseif ($formAction === 'delete_account') {
        $deletePassword = (string)($_POST['delete_password'] ?? '');
        $deleteConfirm = (string)($_POST['delete_confirm'] ?? '');

        if (trim($deletePassword) === '') {
            $deleteErrors[] = 'Current password is required.';
        } elseif (!password_verify($deletePassword, (string)($account['password_hash'] ?? ''))) {
            $deleteErrors[] = 'Current password is incorrect.';
        }

        if ($deleteConfirm !== '1') {
            $deleteErrors[] = 'Please confirm account deletion before continuing.';
        }

        if (($account['role'] ?? '') !== 'user') {
            $deleteErrors[] = 'Only user accounts can be deleted from this page.';
        }

        if (!$deleteErrors) {
            $stmt = db()->prepare("UPDATE users SET status = 'removed' WHERE id = ? AND role = 'user' LIMIT 1");
            $stmt->execute([$userId]);
            logout_user();
            redirect('/public/login.php?account_deleted=1');
        }
    }

    if ($formAction !== 'delete_account') {
        $stmt = db()->prepare("SELECT id, full_name, email, phone, password_hash, role, status, created_at FROM users WHERE id=? LIMIT 1");
        $stmt->execute([$userId]);
        $account = $stmt->fetch();

        if ($account) {
            $profileData['full_name'] = (string)($account['full_name'] ?? $profileData['full_name']);
            $profileData['email'] = (string)($account['email'] ?? $profileData['email']);
            $profileData['phone'] = (string)($account['phone'] ?? $profileData['phone']);
            $joinedAt = (string)($account['created_at'] ?? $joinedAt);
        }
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<div class="ea-dashboard-banner">
  <div class="ea-page-head mb-0">
    <div>
      <div class="ea-page-kicker">My Account</div>
      <h1 class="ea-page-title">Manage Your Account</h1>
      <p class="ea-page-subtitle">Manage your profile information, password, and account settings.</p>
    </div>
    <div class="ea-page-actions">
      <a class="btn btn-outline-success" href="<?= BASE_URL ?>/user/index.php">
        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
      </a>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="ea-panel h-100">
      <div class="d-flex align-items-center gap-3 mb-3">
        <span class="ea-icon-pill"><i class="bi bi-person-circle"></i></span>
        <div>
          <h2 class="ea-panel-title mb-1"><?= e($profileData['full_name'] ?: 'User') ?></h2>
          <p class="ea-panel-subtitle mb-0"><?= e($profileData['email']) ?></p>
        </div>
      </div>

      <div class="ea-note-card mb-3">
        <div class="fw-semibold mb-2">Account Summary</div>
        <div class="ea-meta mb-1"><i class="bi bi-telephone me-2"></i><?= e($profileData['phone']) ?></div>
        <div class="ea-meta mb-1"><i class="bi bi-shield-check me-2"></i>User account</div>
        <div class="ea-meta"><i class="bi bi-calendar-event me-2"></i>Joined <?= e($joinedAt) ?></div>
      </div>

      <div class="alert alert-secondary mb-0">
        Keep your email and Nepal mobile number up to date so orders and account access remain smooth.
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="ea-form-card">
      <div class="ea-form-body">
        <div class="ea-form-section pt-0 mt-0 border-0">
          <h2 class="ea-form-title">Profile Information</h2>
          <p class="ea-form-help">Update the personal details linked to your eAyurvedic account.</p>

          <?php if ($profileSuccess): ?>
            <div class="alert alert-success"><?= e($profileSuccess) ?></div>
          <?php endif; ?>

          <?php if ($profileErrors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($profileErrors as $error): ?>
                  <li><?= e($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="account_action" value="update_profile">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= e($profileData['full_name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($profileData['email']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= e($profileData['phone']) ?>" pattern="^98[0-9]{8}$" inputmode="numeric" maxlength="10" placeholder="98XXXXXXXX" required>
                <div class="form-text">Use a 10-digit Nepal mobile number starting with 98.</div>
              </div>
            </div>

            <div class="ea-form-actions">
              <button class="btn btn-success" type="submit">
                <i class="bi bi-check2-circle me-1"></i>Save Changes
              </button>
            </div>
          </form>
        </div>

        <div class="ea-form-section">
          <h2 class="ea-form-title">Change Password</h2>
          <p class="ea-form-help">Use your current password to set a new secure password.</p>

          <?php if ($passwordSuccess): ?>
            <div class="alert alert-success"><?= e($passwordSuccess) ?></div>
          <?php endif; ?>

          <?php if ($passwordErrors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($passwordErrors as $error): ?>
                  <li><?= e($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="account_action" value="change_password">

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-semibold">Current Password</label>
                <div class="input-group">
                  <input type="password" name="current_password" id="accountCurrentPassword" class="form-control" required>
                  <button class="btn btn-outline-secondary" type="button" onclick="togglePwd(['accountCurrentPassword', 'accountNewPassword', 'accountConfirmPassword'], this)"><i class="bi bi-eye"></i></button>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">New Password</label>
                <input type="password" name="new_password" id="accountNewPassword" class="form-control" minlength="6" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Confirm New Password</label>
                <input type="password" name="confirm_password" id="accountConfirmPassword" class="form-control" minlength="6" required>
              </div>
            </div>

            <div class="ea-form-actions">
              <button class="btn btn-success" type="submit">
                <i class="bi bi-key me-1"></i>Update Password
              </button>
            </div>
          </form>
        </div>

        <div class="ea-form-section">
          <h2 class="ea-form-title text-danger">Delete Account</h2>
          <p class="ea-form-help">This will mark your user account as removed, log you out, and prevent future logins with this account.</p>

          <?php if ($deleteErrors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($deleteErrors as $error): ?>
                  <li><?= e($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="alert alert-warning">
            Your order, concern, and address records are not deleted here. Your account is safely deactivated using the existing user `status` field.
          </div>

          <form method="post" onsubmit="return confirm('Are you sure you want to delete your account? This will log you out immediately.');">
            <?= csrf_field() ?>
            <input type="hidden" name="account_action" value="delete_account">

            <div class="row g-3">
              <div class="col-md-7">
                <label class="form-label fw-semibold">Current Password</label>
                <input type="password" name="delete_password" class="form-control" required>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="deleteConfirm" name="delete_confirm" required>
                  <label class="form-check-label" for="deleteConfirm">
                    I understand this will deactivate my account and I will not be able to log in again.
                  </label>
                </div>
              </div>
            </div>

            <div class="ea-form-actions">
              <button class="btn btn-danger" type="submit">
                <i class="bi bi-trash me-1"></i>Delete Account
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
