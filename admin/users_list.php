<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/csrf.php';

require_login();
require_role('admin');
csrf_init();

$allowedStatuses = [
  'active' => 'Active',
  'blocked' => 'Blocked',
  'removed' => 'Removed',
];

function normalize_user_management_status($status) {
  $status = strtolower(trim((string)$status));
  return in_array($status, ['active', 'blocked', 'removed'], true) ? $status : 'active';
}

function user_status_badge($status) {
  $status = normalize_user_management_status($status);
  if ($status === 'blocked') {
    return '<span class="badge text-bg-warning">Blocked</span>';
  }
  if ($status === 'removed') {
    return '<span class="badge text-bg-secondary">Removed</span>';
  }
  return '<span class="badge text-bg-success">Active</span>';
}

$currentAdmin = current_user();
$successMessage = $_SESSION['users_success_message'] ?? null;
$errorMessage = $_SESSION['users_error_message'] ?? null;
unset($_SESSION['users_success_message'], $_SESSION['users_error_message']);

if (is_post()) {
  csrf_verify();

  $userId = (int)($_POST['user_id'] ?? 0);
  $targetStatus = normalize_user_management_status($_POST['target_status'] ?? '');

  if ($userId <= 0 || !isset($allowedStatuses[$targetStatus])) {
    $_SESSION['users_error_message'] = 'Invalid user action.';
    redirect('/admin/users_list.php');
  }

  if ((int)($currentAdmin['id'] ?? 0) === $userId) {
    $_SESSION['users_error_message'] = 'You cannot change the status of your own logged-in admin account.';
    redirect('/admin/users_list.php');
  }

  $stmt = db()->prepare("SELECT id, full_name, role, status FROM users WHERE id=? LIMIT 1");
  $stmt->execute([$userId]);
  $targetUser = $stmt->fetch();

  if (!$targetUser) {
    $_SESSION['users_error_message'] = 'User not found.';
    redirect('/admin/users_list.php');
  }

  $currentStatus = normalize_user_management_status($targetUser['status'] ?? 'active');
  if ($currentStatus === $targetStatus) {
    $_SESSION['users_success_message'] = 'No changes were needed for this user.';
    redirect('/admin/users_list.php');
  }

  $stmt = db()->prepare("UPDATE users SET status=? WHERE id=?");
  $stmt->execute([$targetStatus, $userId]);

  $_SESSION['users_success_message'] = sprintf(
    '%s is now %s.',
    $targetUser['full_name'] ?? 'User',
    $allowedStatuses[$targetStatus]
  );
  redirect('/admin/users_list.php');
}

$search = trim((string)($_GET['search'] ?? ''));
$requestedFilter = strtolower(trim((string)($_GET['status'] ?? 'all')));
$filter = $requestedFilter === 'all' ? 'all' : (isset($allowedStatuses[$requestedFilter]) ? $requestedFilter : 'all');

$stats = [
  'total' => 0,
  'active' => 0,
  'blocked' => 0,
  'removed' => 0,
  'admin' => 0,
  'user' => 0,
];

try {
  $stats['total'] = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
  $stats['active'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
  $stats['blocked'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE status='blocked'")->fetchColumn();
  $stats['removed'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE status='removed'")->fetchColumn();
  $stats['admin'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
  $stats['user'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
} catch (Exception $e) {
  // Keep page usable
}

$where = [];
$params = [];

if ($search !== '') {
  $where[] = "(full_name LIKE ? OR email LIKE ?)";
  $like = '%' . $search . '%';
  $params[] = $like;
  $params[] = $like;
}

if ($filter !== 'all') {
  $where[] = "status = ?";
  $params[] = $filter;
}

$sql = "
  SELECT id, full_name, email, phone, role, status, created_at
  FROM users
";

if ($where) {
  $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY id DESC';

$users = [];
try {
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  $users = $stmt->fetchAll();
} catch (Exception $e) {
  $users = [];
}

require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="ea-page-head">
  <div>
    <div class="ea-page-kicker">eAyurvedic Admin</div>
    <h1 class="ea-page-title">Manage Users</h1>
    <p class="ea-page-subtitle">View, search, and manage registered users from one place.</p>
  </div>
</section>

<?php if ($successMessage): ?>
  <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage): ?>
  <div class="alert alert-danger"><?= e($errorMessage) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <div class="col-md-6 col-xl-2">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-people"></i></div>
      <div class="ea-stat-label">Total Users</div>
      <div class="ea-stat-value"><?= (int)$stats['total'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-2">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-person-check"></i></div>
      <div class="ea-stat-label">Active Users</div>
      <div class="ea-stat-value"><?= (int)$stats['active'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-2">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-person-lock"></i></div>
      <div class="ea-stat-label">Blocked Users</div>
      <div class="ea-stat-value"><?= (int)$stats['blocked'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-2">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-person-dash"></i></div>
      <div class="ea-stat-label">Removed Users</div>
      <div class="ea-stat-value"><?= (int)$stats['removed'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-2">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-shield-lock"></i></div>
      <div class="ea-stat-label">Admin Users</div>
      <div class="ea-stat-value"><?= (int)$stats['admin'] ?></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-2">
    <div class="ea-stat-card">
      <div class="ea-stat-icon"><i class="bi bi-person"></i></div>
      <div class="ea-stat-label">Customers</div>
      <div class="ea-stat-value"><?= (int)$stats['user'] ?></div>
    </div>
  </div>
</div>

<div class="ea-panel">
  <div class="ea-panel-header">
    <div>
      <h2 class="ea-panel-title">User Directory</h2>
      <p class="ea-panel-subtitle">Search by name or email, then manage account access with simple status actions.</p>
    </div>
  </div>

  <form method="get" class="row g-3 align-items-end mb-4">
    <div class="col-md-6 col-lg-7">
      <label class="form-label fw-semibold">Search</label>
      <input type="text" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search by name or email">
    </div>
    <div class="col-md-4 col-lg-3">
      <label class="form-label fw-semibold">Status Filter</label>
      <select class="form-select" name="status">
        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
        <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="blocked" <?= $filter === 'blocked' ? 'selected' : '' ?>>Blocked</option>
        <option value="removed" <?= $filter === 'removed' ? 'selected' : '' ?>>Removed</option>
      </select>
    </div>
    <div class="col-md-2 col-lg-2">
      <button class="btn btn-success w-100">Filter</button>
    </div>
  </form>

  <?php if (!$users): ?>
    <div class="ea-empty-state">
      <span class="ea-icon-pill"><i class="bi bi-people"></i></span>
      <h3>No users found</h3>
      <p>Try adjusting the search term or selected status filter.</p>
    </div>
  <?php else: ?>
    <div class="ea-table-wrap shadow-none">
      <div class="table-responsive shadow-none">
        <table class="table ea-table align-middle mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Registered Date</th>
              <th>Status</th>
              <th style="width:260px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <?php
                $userStatus = normalize_user_management_status($user['status'] ?? 'active');
                $isCurrentAdmin = (int)$user['id'] === (int)($currentAdmin['id'] ?? 0);
              ?>
              <tr>
                <td><?= (int)$user['id'] ?></td>
                <td>
                  <div class="fw-semibold"><?= e((string)($user['full_name'] ?? '-')) ?></div>
                  <?php if ($isCurrentAdmin): ?>
                    <div class="ea-meta">Current admin account</div>
                  <?php endif; ?>
                </td>
                <td><?= e((string)($user['email'] ?? '-')) ?></td>
                <td><?= e((string)($user['phone'] ?? '-')) ?></td>
                <td>
                  <span class="badge <?= (($user['role'] ?? 'user') === 'admin') ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                    <?= e(ucfirst((string)($user['role'] ?? 'user'))) ?>
                  </span>
                </td>
                <td class="ea-meta">
                  <?= !empty($user['created_at']) ? e(date('M j, Y', strtotime((string)$user['created_at']))) : '-' ?>
                </td>
                <td><?= user_status_badge($userStatus) ?></td>
                <td>
                  <?php if ($isCurrentAdmin): ?>
                    <span class="ea-meta">Status actions disabled for your account</span>
                  <?php else: ?>
                    <div class="ea-actions">
                      <?php if ($userStatus === 'active'): ?>
                        <form method="post">
                          <?= csrf_field() ?>
                          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                          <input type="hidden" name="target_status" value="blocked">
                          <button class="btn btn-sm btn-outline-success">Block</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Mark this user as removed?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                          <input type="hidden" name="target_status" value="removed">
                          <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                      <?php elseif ($userStatus === 'blocked'): ?>
                        <form method="post">
                          <?= csrf_field() ?>
                          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                          <input type="hidden" name="target_status" value="active">
                          <button class="btn btn-sm btn-outline-success">Unblock</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Mark this user as removed?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                          <input type="hidden" name="target_status" value="removed">
                          <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                      <?php else: ?>
                        <form method="post">
                          <?= csrf_field() ?>
                          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                          <input type="hidden" name="target_status" value="active">
                          <button class="btn btn-sm btn-outline-success">Restore</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
