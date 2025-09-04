<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';
$loanId = (int)($_GET['loan_id'] ?? 0);
if (!$loanId) { echo '<div class="alert alert-danger">Invalid loan.</div>'; return; }
$emis = list_loan_emis($loanId);
?>
<div class="pagetitle"><h1>EMI Schedule</h1></div>
<section class="section">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Installments</h5>
      <table class="table table-bordered">
        <thead><tr>
          <th>#</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Amount</th><th>Status</th><th>Action</th>
        </tr></thead>
        <tbody>
        <?php while($row = $emis->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['installment_no']; ?></td>
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
                  <input type="hidden" name="emi_id" value="<?php echo $row['id']; ?>">
                  <button class="btn btn-sm btn-outline-success">Mark Paid</button>
                </form>
              <?php else: ?>
                <span class="text-muted">Paid</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>


