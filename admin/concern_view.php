<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/algorithms.php';

require_login();
require_role('admin');
csrf_init();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid concern id.");

$stmt = db()->prepare("
    SELECT pc.*, u.full_name, u.email
    FROM patient_concerns pc
    JOIN users u ON u.id = pc.user_id
    WHERE pc.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c) die("Concern not found.");

$existingSolutionStmt = db()->prepare("
    SELECT solution_title, solution_details, recommended_products
    FROM solutions
    WHERE concern_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$existingSolutionStmt->execute([$id]);
$existingSolution = $existingSolutionStmt->fetch() ?: [];

$solution_title = $c['disease_name'] ?? ($existingSolution['solution_title'] ?? '');
$solution_details = $existingSolution['solution_details'] ?? '';
$recommended_products = $existingSolution['recommended_products'] ?? '';
$formError = '';

$algo_result = algo_symptom_solution_match(
    $c['symptoms'] ?? '',
    $c['mental_condition'] ?? '',
    $c['digestive_issues'] ?? ''
);

if (is_post()) {
    csrf_verify();

    $admin = current_user();
    $solution_title = trim($_POST['solution_title'] ?? '');
    $solution_details = trim($_POST['solution_details'] ?? '');
    $recommended_products = trim($_POST['recommended_products'] ?? '');

    if ($solution_title === '' || $solution_details === '') {
        $formError = 'Title and Details required.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $updConcern = $pdo->prepare("
                UPDATE patient_concerns
                SET disease_name = ?,
                    status = 'solution_provided'
                WHERE id = ?
            ");
            $updConcern->execute([
                $solution_title,
                $id
            ]);

            $solutionIdStmt = $pdo->prepare("
                SELECT id
                FROM solutions
                WHERE concern_id = ?
                ORDER BY id DESC
                LIMIT 1
            ");
            $solutionIdStmt->execute([$id]);
            $existingSolutionId = $solutionIdStmt->fetchColumn();

            if ($existingSolutionId) {
                $updSolution = $pdo->prepare("
                    UPDATE solutions
                    SET solution_title = ?,
                        solution_details = ?,
                        recommended_products = ?,
                        admin_id = ?
                    WHERE id = ?
                ");
                $updSolution->execute([
                    $solution_title,
                    $solution_details,
                    $recommended_products ?: null,
                    (int)$admin['id'],
                    (int)$existingSolutionId
                ]);
            } else {
                $insSolution = $pdo->prepare("
                    INSERT INTO solutions
                        (concern_id, admin_id, solution_title, solution_details, recommended_products)
                    VALUES
                        (?, ?, ?, ?, ?)
                ");
                $insSolution->execute([
                    $id,
                    (int)$admin['id'],
                    $solution_title,
                    $solution_details,
                    $recommended_products ?: null
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $_SESSION['admin_success_message'] = 'Consultation submitted successfully.';
        redirect('/admin/concerns_list.php');
    }
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<style>
  .ea-two-col {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) 420px;
    gap: 1.5rem;
    align-items: start;
  }

  .ea-detail-block + .ea-detail-block {
    border-top: 1px solid rgba(26, 71, 42, 0.08);
    margin-top: 1.25rem;
    padding-top: 1.25rem;
  }

  @media (max-width: 991.98px) {
    .ea-two-col {
      grid-template-columns: 1fr;
    }
  }
</style>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Concern Details</h1>
    <p class="ea-page-subtitle">Review the patient submission and add the final diagnosis, advice, and recommended products.</p>
  </div>
  <div class="ea-page-actions">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/concerns_list.php">Back to Queue</a>
  </div>
</section>

<?php if ($formError): ?>
  <div class="alert alert-danger"><?= e($formError) ?></div>
<?php endif; ?>

<section class="ea-two-col">
  <div>
    <div class="ea-panel">
      <div class="ea-panel-header">
        <div>
          <h2 class="ea-panel-title"><?= e($c['full_name']) ?></h2>
          <p class="ea-panel-subtitle"><?= e($c['email']) ?></p>
        </div>
        <span class="badge text-bg-secondary">Patient Submission</span>
      </div>

      <div class="ea-detail-block">
        <h3 class="h3 mb-2">Symptoms</h3>
        <div class="ea-meta"><?= nl2br(e($c['symptoms'])) ?></div>
      </div>

      <div class="ea-detail-block">
        <h3 class="h3 mb-2">Mental Condition</h3>
        <div class="ea-meta"><?= nl2br(e($c['mental_condition'] ?? '—')) ?></div>
      </div>

      <div class="ea-detail-block">
        <h3 class="h3 mb-2">Digestive Issues</h3>
        <div class="ea-meta"><?= nl2br(e($c['digestive_issues'] ?? '—')) ?></div>
      </div>

      <div class="ea-detail-block">
        <h3 class="h3 mb-2">Old Treatment</h3>
        <div class="ea-meta"><?= nl2br(e($c['old_treatment_history'] ?? '—')) ?></div>
      </div>
    </div>

    <?php if ($algo_result['mode'] === 'WITH_ALGORITHM'): ?>
      <div class="ea-panel">
        <div class="ea-panel-header">
          <div>
            <h2 class="ea-panel-title">Algorithm Suggestions</h2>
            <p class="ea-panel-subtitle">Decision-support categories based on submitted symptoms and health context.</p>
          </div>
        </div>

        <?php if (!empty($algo_result['top_categories'])): ?>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($algo_result['top_categories'] as $cat): ?>
              <div class="ea-note-card d-flex justify-content-between align-items-center gap-3">
                <strong><?= e($cat['category']) ?></strong>
                <span class="badge text-bg-warning">Score: <?= (int)$cat['score'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-secondary mb-0">No strong category match found.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="ea-form-card">
    <div class="ea-form-body">
      <div class="ea-form-section pt-0 mt-0 border-0">
        <h2 class="ea-form-title">Update Solution</h2>
        <p class="ea-form-help">Save the diagnosis and final response that will be visible in the patient’s solution view.</p>

        <form method="post">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Disease Name (Admin Diagnosis)</label>
            <input type="text" name="solution_title" class="form-control" value="<?= e($solution_title) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Solution Details</label>
            <textarea name="solution_details" class="form-control" rows="6" required><?= e($solution_details) ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Recommended Products</label>
            <textarea name="recommended_products" class="form-control" rows="4"><?= e($recommended_products) ?></textarea>
          </div>

          <div class="ea-form-actions">
            <button class="btn btn-success w-100">Save Solution</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
