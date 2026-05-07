<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/algorithms.php';

require_verified_doctor();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid concern id.');

$stmt = db()->prepare("
  SELECT pc.*, u.full_name, u.email, u.phone
  FROM patient_concerns pc
  JOIN users u ON u.id = pc.user_id
  WHERE pc.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) die('Concern not found.');

$algo_result = algo_symptom_solution_match($c['symptoms'] ?? '', $c['mental_condition'] ?? '', $c['digestive_issues'] ?? '');
$solutionStmt = db()->prepare("SELECT * FROM solutions WHERE concern_id=? ORDER BY id DESC LIMIT 1");
$solutionStmt->execute([$id]);
$solution = $solutionStmt->fetch();

require_once __DIR__ . '/../app/includes/header.php';
?>
<style>.ea-two-col{display:grid;grid-template-columns:minmax(0,1.15fr) 380px;gap:1.5rem;align-items:start}.ea-detail-block+.ea-detail-block{border-top:1px solid rgba(26,71,42,.08);margin-top:1.25rem;padding-top:1.25rem}@media(max-width:991.98px){.ea-two-col{grid-template-columns:1fr}}</style>
<section class="ea-page-head">
  <div><div class="ea-page-kicker">Doctor Panel</div><h1 class="ea-page-title">Concern Details</h1><p class="ea-page-subtitle">Review the patient submission and algorithm suggestions.</p></div>
  <div class="ea-page-actions"><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/doctor/concerns.php">Back</a><a class="btn btn-success" href="<?= BASE_URL ?>/doctor/solution_create.php?id=<?= (int)$c['id'] ?>">Create / Update Solution</a></div>
</section>
<section class="ea-two-col">
  <div class="ea-panel">
    <div class="ea-panel-header"><div><h2 class="ea-panel-title"><?= e($c['full_name']) ?></h2><p class="ea-panel-subtitle"><?= e($c['email']) ?><?= !empty($c['phone']) ? ' | ' . e($c['phone']) : '' ?></p></div><span class="badge text-bg-secondary"><?= e($c['status'] ?? 'pending') ?></span></div>
    <div class="ea-detail-block"><h3 class="h3 mb-2">Symptoms</h3><div class="ea-meta"><?= nl2br(e($c['symptoms'] ?? '')) ?></div></div>
    <div class="ea-detail-block"><h3 class="h3 mb-2">Mental Condition</h3><div class="ea-meta"><?= nl2br(e($c['mental_condition'] ?? '-')) ?></div></div>
    <div class="ea-detail-block"><h3 class="h3 mb-2">Digestive Issues</h3><div class="ea-meta"><?= nl2br(e($c['digestive_issues'] ?? '-')) ?></div></div>
    <div class="ea-detail-block"><h3 class="h3 mb-2">Old Treatment</h3><div class="ea-meta"><?= nl2br(e($c['old_treatment_history'] ?? '-')) ?></div></div>
  </div>
  <div>
    <div class="ea-panel">
      <h2 class="ea-panel-title">Algorithm Suggestions</h2>
      <?php if (!empty($algo_result['top_categories'])): ?>
        <div class="d-flex flex-column gap-2 mt-3"><?php foreach ($algo_result['top_categories'] as $cat): ?><div class="ea-note-card d-flex justify-content-between"><strong><?= e($cat['category']) ?></strong><span class="badge text-bg-warning">Score: <?= (int)$cat['score'] ?></span></div><?php endforeach; ?></div>
      <?php else: ?><div class="alert alert-secondary mb-0 mt-3">No strong category match found.</div><?php endif; ?>
    </div>
    <div class="ea-panel">
      <h2 class="ea-panel-title">Current Solution</h2>
      <?php if ($solution): ?><div class="fw-semibold"><?= e($solution['solution_title']) ?></div><div class="ea-meta mt-2"><?= nl2br(e($solution['solution_details'])) ?></div><?php else: ?><div class="alert alert-warning mb-0">No solution has been provided yet.</div><?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
