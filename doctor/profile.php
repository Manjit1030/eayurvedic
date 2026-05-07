<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';

require_login();
require_role('doctor');

$u = current_user();
$profile = get_doctor_profile((int)$u['id']);
require_once __DIR__ . '/../app/includes/header.php';
?>
<section class="ea-page-head">
  <div><div class="ea-page-kicker">Doctor Panel</div><h1 class="ea-page-title">Profile</h1><p class="ea-page-subtitle">Account and professional verification details.</p></div>
  <div class="ea-page-actions"><a class="btn btn-outline-success" href="<?= BASE_URL ?>/doctor/register_profile.php">Complete/Update Registration</a></div>
</section>
<div class="ea-panel">
  <h2 class="ea-panel-title"><?= e($u['full_name'] ?? 'Doctor') ?></h2>
  <p class="ea-meta"><?= e($u['email'] ?? '') ?></p>
  <?php if (!$profile): ?>
    <div class="alert alert-warning mb-0">Professional registration has not been submitted.</div>
  <?php else: ?>
    <div class="row g-3 mt-2">
      <div class="col-md-6"><div class="ea-note-card"><strong>Status</strong><div><?= e(ucfirst($profile['verification_status'])) ?></div></div></div>
      <div class="col-md-6"><div class="ea-note-card"><strong>Full Name</strong><div><?= e($profile['full_name']) ?></div></div></div>
      <div class="col-md-6"><div class="ea-note-card"><strong>Gender</strong><div><?= e($profile['gender']) ?></div></div></div>
      <div class="col-md-6"><div class="ea-note-card"><strong>Date of Birth</strong><div><?= e($profile['date_of_birth']) ?></div></div></div>
      <div class="col-md-6"><div class="ea-note-card"><strong>Experience</strong><div><?= (int)$profile['years_of_experience'] ?> years</div></div></div>
      <div class="col-md-6"><div class="ea-note-card"><strong>Qualification</strong><div><?= e($profile['qualification']) ?></div></div></div>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
