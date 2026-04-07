<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');

$user = current_user();

/*
  Fetch user concerns + latest solution (if any)
  We use LEFT JOIN with a subquery to get latest solution per concern.
*/
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

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 fw-bold mb-0">My Solutions</h1>
  <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/user/concerns_add.php">
    + New Concern
  </a>
</div>

<div class="alert alert-secondary">
  Here you can view the status of your submitted concerns and the solutions provided by the clinic/admin.
</div>

<?php if (!$rows): ?>
  <div class="alert alert-info">
    You have not submitted any concerns yet.
    <a href="<?= BASE_URL ?>/user/concerns_add.php">Submit your first concern</a>.
  </div>
<?php else: ?>

  <?php foreach ($rows as $r): ?>
    <div class="card shadow-sm mb-3">
      <div class="card-body">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <div class="fw-bold">
              Concern #<?= (int)$r['id'] ?> — <?= e($r['disease_name'] ?? 'Health Concern') ?>
            </div>
            <div class="text-muted small">
              Submitted: <?= e($r['created_at'] ?? '') ?>
            </div>
          </div>

          <div class="text-end">
            <?= sev_badge($r['severity'] ?? 'mild') ?>
            <?= status_badge($r['status'] ?? 'pending') ?>
          </div>
        </div>

        <hr class="my-3">

        <div class="mb-2">
          <div class="fw-semibold">Symptoms</div>
          <div class="text-muted"><?= nl2br(e($r['symptoms'] ?? '')) ?></div>
        </div>

        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <div class="fw-semibold">Mental Condition</div>
            <div class="text-muted"><?= nl2br(e($r['mental_condition'] ?? '—')) ?></div>
          </div>
          <div class="col-md-6">
            <div class="fw-semibold">Digestive Issues</div>
            <div class="text-muted"><?= nl2br(e($r['digestive_issues'] ?? '—')) ?></div>
          </div>
        </div>

        <div class="mt-3">
          <div class="fw-semibold">Old Treatment History</div>
          <div class="text-muted"><?= nl2br(e($r['old_treatment_history'] ?? '—')) ?></div>
        </div>

        <hr class="my-3">

        <?php if (empty($r['solution_title'])): ?>
          <div class="alert alert-warning mb-0">
            <b>No solution yet.</b> Your concern is in the queue. Please check again later.
          </div>
        <?php else: ?>
          <div class="alert alert-success">
            <div class="fw-bold mb-1">
              ✅ Solution: <?= e($r['solution_title']) ?>
            </div>
            <div class="text-muted small mb-2">
              Provided on: <?= e($r['solution_created_at'] ?? '') ?>
            </div>
            <div><?= nl2br(e($r['solution_details'] ?? '')) ?></div>

            <?php if (!empty($r['recommended_products'])): ?>
              <hr>
              <div class="fw-semibold">Recommended Products / Medicines</div>
              <div class="text-muted"><?= nl2br(e($r['recommended_products'])) ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
