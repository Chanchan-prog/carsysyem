<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
$u = current_user();
if (($u['role'] ?? 'customer') === 'customer') {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason !== '') {
      $conn->query("CREATE TABLE IF NOT EXISTS appeals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reason VARCHAR(255) NOT NULL,
        status ENUM('open','resolved') NOT NULL DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
      )");
      $stmt = $conn->prepare('INSERT INTO appeals (user_id, reason) VALUES (?, ?)');
      $stmt->bind_param('is', $u['id'], $reason);
      $stmt->execute();
      echo '<div class="alert alert-info">Appeal submitted. Admin will review your request.</div>';
    } else {
      echo '<div class="alert alert-warning">Please provide a reason for appeal.</div>';
    }
  }
  ?>
  <div class="pagetitle"><h1>Appeal Block</h1></div>
  <section class="section">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Submit Appeal</h5>
        <form method="post" class="row g-2">
          <div class="col-12">
            <label class="form-label">Reason</label>
            <input name="reason" class="form-control" placeholder="Explain why your account should be unblocked" required>
          </div>
          <div class="col-12">
            <button class="btn btn-primary">Send Appeal</button>
          </div>
        </form>
      </div>
    </div>
  </section>
  <?php
  return;
}

// Admin view
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $appealId = (int)($_POST['appeal_id'] ?? 0);
  $userId = (int)($_POST['user_id'] ?? 0);
  if ($action === 'resolve' && $appealId) {
    $conn->query("UPDATE appeals SET status='resolved' WHERE id=".$appealId);
  } elseif ($action === 'unblock' && $userId) {
    $conn->query("UPDATE users SET blocked=0 WHERE id=".$userId);
    if ($appealId) { $conn->query("UPDATE appeals SET status='resolved' WHERE id=".$appealId); }
  }
}
$conn->query("CREATE TABLE IF NOT EXISTS appeals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  status ENUM('open','resolved') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
)");
$appeals = $conn->query("SELECT a.*, u.username, u.full_name, u.blocked FROM appeals a JOIN users u ON u.id=a.user_id ORDER BY a.status='resolved', a.id DESC");
?>
<div class="pagetitle"><h1>User Appeals</h1></div>
<section class="section">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Pending Appeals</h5>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($appeals && $appeals->num_rows>0): while($a=$appeals->fetch_assoc()): ?>
              <tr>
                <td><?php echo (int)$a['id']; ?></td>
                <td><?php echo htmlspecialchars($a['full_name'] ?: $a['username']); ?></td>
                <td><?php echo htmlspecialchars($a['reason']); ?></td>
                <td><span class="badge bg-<?php echo $a['status']==='resolved'?'success':'warning'; ?>"><?php echo strtoupper($a['status']); ?></span></td>
                <td>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="appeal_id" value="<?php echo (int)$a['id']; ?>">
                    <input type="hidden" name="user_id" value="<?php echo (int)$a['user_id']; ?>">
                    <?php if ((int)$a['blocked']===1): ?>
                      <button name="action" value="unblock" class="btn btn-sm btn-primary">Unblock</button>
                    <?php endif; ?>
                    <button name="action" value="resolve" class="btn btn-sm btn-outline-secondary">Resolve</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="5" class="text-center">No appeals.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>


