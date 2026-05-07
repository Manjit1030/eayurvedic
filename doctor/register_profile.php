<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('doctor');
csrf_init();

$u = current_user();
$profile = get_doctor_profile((int)$u['id']);
$errors = [];
$old = [
    'full_name' => $profile['full_name'] ?? ($u['full_name'] ?? ''),
    'gender' => $profile['gender'] ?? '',
    'date_of_birth' => $profile['date_of_birth'] ?? '',
    'years_of_experience' => $profile['years_of_experience'] ?? '',
    'qualification' => $profile['qualification'] ?? '',
];

function doctor_upload_file(string $field, array &$errors, ?string $existing = null): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return $existing;
    }
    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload failed for ' . str_replace('_', ' ', $field) . '.';
        return $existing;
    }
    if ((int)$_FILES[$field]['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Each document must be 5MB or smaller.';
        return $existing;
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        $errors[] = 'Allowed document types are pdf, jpg, jpeg, and png.';
        return $existing;
    }
    $dir = __DIR__ . '/../public/uploads/doctors';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = $field . '_' . bin2hex(random_bytes(16)) . '.' . $ext;
    $target = $dir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        $errors[] = 'Could not save uploaded document.';
        return $existing;
    }
    return 'uploads/doctors/' . $name;
}

if (is_post()) {
    csrf_verify();
    if ($profile && in_array($profile['verification_status'] ?? '', ['pending', 'verified'], true)) {
        redirect('/doctor/register_profile.php');
    }
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['gender'] = trim($_POST['gender'] ?? '');
    $old['date_of_birth'] = trim($_POST['date_of_birth'] ?? '');
    $old['years_of_experience'] = trim($_POST['years_of_experience'] ?? '');
    $old['qualification'] = trim($_POST['qualification'] ?? '');

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if ($old['gender'] === '') $errors[] = 'Gender is required.';
    if ($old['date_of_birth'] === '') $errors[] = 'Date of birth is required.';
    if ($old['years_of_experience'] === '' || (int)$old['years_of_experience'] < 0) $errors[] = 'Years of experience is required.';
    if ($old['qualification'] === '') $errors[] = 'Qualification is required.';

    $degree = doctor_upload_file('ayurveda_degree_certificate', $errors, $profile['ayurveda_degree_certificate'] ?? null);
    $license = doctor_upload_file('clinic_license', $errors, $profile['clinic_license'] ?? null);
    if (!$degree) $errors[] = 'Ayurveda degree certificate is required.';
    if (!$license) $errors[] = 'Clinic license is required.';

    if (!$errors) {
        if ($profile) {
            $stmt = db()->prepare("
                UPDATE doctor_profiles
                SET full_name=?, gender=?, date_of_birth=?, years_of_experience=?, qualification=?,
                    ayurveda_degree_certificate=?, clinic_license=?, verification_status='pending',
                    rejection_reason=NULL, verified_by=NULL, verified_at=NULL, updated_at=NOW()
                WHERE user_id=?
            ");
            $stmt->execute([$old['full_name'], $old['gender'], $old['date_of_birth'], (int)$old['years_of_experience'], $old['qualification'], $degree, $license, (int)$u['id']]);
        } else {
            $stmt = db()->prepare("
                INSERT INTO doctor_profiles
                (user_id, full_name, gender, date_of_birth, years_of_experience, qualification, ayurveda_degree_certificate, clinic_license)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([(int)$u['id'], $old['full_name'], $old['gender'], $old['date_of_birth'], (int)$old['years_of_experience'], $old['qualification'], $degree, $license]);
        }
        redirect('/doctor/dashboard.php');
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>
<section class="ea-page-head"><div><div class="ea-page-kicker">Doctor Panel</div><h1 class="ea-page-title">Professional Registration</h1><p class="ea-page-subtitle">Submit credentials for admin verification.</p></div></section>
<?php if ($profile && ($profile['verification_status'] ?? '') === 'pending' && !is_post()): ?>
  <div class="alert alert-info">Your doctor verification request is pending admin approval.</div>
<?php elseif ($profile && ($profile['verification_status'] ?? '') === 'verified' && !is_post()): ?>
  <div class="alert alert-success">Your doctor profile is verified.</div>
<?php endif; ?>
<?php if ($profile && ($profile['verification_status'] ?? '') === 'rejected'): ?>
  <div class="alert alert-danger"><div class="fw-semibold">Rejected</div><?= nl2br(e($profile['rejection_reason'] ?? 'No reason provided.')) ?></div>
<?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<?php if (!$profile || ($profile['verification_status'] ?? '') === 'rejected' || is_post()): ?>
<form method="post" enctype="multipart/form-data" class="ea-form-card">
  <div class="ea-form-body">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Full Name</label><input class="form-control" name="full_name" value="<?= e((string)$old['full_name']) ?>" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Gender</label><select class="form-select" name="gender" required><option value="">Select</option><?php foreach (['Male','Female','Other'] as $gender): ?><option value="<?= e($gender) ?>" <?= $old['gender'] === $gender ? 'selected' : '' ?>><?= e($gender) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="<?= e((string)$old['date_of_birth']) ?>" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Years of Experience</label><input type="number" min="0" class="form-control" name="years_of_experience" value="<?= e((string)$old['years_of_experience']) ?>" required></div>
      <div class="col-12"><label class="form-label fw-semibold">Qualification</label><input class="form-control" name="qualification" value="<?= e((string)$old['qualification']) ?>" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Ayurveda Degree Certificate</label><input type="file" class="form-control" name="ayurveda_degree_certificate" accept=".pdf,.jpg,.jpeg,.png" <?= empty($profile['ayurveda_degree_certificate']) ? 'required' : '' ?>></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Clinic License</label><input type="file" class="form-control" name="clinic_license" accept=".pdf,.jpg,.jpeg,.png" <?= empty($profile['clinic_license']) ? 'required' : '' ?>></div>
    </div>
    <div class="ea-form-actions"><button class="btn btn-success">Submit for Verification</button><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/doctor/dashboard.php">Cancel</a></div>
  </div>
</form>
<?php elseif ($profile): ?>
  <div class="ea-panel">
    <h2 class="ea-panel-title"><?= e($profile['full_name']) ?></h2>
    <p class="ea-meta mb-0"><?= e($profile['qualification']) ?>, <?= (int)$profile['years_of_experience'] ?> years experience</p>
  </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
