<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('admin');
csrf_init();

$admin = current_user();
$success = $_SESSION['doctors_success_message'] ?? null;
$error = $_SESSION['doctors_error_message'] ?? null;
unset($_SESSION['doctors_success_message'], $_SESSION['doctors_error_message']);

function doctor_verification_badge(?string $status): string {
    $status = $status ?: 'not_submitted';
    if ($status === 'verified') return '<span class="badge text-bg-success">Verified</span>';
    if ($status === 'rejected') return '<span class="badge text-bg-danger">Rejected</span>';
    if ($status === 'pending') return '<span class="badge text-bg-warning">Pending</span>';
    return '<span class="badge text-bg-secondary">Not Submitted</span>';
}

if (is_post()) {
    csrf_verify();
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    $stmt = db()->prepare("
        SELECT u.id, u.full_name, u.role, dp.id AS profile_id
        FROM users u
        LEFT JOIN doctor_profiles dp ON dp.user_id = u.id
        WHERE u.id = ? AND u.role = 'doctor'
        LIMIT 1
    ");
    $stmt->execute([$doctorId]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        $_SESSION['doctors_error_message'] = 'Doctor account not found.';
        redirect('/admin/doctors.php');
    }

    if ($action === 'verify') {
        if (empty($doctor['profile_id'])) {
            $_SESSION['doctors_error_message'] = 'This doctor has not submitted a profile yet.';
        } else {
            $stmt = db()->prepare("
                UPDATE doctor_profiles
                SET verification_status='verified', verified_by=?, verified_at=NOW(), rejection_reason=NULL, updated_at=NOW()
                WHERE user_id=?
            ");
            $stmt->execute([(int)$admin['id'], $doctorId]);
            $_SESSION['doctors_success_message'] = 'Doctor verified successfully.';
        }
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        if (empty($doctor['profile_id'])) {
            $_SESSION['doctors_error_message'] = 'This doctor has not submitted a profile yet.';
        } elseif ($reason === '') {
            $_SESSION['doctors_error_message'] = 'Rejection reason is required.';
        } else {
            $stmt = db()->prepare("
                UPDATE doctor_profiles
                SET verification_status='rejected', rejection_reason=?, verified_by=NULL, verified_at=NULL, updated_at=NOW()
                WHERE user_id=?
            ");
            $stmt->execute([$reason, $doctorId]);
            $_SESSION['doctors_success_message'] = 'Doctor rejected successfully.';
        }
    } elseif ($action === 'remove') {
        $stmt = db()->prepare("DELETE FROM users WHERE id=? AND role='doctor'");
        $stmt->execute([$doctorId]);
        $_SESSION['doctors_success_message'] = 'Doctor account removed successfully.';
    } else {
        $_SESSION['doctors_error_message'] = 'Invalid doctor action.';
    }

    redirect('/admin/doctors.php');
}

$rows = db()->query("
    SELECT
        u.id, u.full_name AS account_name, u.email, u.phone, u.created_at AS account_created_at,
        dp.full_name, dp.gender, dp.date_of_birth, dp.years_of_experience, dp.qualification,
        dp.ayurveda_degree_certificate, dp.clinic_license, dp.verification_status,
        dp.rejection_reason, dp.created_at AS profile_created_at
    FROM users u
    LEFT JOIN doctor_profiles dp ON dp.user_id = u.id
    WHERE u.role = 'doctor'
    ORDER BY u.id DESC
")->fetchAll();

require_once __DIR__ . '/../app/includes/header.php';
?>
<section class="ea-page-head">
  <div><div class="ea-page-kicker">eAyurvedic Admin</div><h1 class="ea-page-title">Manage Doctors</h1><p class="ea-page-subtitle">Verify, reject, or remove doctor accounts and professional registrations.</p></div>
</section>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if (!$rows): ?>
  <div class="ea-empty-state"><span class="ea-icon-pill"><i class="bi bi-heart-pulse"></i></span><h3>No doctors yet</h3><p>Doctor signup accounts will appear here.</p></div>
<?php else: ?>
  <div class="ea-table-wrap"><div class="table-responsive shadow-none"><table class="table ea-table align-middle mb-0">
    <thead><tr><th>Account</th><th>Profile</th><th>Documents</th><th>Status</th><th>Created</th><th style="min-width:280px;">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td>
          <div class="fw-semibold"><?= e($row['account_name'] ?? '-') ?></div>
          <div class="ea-meta"><?= e($row['email'] ?? '-') ?></div>
          <div class="ea-meta"><?= e($row['phone'] ?? '-') ?></div>
        </td>
        <td>
          <?php if (empty($row['profile_created_at'])): ?>
            <span class="ea-meta">Not submitted</span>
          <?php else: ?>
            <div class="fw-semibold"><?= e($row['full_name'] ?? '-') ?></div>
            <div class="ea-meta"><?= e($row['gender'] ?? '-') ?> | DOB: <?= e($row['date_of_birth'] ?? '-') ?></div>
            <div class="ea-meta"><?= (int)$row['years_of_experience'] ?> years | <?= e($row['qualification'] ?? '-') ?></div>
            <?php if (!empty($row['rejection_reason'])): ?><div class="text-danger small mt-1"><?= e($row['rejection_reason']) ?></div><?php endif; ?>
          <?php endif; ?>
        </td>
        <td>
          <?php if (!empty($row['ayurveda_degree_certificate'])): ?><a class="btn btn-sm btn-outline-success mb-1" target="_blank" href="<?= BASE_URL ?>/public/<?= e($row['ayurveda_degree_certificate']) ?>">Degree</a><?php endif; ?>
          <?php if (!empty($row['clinic_license'])): ?><a class="btn btn-sm btn-outline-success mb-1" target="_blank" href="<?= BASE_URL ?>/public/<?= e($row['clinic_license']) ?>">License</a><?php endif; ?>
          <?php if (empty($row['ayurveda_degree_certificate']) && empty($row['clinic_license'])): ?><span class="ea-meta">No documents</span><?php endif; ?>
        </td>
        <td><?= doctor_verification_badge($row['verification_status'] ?? null) ?></td>
        <td class="ea-meta">
          Account: <?= e($row['account_created_at'] ?? '-') ?><br>
          Profile: <?= e($row['profile_created_at'] ?? '-') ?>
        </td>
        <td>
          <div class="ea-actions">
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="doctor_id" value="<?= (int)$row['id'] ?>">
              <input type="hidden" name="action" value="verify">
              <button class="btn btn-sm btn-outline-success" <?= empty($row['profile_created_at']) ? 'disabled' : '' ?>>Verify</button>
            </form>
            <form method="post" onsubmit="return this.rejection_reason.value.trim() !== '' || (alert('Enter rejection reason.'), false);">
              <?= csrf_field() ?>
              <input type="hidden" name="doctor_id" value="<?= (int)$row['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <input class="form-control form-control-sm mb-1" name="rejection_reason" placeholder="Rejection reason" <?= empty($row['profile_created_at']) ? 'disabled' : '' ?>>
              <button class="btn btn-sm btn-outline-danger" <?= empty($row['profile_created_at']) ? 'disabled' : '' ?>>Reject</button>
            </form>
            <form method="post" onsubmit="return confirm('Remove this doctor account? Existing concerns remain and doctor_id on solutions will be cleared by the database.');">
              <?= csrf_field() ?>
              <input type="hidden" name="doctor_id" value="<?= (int)$row['id'] ?>">
              <input type="hidden" name="action" value="remove">
              <button class="btn btn-sm btn-danger">Remove</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
