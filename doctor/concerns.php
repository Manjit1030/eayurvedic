<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';

require_verified_doctor();

$successMessage = $_SESSION['doctor_success_message'] ?? null;
unset($_SESSION['doctor_success_message']);

function doctor_sev_badge(string $sev): string {
  $sev = strtolower($sev);
  if ($sev === 'severe') return '<span class="badge text-bg-danger">Severe</span>';
  if ($sev === 'moderate') return '<span class="badge text-bg-warning">Moderate</span>';
  return '<span class="badge text-bg-success">Mild</span>';
}
function doctor_status_badge(string $st): string {
  $st = strtolower($st);
  if ($st === 'solution_provided') return '<span class="badge text-bg-success">Solution Provided</span>';
  if ($st === 'reviewed') return '<span class="badge text-bg-primary">Reviewed</span>';
  return '<span class="badge text-bg-secondary">Pending</span>';
}

$rows = db()->query("
  SELECT c.*, u.full_name, u.email, u.phone
  FROM patient_concerns c
  JOIN users u ON u.id = c.user_id
  ORDER BY c.id DESC
")->fetchAll();

require_once __DIR__ . '/../app/includes/header.php';
?>
<section class="ea-page-head">
  <div><div class="ea-page-kicker">Doctor Panel</div><h1 class="ea-page-title">Patient Concerns</h1><p class="ea-page-subtitle">Review patient submissions and provide solutions.</p></div>
  <div class="ea-page-actions"><span class="ea-meta">Total: <?= count($rows) ?></span></div>
</section>
<?php if ($successMessage): ?><div class="alert alert-success"><?= e($successMessage) ?></div><?php endif; ?>
<div class="alert alert-secondary"><b>Note:</b> Severity may be auto-generated from symptoms when Algorithm Mode is enabled.</div>
<?php if (!$rows): ?>
  <div class="ea-empty-state"><span class="ea-icon-pill"><i class="bi bi-clipboard2-heart"></i></span><h3>No concerns yet</h3><p>Patient concerns will appear here once users submit consultation requests.</p></div>
<?php else: ?>
  <div class="ea-table-wrap"><div class="table-responsive shadow-none"><table class="table ea-table align-middle mb-0">
    <thead><tr><th>ID</th><th>Patient</th><th>Symptoms</th><th>Severity</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><div class="fw-semibold"><?= e($r['full_name'] ?? '-') ?></div><div class="ea-meta"><?= e($r['email'] ?? '') ?><?= !empty($r['phone']) ? ' | ' . e($r['phone']) : '' ?></div></td>
        <td><?= e(mb_strimwidth(trim((string)($r['symptoms'] ?? '')), 0, 120, '...') ?: '-') ?></td>
        <td><?= doctor_sev_badge($r['severity'] ?? 'mild') ?></td>
        <td><?= doctor_status_badge($r['status'] ?? 'pending') ?></td>
        <td class="ea-meta"><?= e($r['created_at'] ?? '') ?></td>
        <td><a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/doctor/concern_view.php?id=<?= (int)$r['id'] ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
