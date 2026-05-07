<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';

require_login();
require_role('doctor');

$u = current_user();
$profile = get_doctor_profile((int)$u['id']);
$notice = $_SESSION['doctor_notice'] ?? null;
unset($_SESSION['doctor_notice']);

$stats = ['total' => 0, 'pending' => 0, 'solved' => 0];
$recentConcerns = [];
if ($profile && ($profile['verification_status'] ?? '') === 'verified') {
    $stats['total'] = (int)db()->query("SELECT COUNT(*) FROM patient_concerns")->fetchColumn();
    $stats['pending'] = (int)db()->query("SELECT COUNT(*) FROM patient_concerns WHERE status='pending'")->fetchColumn();
    $stats['solved'] = (int)db()->query("SELECT COUNT(*) FROM patient_concerns WHERE status='solution_provided'")->fetchColumn();
    $recentConcerns = db()->query("
        SELECT pc.id, pc.symptoms, pc.severity, pc.status, pc.created_at, u.full_name
        FROM patient_concerns pc
        JOIN users u ON u.id = pc.user_id
        ORDER BY pc.id DESC
        LIMIT 6
    ")->fetchAll();
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">Doctor Panel</div>
    <h1 class="ea-page-title">Doctor Dashboard</h1>
    <p class="ea-page-subtitle">Welcome, <?= e($u['full_name'] ?? 'Doctor') ?>.</p>
  </div>
</section>

<?php if ($notice): ?><div class="alert alert-warning"><?= e($notice) ?></div><?php endif; ?>

<?php if (!$profile): ?>
  <div class="ea-panel">
    <h2 class="ea-panel-title">Please complete your doctor registration for admin verification.</h2>
    <a class="btn btn-success mt-3" href="<?= BASE_URL ?>/doctor/register_profile.php">Complete Registration</a>
  </div>
<?php elseif (($profile['verification_status'] ?? '') === 'pending'): ?>
  <div class="alert alert-info">Your doctor verification request is pending admin approval.</div>
<?php elseif (($profile['verification_status'] ?? '') === 'rejected'): ?>
  <div class="alert alert-danger">
    <div class="fw-semibold">Your doctor verification request was rejected.</div>
    <div><?= nl2br(e($profile['rejection_reason'] ?? 'No reason provided.')) ?></div>
  </div>
  <a class="btn btn-success" href="<?= BASE_URL ?>/doctor/register_profile.php">Resubmit Registration</a>
<?php else: ?>
  <div class="row g-4 mb-4">
    <div class="col-md-4"><div class="ea-stat-card"><div class="ea-stat-icon"><i class="bi bi-clipboard2-heart"></i></div><div class="ea-stat-label">Total Patient Concerns</div><div class="ea-stat-value"><?= (int)$stats['total'] ?></div></div></div>
    <div class="col-md-4"><div class="ea-stat-card"><div class="ea-stat-icon"><i class="bi bi-hourglass-split"></i></div><div class="ea-stat-label">Pending Concerns</div><div class="ea-stat-value"><?= (int)$stats['pending'] ?></div></div></div>
    <div class="col-md-4"><div class="ea-stat-card"><div class="ea-stat-icon"><i class="bi bi-check2-circle"></i></div><div class="ea-stat-label">Solved Concerns</div><div class="ea-stat-value"><?= (int)$stats['solved'] ?></div></div></div>
  </div>
  <div class="ea-panel">
    <div class="ea-panel-header">
      <div><h2 class="ea-panel-title">Recent Concerns</h2><p class="ea-panel-subtitle">Latest patient submissions.</p></div>
      <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/doctor/concerns.php">Manage Concerns</a>
    </div>
    <?php if (!$recentConcerns): ?>
      <div class="ea-empty-state"><span class="ea-icon-pill"><i class="bi bi-clipboard2-heart"></i></span><h3>No concerns yet</h3><p>Patient concerns will appear here.</p></div>
    <?php else: ?>
      <div class="ea-table-wrap shadow-none"><div class="table-responsive shadow-none"><table class="table ea-table mb-0">
        <thead><tr><th>ID</th><th>Patient</th><th>Symptoms</th><th>Severity</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
        <?php foreach ($recentConcerns as $c): ?>
          <tr><td><?= (int)$c['id'] ?></td><td><?= e($c['full_name']) ?></td><td><?= e(mb_strimwidth($c['symptoms'] ?? '', 0, 90, '...')) ?></td><td><?= e($c['severity']) ?></td><td><?= e($c['status']) ?></td><td class="ea-meta"><?= e($c['created_at']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
