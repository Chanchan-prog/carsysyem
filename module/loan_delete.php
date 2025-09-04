<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
$user = current_user();
$id = (int)($_POST['id'] ?? 0);
if ($id && $user['id']) {
  $stmt = $conn->prepare('DELETE FROM loans WHERE id=? AND user_id=? AND status="pending"');
  $stmt->bind_param('ii', $id, $user['id']);
  $stmt->execute();
}
header('Location: index.php?page=my_loans');
exit();


