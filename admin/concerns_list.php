<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('admin');

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

$stmt = db()->query("
  SELECT c.*, u.full_name, u.email, u.phone
  FROM patient_concerns c
  JOIN users u ON u.id = c.user_id
  ORDER BY c.id DESC
");
$rows = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 fw-bold mb-0">Consultation Queue</h1>
    <div class="text-muted small">Patients submit symptoms. Admin reviews and provides disease + solution.</div>
  </div>
  <div class="text-muted small">Total: <?= count($rows) ?></div>
</div>

<div class="alert alert-secondary">
  <b>Note:</b> Severity may be auto-generated from symptoms when Algorithm Mode is enabled.
</div>

<?php if (!$rows): ?>
  <div class="alert alert-info">No concerns yet.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:90px;">ID</th>
          <th style="min-width:220px;">Patient</th>
          <th>Symptoms</th>
          <th style="width:120px;">Severity</th>
          <th style="width:160px;">Status</th>
          <th style="width:180px;">Created</th>
          <th style="width:160px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td>
              <div class="fw-semibold"><?= e($r['full_name'] ?? '—') ?></div>
              <div class="text-muted small"><?= e($r['email'] ?? '') ?><?= !empty($r['phone']) ? ' • ' . e($r['phone']) : '' ?></div>
            </td>
            <td>
              <?php
                $sym = trim((string)($r['symptoms'] ?? ''));
                $symPreview = mb_strimwidth($sym, 0, 120, '...');
              ?>
              <div class="fw-semibold"><?= e($symPreview ?: '—') ?></div>
              <?php if (!empty($r['mental_condition']) || !empty($r['digestive_issues']) || !empty($r['old_treatment_history'])): ?>
                <div class="text-muted small">+ extra details provided</div>
              <?php endif; ?>
            </td>
            <td><?= sev_badge($r['severity'] ?? 'mild') ?></td>
            <td><?= status_badge($r['status'] ?? 'pending') ?></td>
            <td><?= e($r['created_at'] ?? '') ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary"
                 href="<?= BASE_URL ?>/admin/concern_view.php?id=<?= (int)$r['id'] ?>">
                View / Solution
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
