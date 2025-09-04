<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';
$u = current_user();
if (!$u['id']) { require_login(); }

// Fetch all EMIs for the current user's approved loans
$conn->query("UPDATE emis SET status='late' WHERE status='due' AND due_date < CURDATE()");
$stmt = $conn->prepare("SELECT e.id, e.installment_no, e.due_date, e.principal_component, e.interest_component, e.amount, e.status, l.id AS loan_id, l.car_id, c.model, c.plate_no
                        FROM emis e
                        JOIN loans l ON l.id=e.loan_id
                        JOIN cars c ON c.id=l.car_id
                        WHERE l.user_id=? AND l.status='approved'
                        ORDER BY e.status='paid', e.due_date ASC, e.installment_no ASC");
$stmt->bind_param('i', $u['id']);
$stmt->execute();
$emis = $stmt->get_result();
?>
<div class="pagetitle"><h1>My Payments</h1></div>
<section class="section">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">EMI Schedule</h5>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Loan #</th>
              <th>Car</th>
              <th>Installment</th>
              <th>Due Date</th>
              <th>Principal</th>
              <th>Interest</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($emis && $emis->num_rows>0): while($row=$emis->fetch_assoc()): ?>
              <tr>
                <td><?php echo (int)$row['loan_id']; ?></td>
                <td><?php echo htmlspecialchars(($row['model'] ?? '') . (isset($row['plate_no']) && $row['plate_no']? ' ('.$row['plate_no'].')':'')); ?></td>
                <td><?php echo (int)$row['installment_no']; ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td>$<?php echo number_format($row['principal_component'],2); ?></td>
                <td>$<?php echo number_format($row['interest_component'],2); ?></td>
                <td>$<?php echo number_format($row['amount'],2); ?></td>
                <td>
                  <span class="badge bg-<?php echo $row['status']==='paid'?'success':($row['status']==='late'?'danger':'warning'); ?>"><?php echo strtoupper($row['status']); ?></span>
                </td>
                <td>
                  <?php if ($row['status']!=='paid'): ?>
                    <form method="post" action="module/emi_toggle_paid.php" class="d-inline">
                      <input type="hidden" name="emi_id" value="<?php echo (int)$row['id']; ?>">
                      <select name="method" class="form-select form-select-sm d-inline-block" style="width:auto; display:inline-block; vertical-align:middle;">
                        <option value="manual">Manual</option>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="bank">Bank Transfer</option>
                      </select>
                      <button class="btn btn-sm btn-outline-success">Mark Paid</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">Paid</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="9" class="text-center">No EMIs found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>


