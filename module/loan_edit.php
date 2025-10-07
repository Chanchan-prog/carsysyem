<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
// Load loan and ensure ownership and pending status
$stmt = $conn->prepare('SELECT * FROM loans WHERE id=? AND user_id=?');
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();
if (!$loan || $loan['status'] !== 'pending') {
  echo '<div class="alert alert-danger">Loan not found or cannot be edited.</div>';
  return;
}
$cars = get_active_cars();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $carId = (int)($_POST['car_id'] ?? 0);
  $price = (float)($_POST['price'] ?? 0);
  $down = (float)($_POST['down_payment'] ?? 0);
  $rate = (float)($_POST['annual_rate'] ?? 0);
  $months = (int)($_POST['term_months'] ?? 0);
  if ($carId && $price > 0 && $months > 0 && $rate >= 0 && $down >= 0 && $down <= $price) {
    $principal = max(0, $price - $down);
    $upd = $conn->prepare('UPDATE loans SET car_id=?, car_price=?, down_payment=?, principal=?, annual_interest_rate=?, term_months=? WHERE id=? AND user_id=? AND status="pending"');
    $upd->bind_param('iidddiii', $carId, $price, $down, $principal, $rate, $months, $id, $user['id']);
    if ($upd->execute()) {
      header('Location: index.php?page=my_loans');
      exit();
    } else {
      $message = 'Failed to update loan.';
    }
  } else {
    $message = 'Please fill all fields correctly.';
  }
}
?>
<div class="pagetitle"><h1>Edit Loan</h1></div>
<section class="section">
  <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Update Details</h5>
      <form method="post" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Car</label>
          <select name="car_id" class="form-select" required>
            <?php foreach ($cars as $c): ?>
              <option value="<?php echo $c['id']; ?>" <?php echo $c['id']==$loan['car_id']?'selected':''; ?>><?php echo htmlspecialchars($c['model']); ?> - ₱<?php echo number_format($c['price'],2); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Car Price</label>
          <input type="number" step="0.01" class="form-control" name="price" value="<?php echo htmlspecialchars($loan['car_price']); ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Down Payment</label>
          <input type="number" step="0.01" class="form-control" name="down_payment" value="<?php echo htmlspecialchars($loan['down_payment']); ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Annual Interest Rate (%)</label>
          <input type="number" step="0.01" class="form-control" name="annual_rate" value="<?php echo htmlspecialchars($loan['annual_interest_rate']); ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Term (months)</label>
          <input type="number" class="form-control" name="term_months" value="<?php echo htmlspecialchars($loan['term_months']); ?>" required>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <a href="index.php?page=my_loans" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</section>


