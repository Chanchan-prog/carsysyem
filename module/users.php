<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../db/db.php';

$hasBlocked = $conn->query("SHOW COLUMNS FROM users LIKE 'blocked'");
if (!$hasBlocked || $hasBlocked->num_rows === 0) {
  $conn->query("ALTER TABLE users ADD COLUMN blocked TINYINT(1) NOT NULL DEFAULT 0 AFTER role");
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'create') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';
    $password = $_POST['password'] ?? '';
    if ($username && $full_name && filter_var($email, FILTER_VALIDATE_EMAIL) && $password) {
      $check = $conn->prepare('SELECT 1 FROM users WHERE username=? OR email=?');
      $check->bind_param('ss', $username, $email);
      $check->execute();
      if ($check->get_result()->fetch_assoc()) {
        $message = 'Username or email already exists.';
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $conn->prepare('INSERT INTO users (username, password_hash, full_name, role, email) VALUES (?,?,?,?,?)');
        $ins->bind_param('sssss', $username, $hash, $full_name, $role, $email);
        $ins->execute();
        $message = 'User created.';
      }
    } else {
      $message = 'Please fill all fields correctly.';
    }
  } elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';
    $password = $_POST['password'] ?? '';
    if ($id && $username && $full_name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      // Ensure unique username/email for others
      $chk = $conn->prepare('SELECT 1 FROM users WHERE (username=? OR email=?) AND id<>?');
      $chk->bind_param('ssi', $username, $email, $id);
      $chk->execute();
      if ($chk->get_result()->fetch_assoc()) {
        $message = 'Username or email already in use by another user.';
      } else {
        if ($password !== '') {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $upd = $conn->prepare('UPDATE users SET username=?, full_name=?, email=?, role=?, password_hash=? WHERE id=?');
          $upd->bind_param('sssssi', $username, $full_name, $email, $role, $hash, $id);
        } else {
          $upd = $conn->prepare('UPDATE users SET username=?, full_name=?, email=?, role=? WHERE id=?');
          $upd->bind_param('ssssi', $username, $full_name, $email, $role, $id);
        }
        $upd->execute();
        $message = 'User updated.';
      }
    } else {
      $message = 'Invalid data.';
    }
  } elseif ($action === 'block') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && $id !== (int)($_SESSION['user_id'] ?? 0)) {
      $upd = $conn->prepare('UPDATE users SET blocked=1 WHERE id=?');
      $upd->bind_param('i', $id);
      $upd->execute();
      $message = 'User blocked.';
    } else {
      $message = 'Cannot block current user.';
    }
  } elseif ($action === 'unblock') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      $upd = $conn->prepare('UPDATE users SET blocked=0 WHERE id=?');
      $upd->bind_param('i', $id);
      $upd->execute();
      $message = 'User unblocked.';
    }
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    // Prevent deleting yourself
    if ($id && $id !== (int)($_SESSION['user_id'] ?? 0)) {
      $del = $conn->prepare('DELETE FROM users WHERE id=?');
      $del->bind_param('i', $id);
      $del->execute();
      $message = 'User deleted.';
    } else {
      $message = 'Cannot delete current user.';
    }
  }
}

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
if ($q !== '') {
  $safe = '%' . $conn->real_escape_string($q) . '%';
  $where = "WHERE username LIKE '$safe' OR full_name LIKE '$safe' OR email LIKE '$safe' OR role LIKE '$safe'";
}
$countRes = $conn->query("SELECT COUNT(*) AS c FROM users $where");
$total = $countRes ? (int)$countRes->fetch_assoc()['c'] : 0;
$res = $conn->query("SELECT id, username, full_name, email, role, blocked, created_at FROM users $where ORDER BY id DESC LIMIT $perPage OFFSET $offset");
?>
<div class="pagetitle"><h1>Users</h1></div>
<section class="section">
  <?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <form class="row g-2 mb-2" method="get">
    <input type="hidden" name="page" value="users">
    <div class="col-md-4">
      <input type="text" class="form-control" name="q" placeholder="Search username, name, email, role" value="<?php echo htmlspecialchars($q); ?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </div>
  </form>
  <div class="card mb-3">
    <div class="card-body">
      <h5 class="card-title">Add User</h5>
      <form method="post" class="row g-2">
        <input type="hidden" name="action" value="create">
        <div class="col-md-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Role</label>
          <select name="role" class="form-select">
          <!--  <option value="customer">Customer</option> -->
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-primary" type="submit">Create</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">All Users</h5>
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($res && $res->num_rows > 0): ?>
              <?php while ($u = $res->fetch_assoc()): ?>
                <tr>
                  <form method="post" class="row g-1" style="--bs-gutter-x:0.5rem">
                    <td class="col-auto align-middle"><?php echo (int)$u['id']; ?></td>
                    <td class="col"><input name="username" class="form-control form-control-sm" value="<?php echo htmlspecialchars($u['username']); ?>" required></td>
                    <td class="col"><input name="full_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($u['full_name']); ?>" required></td>
                    <td class="col"><input type="email" name="email" class="form-control form-control-sm" value="<?php echo htmlspecialchars($u['email']); ?>" required></td>
                    <td class="col-2">
                      <select name="role" class="form-select form-select-sm">
                        <option value="customer" <?php echo $u['role']==='customer'?'selected':''; ?>>Customer</option>
                        <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                      </select>
                    </td>
                    <td class="col-auto align-middle">
                      <?php if ((int)($u['blocked'] ?? 0) === 1): ?>
                        <span class="badge bg-dark">Blocked</span>
                      <?php else: ?>
                        <span class="badge bg-success">Active</span>
                      <?php endif; ?>
                    </td>
                    <td class="col-auto align-middle"><?php echo htmlspecialchars($u['created_at']); ?></td>
                    <td class="col-4">
                      <div class="d-flex gap-1">
                        <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                        <input type="password" name="password" class="form-control form-control-sm" placeholder="New password (optional)">
                        <button name="action" value="update" class="btn btn-sm btn-success">Save</button>
                        <?php if ((int)($u['blocked'] ?? 0) === 1): ?>
                          <button name="action" value="unblock" class="btn btn-sm btn-warning">Unblock</button>
                        <?php else: ?>
                          <button name="action" value="block" class="btn btn-sm btn-outline-secondary" <?php echo ((int)$u['id'])===(int)($_SESSION['user_id']??0)?'disabled':''; ?>>Block</button>
                        <?php endif; ?>
                        <button name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')" <?php echo ((int)$u['id'])===(int)($_SESSION['user_id']??0)?'disabled':''; ?>>Delete</button>
                      </div>
                    </td>
                  </form>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center">No users found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <?php 
          $totalPages = (int)ceil(max(1, $total) / $perPage);
          $base = 'index.php?page=users&q=' . urlencode($q) . '&p=';
        ?>
        <nav>
          <ul class="pagination">
            <li class="page-item <?php echo $page<=1?'disabled':''; ?>"><a class="page-link" href="<?php echo $base . max(1, $page-1); ?>">Prev</a></li>
            <li class="page-item disabled"><span class="page-link">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span></li>
            <li class="page-item <?php echo $page>=$totalPages?'disabled':''; ?>"><a class="page-link" href="<?php echo $base . min($totalPages, $page+1); ?>">Next</a></li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
  
</section>


