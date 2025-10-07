<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';
$user = current_user();
$loans = list_user_loans($user['id']);
?>
<div class="pagetitle"><h1>My Applications</h1></div>
<section class="section">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Loans</h5>
      <table class="table table-striped">
        <thead><tr>
          <th>ID</th><th>Car</th><th>Principal</th><th>Rate</th><th>Term</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php while($row = $loans->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['model']); ?></td>
            <td>₱<?php echo number_format($row['principal'],2); ?></td>
            <td><?php echo $row['annual_interest_rate']; ?>%</td>
            <td><?php echo $row['term_months']; ?></td>
            <td><span class="badge bg-<?php echo $row['status']==='approved'?'success':($row['status']==='rejected'?'danger':'warning'); ?>"><?php echo strtoupper($row['status']); ?></span></td>
            <td>
              <?php if ($row['status']==='approved'): ?>
                <a href="index.php?page=emi_schedule&loan_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">EMI Schedule</a>
              <?php elseif ($row['status']==='pending'): ?>
                <a href="index.php?page=loan_edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                <form method="post" action="index.php?page=loan_delete" style="display:inline" onsubmit="return confirm('Delete this application?');">
                  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                  <button class="btn btn-sm btn-danger">Delete</button>
                </form>
              <?php else: ?>
                <span class="text-muted">Rejected</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>


