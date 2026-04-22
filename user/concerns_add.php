<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/algorithms.php';

require_login();
require_role('user');
csrf_init();

$u = current_user();

$errors = [];
$symptoms = '';
$mental = '';
$digestive = '';
$old_treatment = '';
$severity = 'mild';

$algoMode = (defined('ALGO_ENABLED') && ALGO_ENABLED) ? 'WITH_ALGORITHM' : 'WITHOUT_ALGORITHM';

if (is_post()) {
  csrf_verify();

  $symptoms = trim($_POST['symptoms'] ?? '');
  $mental = trim($_POST['mental_condition'] ?? '');
  $digestive = trim($_POST['digestive_issues'] ?? '');
  $old_treatment = trim($_POST['old_treatment_history'] ?? '');

  if ($symptoms === '') {
    $errors[] = "Symptoms are required.";
  }

  if (!$errors) {
    if (function_exists('algo_severity_score')) {
      $severity = algo_severity_score($symptoms, $mental, $digestive);
    } else {
      $severity = 'mild';
    }

    $stmt = db()->prepare("
      INSERT INTO patient_concerns
        (user_id, disease_name, symptoms, mental_condition, digestive_issues, old_treatment_history, severity, status)
      VALUES
        (?, NULL, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
      $u['id'],
      $symptoms,
      $mental ?: null,
      $digestive ?: null,
      $old_treatment ?: null,
      $severity
    ]);

    redirect('/user/concerns_list.php');
  }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">User Panel</div>
    <h1 class="ea-page-title">Add Health Concern</h1>
    <p class="ea-page-subtitle">Share symptoms and optional details so the admin can review your case properly.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/user/concerns_list.php">Back to Concerns</a>
  </div>
</section>

<div class="alert alert-secondary">
  <b>Algorithm Mode:</b> <?= e($algoMode) ?> (Severity will be auto-calculated when enabled)
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="ea-form-card">
  <div class="ea-form-body">
    <?= csrf_field() ?>

    <div class="ea-form-section">
      <h2 class="ea-form-title">Symptoms & Condition</h2>
      <p class="ea-form-help">Use clear language and separate symptoms with commas for better readability and algorithm support.</p>

      <div class="mb-3">
        <label class="form-label fw-semibold">Symptoms *</label>
        <textarea name="symptoms" class="form-control" rows="4" required placeholder="Write symptoms separated by commas (e.g., headache, fever, weakness)"><?= e($symptoms) ?></textarea>
        <div class="form-text">Tip: use commas to separate symptoms to help severity algorithm.</div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Mental Condition (optional)</label>
        <textarea name="mental_condition" class="form-control" rows="3" placeholder="e.g., stress, anxiety, low sleep"><?= e($mental) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Digestive Issues (optional)</label>
        <textarea name="digestive_issues" class="form-control" rows="3" placeholder="e.g., constipation, acidity"><?= e($digestive) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Old Treatment History (optional)</label>
        <textarea name="old_treatment_history" class="form-control" rows="3" placeholder="e.g., took paracetamol for 2 days"><?= e($old_treatment) ?></textarea>
      </div>
    </div>

    <div class="ea-form-actions">
      <button class="btn btn-success">Submit Concern</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/user/concerns_list.php">Cancel</a>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
