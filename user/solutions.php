<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');

$user = current_user();

$stmt = db()->prepare("
  SELECT
    pc.*,
    s.solution_title,
    s.solution_details,
    s.recommended_products,
    s.created_at AS solution_created_at
  FROM patient_concerns pc
  LEFT JOIN solutions s
    ON s.id = (
      SELECT ss.id
      FROM solutions ss
      WHERE ss.concern_id = pc.id
      ORDER BY ss.id DESC
      LIMIT 1
    )
  WHERE pc.user_id = ?
  ORDER BY pc.id DESC
");
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

function sev_badge(string $sev): string {
  $sev = strtolower($sev);
  if ($sev === 'severe') return '<span class="badge text-bg-danger">Severe</span>';
  if ($sev === 'moderate') return '<span class="badge text-bg-warning">Moderate</span>';
  return '<span class="badge text-bg-success">Mild</span>';
}
function status_badge(string $st): string {
  $st = strtolower($st);
  if ($st === 'solution_provided') return '<span class="badge text-bg-success">Solution Provided</span>';
  if ($st === 'reviewed') return '<span class="badge text-bg-primary">Reviewed</span>';
  return '<span class="badge text-bg-secondary">Pending</span>';
}
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">User Panel</div>
    <h1 class="ea-page-title">My Solutions</h1>
    <p class="ea-page-subtitle">Review the latest admin diagnosis, treatment details, and recommended products for each concern.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-success" href="<?= BASE_URL ?>/user/concerns_add.php"><i class="bi bi-plus-circle me-1"></i>New Concern</a>
  </div>
</section>

<div class="alert alert-secondary">
  Here you can view the status of your submitted concerns and the solutions provided by the clinic/admin.
</div>

<?php if (!$rows): ?>
  <div class="ea-empty-state">
    <span class="ea-icon-pill"><i class="bi bi-chat-left-text"></i></span>
    <h3>No concerns submitted</h3>
    <p>You have not submitted any concerns yet. Add one to begin receiving solutions.</p>
  </div>
<?php else: ?>
  <div class="d-flex flex-column gap-4">
    <?php foreach ($rows as $r): ?>
      <div class="ea-panel">
        <div class="ea-panel-header">
          <div>
            <h2 class="ea-panel-title">Concern #<?= (int)$r['id'] ?> — <?= e($r['disease_name'] ?? 'Health Concern') ?></h2>
            <p class="ea-panel-subtitle">Submitted: <?= e($r['created_at'] ?? '') ?></p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?= sev_badge($r['severity'] ?? 'mild') ?>
            <?= status_badge($r['status'] ?? 'pending') ?>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-12">
            <div class="ea-note-card">
              <div class="fw-semibold mb-2">Symptoms</div>
              <div class="ea-meta"><?= nl2br(e($r['symptoms'] ?? '')) ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="ea-note-card h-100">
              <div class="fw-semibold mb-2">Mental Condition</div>
              <div class="ea-meta"><?= nl2br(e($r['mental_condition'] ?? '—')) ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="ea-note-card h-100">
              <div class="fw-semibold mb-2">Digestive Issues</div>
              <div class="ea-meta"><?= nl2br(e($r['digestive_issues'] ?? '—')) ?></div>
            </div>
          </div>
          <div class="col-12">
            <div class="ea-note-card">
              <div class="fw-semibold mb-2">Old Treatment History</div>
              <div class="ea-meta"><?= nl2br(e($r['old_treatment_history'] ?? '—')) ?></div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <?php if (empty($r['solution_title'])): ?>
            <div class="alert alert-warning mb-0">
              <b>No solution yet.</b> Your concern is still in the queue. Please check again later.
            </div>
          <?php else: ?>
            <div class="alert alert-success mb-0">
              <div class="fw-bold mb-1">Solution: <?= e($r['solution_title']) ?></div>
              <div class="ea-meta mb-2">Provided on: <?= e($r['solution_created_at'] ?? '') ?></div>
              <div><?= nl2br(e($r['solution_details'] ?? '')) ?></div>

              <?php if (!empty($r['recommended_products'])): ?>
                <hr>
                <div class="fw-semibold">Recommended Products / Medicines</div>
                <div><?= nl2br(e($r['recommended_products'])) ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
