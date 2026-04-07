<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/algorithms.php';
require_once __DIR__ . '/../app/includes/header.php';

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

  // Algorithm #1: Severity classification
  if (!$errors) {
    if (function_exists('algo_severity_score')) {
      $severity = algo_severity_score($symptoms, $mental, $digestive);
    } else {
      $severity = 'mild';
    }

    // disease_name must be NULL because user doesn't know disease
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
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 fw-bold mb-0">Add Health Concern</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>/user/concerns_list.php">← Back</a>
</div>

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

<form method="post" class="card shadow-sm">
  <div class="card-body">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label class="form-label">Symptoms *</label>
      <textarea name="symptoms" class="form-control" rows="4" required
        placeholder="Write symptoms separated by commas (e.g., headache, fever, weakness)"><?= e($symptoms) ?></textarea>
      <div class="form-text">Tip: use commas to separate symptoms to help severity algorithm.</div>
    </div>

    <div class="mb-3">
      <label class="form-label">Mental Condition (optional)</label>
      <textarea name="mental_condition" class="form-control" rows="2"
        placeholder="e.g., stress, anxiety, low sleep"><?= e($mental) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Digestive Issues (optional)</label>
      <textarea name="digestive_issues" class="form-control" rows="2"
        placeholder="e.g., constipation, acidity"><?= e($digestive) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Old Treatment History (optional)</label>
      <textarea name="old_treatment_history" class="form-control" rows="2"
        placeholder="e.g., took paracetamol for 2 days"><?= e($old_treatment) ?></textarea>
    </div>

    <button class="btn btn-success">Submit Concern</button>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
