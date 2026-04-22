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

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">User Panel</div>
    <h1 class="ea-page-title">Concern #<?= (int)$c['id'] ?></h1>
    <p class="ea-page-subtitle">Submitted on <?= e($c['created_at'] ?? '') ?>. Review your concern details and current status below.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/user/concerns_list.php">Back</a>
    <a class="btn btn-outline-success" href="<?= BASE_URL ?>/user/concern_edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
  </div>
</section>

<div class="ea-panel">
  <div class="ea-panel-header">
    <div>
      <h2 class="ea-panel-title">Concern Summary</h2>
      <p class="ea-panel-subtitle">Disease name is updated by the admin after review.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?= sev_badge($c['severity'] ?? 'mild') ?>
      <span class="badge text-bg-secondary"><?= e($c['status'] ?? 'pending') ?></span>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="ea-note-card h-100">
        <div class="fw-semibold mb-2">Disease (Set by Admin)</div>
        <div class="ea-meta"><?= e($c['disease_name'] ?: 'Not identified yet') ?></div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="ea-note-card h-100">
        <div class="fw-semibold mb-2">Symptoms</div>
        <div class="ea-meta"><?= nl2br(e($c['symptoms'] ?? '')) ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="ea-note-card h-100">
        <div class="fw-semibold mb-2">Mental Condition</div>
        <div class="ea-meta"><?= nl2br(e($c['mental_condition'] ?? '—')) ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="ea-note-card h-100">
        <div class="fw-semibold mb-2">Digestive Issues</div>
        <div class="ea-meta"><?= nl2br(e($c['digestive_issues'] ?? '—')) ?></div>
      </div>
    </div>
    <div class="col-12">
      <div class="ea-note-card">
        <div class="fw-semibold mb-2">Old Treatment History</div>
        <div class="ea-meta"><?= nl2br(e($c['old_treatment_history'] ?? '—')) ?></div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
