<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/algorithms.php'; // IMPORTANT
require_once __DIR__ . '/../app/includes/header.php';

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

$solution_title = $c['solution_title'] ?? '';
$solution_details = $c['solution_details'] ?? '';
$recommended_products = $c['recommended_products'] ?? '';

/* -------------------------------------------------------
   ALGORITHM #3 — Symptom → Solution Matching
--------------------------------------------------------*/
$algo_result = algo_symptom_solution_match(
    $c['symptoms'] ?? '',
    $c['mental_condition'] ?? '',
    $c['digestive_issues'] ?? ''
);

/* -------------------------------------------------------
   HANDLE SOLUTION UPDATE
--------------------------------------------------------*/
if (is_post()) {
    csrf_verify();

    $solution_title = trim($_POST['solution_title'] ?? '');
    $solution_details = trim($_POST['solution_details'] ?? '');
    $recommended_products = trim($_POST['recommended_products'] ?? '');

    if ($solution_title === '' || $solution_details === '') {
        echo '<div class="alert alert-danger">Title and Details required.</div>';
    } else {

        $upd = db()->prepare("
            UPDATE patient_concerns
            SET disease_name = ?, 
                solution_title = ?, 
                solution_details = ?, 
                recommended_products = ?, 
                status = 'solution_provided'
            WHERE id = ?
        ");

        $upd->execute([
            $solution_title, // disease determined by admin
            $solution_title,
            $solution_details,
            $recommended_products ?: null,
            $id
        ]);

        redirect('/admin/concern_view.php?id=' . $id);
    }
}
?>

<div class="container py-4">

<h1 class="h4 mb-4">Concern Details</h1>

<div class="row g-4">

<!-- LEFT SIDE -->
<div class="col-lg-7">

<div class="card shadow-sm">
<div class="card-body">

<h5><?= e($c['full_name']) ?></h5>
<div class="text-muted small"><?= e($c['email']) ?></div>

<hr>

<h6>Symptoms</h6>
<p><?= nl2br(e($c['symptoms'])) ?></p>

<h6>Mental Condition</h6>
<p><?= nl2br(e($c['mental_condition'] ?? '—')) ?></p>

<h6>Digestive Issues</h6>
<p><?= nl2br(e($c['digestive_issues'] ?? '—')) ?></p>

<h6>Old Treatment</h6>
<p><?= nl2br(e($c['old_treatment_history'] ?? '—')) ?></p>

</div>
</div>


<!-- ALGORITHM DISPLAY -->
<?php if ($algo_result['mode'] === 'WITH_ALGORITHM'): ?>

<div class="card mt-4 border-success">
<div class="card-body">

<h6 class="text-success">
Algorithm #3: Symptom → Solution Suggestions
</h6>

<?php if (!empty($algo_result['top_categories'])): ?>

<ul>
<?php foreach ($algo_result['top_categories'] as $cat): ?>
<li>
<?= e($cat['category']) ?>
(score: <?= (int)$cat['score'] ?>)
</li>
<?php endforeach; ?>
</ul>

<?php else: ?>
<p>No strong category match found.</p>
<?php endif; ?>

</div>
</div>

<?php endif; ?>

</div>

<!-- RIGHT SIDE -->
<div class="col-lg-5">

<div class="card shadow-sm">
<div class="card-body">

<h5>Update Solution</h5>

<form method="post">
<?= csrf_field() ?>

<div class="mb-3">
<label class="form-label">Disease Name (Admin Diagnosis)</label>
<input type="text"
       name="solution_title"
       class="form-control"
       value="<?= e($solution_title) ?>"
       required>
</div>

<div class="mb-3">
<label class="form-label">Solution Details</label>
<textarea name="solution_details"
          class="form-control"
          rows="5"
          required><?= e($solution_details) ?></textarea>
</div>

<div class="mb-3">
<label class="form-label">Recommended Products</label>
<textarea name="recommended_products"
          class="form-control"
          rows="3"><?= e($recommended_products) ?></textarea>
</div>

<button class="btn btn-success w-100">
Save Solution
</button>

</form>

</div>
</div>

</div>
</div>

</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
