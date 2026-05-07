<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/algorithms.php';

require_verified_doctor();
csrf_init();

$doctor = current_user();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid concern id.');

$stmt = db()->prepare("
  SELECT pc.*, u.full_name, u.email
  FROM patient_concerns pc
  JOIN users u ON u.id = pc.user_id
  WHERE pc.id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) die('Concern not found.');

$existingStmt = db()->prepare("SELECT * FROM solutions WHERE concern_id=? ORDER BY id DESC LIMIT 1");
$existingStmt->execute([$id]);
$existing = $existingStmt->fetch() ?: [];

$solution_title = $existing['solution_title'] ?? ($c['disease_name'] ?? '');
$solution_details = $existing['solution_details'] ?? '';
$recommended_products = $existing['recommended_products'] ?? '';
$formError = '';
$algo_result = algo_symptom_solution_match($c['symptoms'] ?? '', $c['mental_condition'] ?? '', $c['digestive_issues'] ?? '');

if (is_post()) {
    csrf_verify();
    $solution_title = trim($_POST['solution_title'] ?? '');
    $solution_details = trim($_POST['solution_details'] ?? '');
    $recommended_products = trim($_POST['recommended_products'] ?? '');
    if ($solution_title === '' || $solution_details === '') {
        $formError = 'Title and details are required.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $updConcern = $pdo->prepare("UPDATE patient_concerns SET disease_name=?, status='solution_provided' WHERE id=?");
            $updConcern->execute([$solution_title, $id]);

            $solutionIdStmt = $pdo->prepare("SELECT id FROM solutions WHERE concern_id=? ORDER BY id DESC LIMIT 1");
            $solutionIdStmt->execute([$id]);
            $existingSolutionId = $solutionIdStmt->fetchColumn();

            if ($existingSolutionId) {
                $updSolution = $pdo->prepare("
                    UPDATE solutions
                    SET solution_title=?, solution_details=?, recommended_products=?, doctor_id=?
                    WHERE id=?
                ");
                $updSolution->execute([$solution_title, $solution_details, $recommended_products ?: null, (int)$doctor['id'], (int)$existingSolutionId]);
            } else {
                $insSolution = $pdo->prepare("
                    INSERT INTO solutions (concern_id, admin_id, doctor_id, solution_title, solution_details, recommended_products)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insSolution->execute([$id, null, (int)$doctor['id'], $solution_title, $solution_details, $recommended_products ?: null]);
            }
            $pdo->commit();
            $_SESSION['doctor_success_message'] = 'Solution submitted successfully.';
            redirect('/doctor/concerns.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>
<section class="ea-page-head">
  <div><div class="ea-page-kicker">Doctor Panel</div><h1 class="ea-page-title">Create Solution</h1><p class="ea-page-subtitle">Provide the diagnosis, advice, and recommended products visible to the patient.</p></div>
  <div class="ea-page-actions"><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/doctor/concern_view.php?id=<?= (int)$id ?>">Back</a></div>
</section>
<?php if ($formError): ?><div class="alert alert-danger"><?= e($formError) ?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="ea-panel">
      <h2 class="ea-panel-title"><?= e($c['full_name']) ?></h2>
      <div class="ea-note-card mt-3"><div class="fw-semibold mb-2">Symptoms</div><?= nl2br(e($c['symptoms'] ?? '')) ?></div>
      <?php if (!empty($algo_result['top_categories'])): ?>
        <h3 class="h4 mt-4">Algorithm Suggestions</h3>
        <div class="d-flex flex-column gap-2"><?php foreach ($algo_result['top_categories'] as $cat): ?><div class="ea-note-card d-flex justify-content-between"><strong><?= e($cat['category']) ?></strong><span class="badge text-bg-warning">Score: <?= (int)$cat['score'] ?></span></div><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <form method="post" class="ea-form-card">
      <div class="ea-form-body">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label fw-semibold">Disease Name / Diagnosis</label><input class="form-control" name="solution_title" value="<?= e($solution_title) ?>" required></div>
        <div class="mb-3"><label class="form-label fw-semibold">Solution Details</label><textarea class="form-control" name="solution_details" rows="7" required><?= e($solution_details) ?></textarea></div>
        <div class="mb-3"><label class="form-label fw-semibold">Recommended Products</label><textarea class="form-control" name="recommended_products" rows="4"><?= e($recommended_products) ?></textarea></div>
        <button class="btn btn-success w-100">Save Solution</button>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
