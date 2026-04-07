<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');

$u = current_user();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid concern id.");

$stmt = db()->prepare("SELECT * FROM patient_concerns WHERE id=? AND user_id=? LIMIT 1");
$stmt->execute([$id, $u['id']]);
$c = $stmt->fetch();
if (!$c) die("Access denied or concern not found.");

function sev_badge(string $sev): string {
  $sev = strtolower($sev);
  if ($sev === 'severe') return '<span class="badge text-bg-danger">Severe</span>';
  if ($sev === 'moderate') return '<span class="badge text-bg-warning">Moderate</span>';
  return '<span class="badge text-bg-success">Mild</span>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-0">Concern #<?= (int)$c['id'] ?></h1>
    <div class="text-muted small">Submitted: <?= e($c['created_at'] ?? '') ?></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>/user/concerns_list.php">← Back</a>
    <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/user/concern_edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex justify-content-between flex-wrap gap-2">
      <div>
        <div class="fw-semibold">Disease (Set by Admin)</div>
        <div><?= e($c['disease_name'] ?: 'Not identified yet') ?></div>
      </div>
      <div class="text-end">
        <?= sev_badge($c['severity'] ?? 'mild') ?>
        <span class="badge text-bg-secondary"><?= e($c['status'] ?? 'pending') ?></span>
      </div>
    </div>

    <hr>

    <div class="mb-3">
      <div class="fw-semibold">Symptoms</div>
      <div class="text-muted"><?= nl2br(e($c['symptoms'] ?? '')) ?></div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="fw-semibold">Mental Condition</div>
        <div class="text-muted"><?= nl2br(e($c['mental_condition'] ?? '—')) ?></div>
      </div>
      <div class="col-md-6">
        <div class="fw-semibold">Digestive Issues</div>
        <div class="text-muted"><?= nl2br(e($c['digestive_issues'] ?? '—')) ?></div>
      </div>
    </div>

    <div class="mt-3">
      <div class="fw-semibold">Old Treatment History</div>
      <div class="text-muted"><?= nl2br(e($c['old_treatment_history'] ?? '—')) ?></div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
