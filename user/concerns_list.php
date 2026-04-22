<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/includes/header.php';

require_login();
require_role('user');
csrf_init();

$u = current_user();

$stmt = db()->prepare("SELECT * FROM patient_concerns WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$u['id']]);
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
    <h1 class="ea-page-title">My Health Concerns</h1>
    <p class="ea-page-subtitle">Track your submitted concerns, review statuses, and manage updates before admin response.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-success" href="<?= BASE_URL ?>/user/concerns_add.php"><i class="bi bi-plus-circle me-1"></i>New Concern</a>
  </div>
</section>

<?php if (!$rows): ?>
  <div class="ea-empty-state">
    <span class="ea-icon-pill"><i class="bi bi-clipboard2-heart"></i></span>
    <h3>No concerns yet</h3>
    <p>Submit your first concern to begin the Ayurvedic consultation process.</p>
  </div>
<?php else: ?>
  <div class="ea-table-wrap">
    <div class="table-responsive shadow-none">
      <table class="table ea-table align-middle mb-0">
        <thead>
          <tr>
            <th style="width:60px;">#</th>
            <th>Symptoms</th>
            <th style="width:110px;">Severity</th>
            <th style="width:160px;">Status</th>
            <th style="width:180px;">Submitted</th>
            <th style="width:240px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td>
                <div class="fw-semibold"><?= e(mb_strimwidth($r['symptoms'] ?? '', 0, 100, '...')) ?></div>
              </td>
              <td><?= sev_badge($r['severity'] ?? 'mild') ?></td>
              <td><?= status_badge($r['status'] ?? 'pending') ?></td>
              <td class="ea-meta"><?= e($r['created_at'] ?? '') ?></td>
              <td>
                <div class="ea-actions">
                  <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/user/concern_view.php?id=<?= (int)$r['id'] ?>">View</a>
                  <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/user/concern_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                  <form method="post" action="<?= BASE_URL ?>/user/concern_delete.php" onsubmit="return confirm('Delete this concern?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
