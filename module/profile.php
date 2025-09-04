<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
$u = current_user();
if (!$u['id']) { require_login(); }

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Please provide a valid name and email.';
  } elseif ($password !== '' && $password !== $confirm) {
    $message = 'Passwords do not match.';
  } else {
    $chk = $conn->prepare('SELECT 1 FROM users WHERE (email=?) AND id<>?');
    $chk->bind_param('si', $email, $u['id']);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) {
      $message = 'Email already in use.';
    } else {
      if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $conn->prepare('UPDATE users SET full_name=?, email=?, password_hash=? WHERE id=?');
        $upd->bind_param('sssi', $full_name, $email, $hash, $u['id']);
      } else {
        $upd = $conn->prepare('UPDATE users SET full_name=?, email=? WHERE id=?');
        $upd->bind_param('ssi', $full_name, $email, $u['id']);
      }
      if ($upd->execute()) {
        $_SESSION['fullname'] = $full_name;
        $message = 'Profile updated.';
      } else {
        $message = 'Failed to update profile.';
      }
    }
  }
}

$stmt = $conn->prepare('SELECT username, full_name, email, role, created_at FROM users WHERE id=?');
$stmt->bind_param('i', $u['id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
?>
<div class="pagetitle"><h1>My Profile</h1></div>
<section class="section">
  <?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <div class="row">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Profile Details</h5>
          <form method="post" class="row g-3">
            <div class="col-12">
              <label class="form-label">Username</label>
              <input class="form-control" value="<?php echo htmlspecialchars($profile['username']); ?>" disabled>
            </div>
            <div class="col-12">
              <label class="form-label">Full Name</label>
              <input name="full_name" class="form-control" value="<?php echo htmlspecialchars($profile['full_name']); ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">New Password (optional)</label>
              <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
            </div>
            <div class="col-12">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Account Info</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>Role:</strong> <?php echo htmlspecialchars($profile['role']); ?></li>
            <li class="list-group-item"><strong>Member Since:</strong> <?php echo htmlspecialchars($profile['created_at']); ?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>


