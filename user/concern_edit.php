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
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid concern id.");

$stmt = db()->prepare("SELECT * FROM patient_concerns WHERE id=? AND user_id=? LIMIT 1");
$stmt->execute([$id, $u['id']]);
$c = $stmt->fetch();
if (!$c) die("Access denied or concern not found.");

$errors = [];
$symptoms = $c['symptoms'] ?? '';
$mental = $c['mental_condition'] ?? '';
$digestive = $c['digestive_issues'] ?? '';
$old = $c['old_treatment_history'] ?? '';

if (is_post()) {
  csrf_verify();

  $symptoms = trim($_POST['symptoms'] ?? '');
  $mental = trim($_POST['mental_condition'] ?? '');
  $digestive = trim($_POST['digestive_issues'] ?? '');
  $old = trim($_POST['old_treatment_history'] ?? '');

  if ($symptoms === '') $errors[] = "Symptoms are required.";

  if (!$errors) {
    $upd = db()->prepare("
      UPDATE patient_concerns
      SET symptoms=?, mental_condition=?, digestive_issues=?, old_treatment_history=?, status='pending'
      WHERE id=? AND user_id=?
    ");
    $upd->execute([
      $symptoms,
      $mental ?: null,
      $digestive ?: null,
      $old ?: null,
      $id,
      $u['id']
    ]);

    redirect('/user/concern_view.php?id=' . $id);
  }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 fw-bold mb-0">Edit Concern #<?= (int)$id ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>/user/concern_view.php?id=<?= (int)$id ?>">← Back</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="alert alert-warning">
  Editing a concern will mark it as <b>pending</b> again so admin can review the updated details.
</div>

<form method="post" class="card shadow-sm">
  <div class="card-body">
    <?= csrf_field() ?>

    <div class="mb-3">
      <label class="form-label">Symptoms *</label>
      <textarea name="symptoms" class="form-control" rows="4" required><?= e($symptoms) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Mental Condition (optional)</label>
      <textarea name="mental_condition" class="form-control" rows="2"><?= e($mental) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Digestive Issues (optional)</label>
      <textarea name="digestive_issues" class="form-control" rows="2"><?= e($digestive) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Old Treatment History (optional)</label>
      <textarea name="old_treatment_history" class="form-control" rows="2"><?= e($old) ?></textarea>
    </div>

    <button class="btn btn-success">Save Changes</button>
  </div>
</form>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
