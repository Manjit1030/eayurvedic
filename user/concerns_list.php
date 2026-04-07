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

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 fw-bold mb-0">My Health Concerns</h1>
  <a class="btn btn-success btn-sm" href="<?= BASE_URL ?>/user/concerns_add.php">+ New Concern</a>
</div>

<?php if (!$rows): ?>
  <div class="alert alert-info">No concerns yet. Submit your first concern.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px;">#</th>
          <th>Symptoms</th>
          <th style="width:110px;">Severity</th>
          <th style="width:160px;">Status</th>
          <th style="width:180px;">Submitted</th>
          <th style="width:210px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>

            <td>
              <?= e(mb_strimwidth($r['symptoms'] ?? '', 0, 80, '...')) ?>
            </td>

            <td><?= sev_badge($r['severity'] ?? 'mild') ?></td>
            <td><?= status_badge($r['status'] ?? 'pending') ?></td>
            <td><?= e($r['created_at'] ?? '') ?></td>

            <td>
              <a class="btn btn-sm btn-outline-primary"
                 href="<?= BASE_URL ?>/user/concern_view.php?id=<?= (int)$r['id'] ?>">View</a>

              <a class="btn btn-sm btn-outline-success"
                 href="<?= BASE_URL ?>/user/concern_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>

              <form method="post" action="<?= BASE_URL ?>/user/concern_delete.php"
                    class="d-inline" onsubmit="return confirm('Delete this concern?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>

          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
