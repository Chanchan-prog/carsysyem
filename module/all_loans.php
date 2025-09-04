<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../db/db.php';
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
if ($q !== '') {
  $safe = '%' . $conn->real_escape_string($q) . '%';
  $where = "WHERE u.full_name LIKE '$safe' OR u.username LIKE '$safe' OR c.model LIKE '$safe' OR l.status LIKE '$safe'";
}
$countRes = $conn->query("SELECT COUNT(*) AS c FROM loans l JOIN users u ON u.id=l.user_id JOIN cars c ON c.id=l.car_id $where");
$total = $countRes ? (int)$countRes->fetch_assoc()['c'] : 0;
$res = $conn->query("SELECT l.*, u.full_name, u.username, c.model FROM loans l JOIN users u ON u.id=l.user_id JOIN cars c ON c.id=l.car_id $where ORDER BY l.id DESC LIMIT $perPage OFFSET $offset");
?>
<div class="pagetitle"><h1>All Loans & EMIs</h1></div>
<section class="section">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Loans</h5>
      <form class="row g-2 mb-2" method="get">
        <input type="hidden" name="page" value="all_loans">
        <div class="col-md-4">
          <input type="text" class="form-control" name="q" placeholder="Search customer, car, status" value="<?php echo htmlspecialchars($q); ?>">
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-primary" type="submit">Search</button>
        </div>
      </form>
      <table class="table table-hover">
        <thead><tr>
          <th>ID</th><th>Customer</th><th>Car</th><th>Principal</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
        <?php while($row=$res->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['model']); ?></td>
            <td>$<?php echo number_format($row['principal'],2); ?></td>
            <td><span class="badge bg-<?php echo $row['status']==='approved'?'success':($row['status']==='rejected'?'danger':'warning'); ?>"><?php echo strtoupper($row['status']); ?></span></td>
            <td>
              <?php if ($row['status']==='approved'): ?>
                <a href="index.php?page=emi_schedule&loan_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">EMIs</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <?php 
        $totalPages = (int)ceil(max(1, $total) / $perPage);
        $base = 'index.php?page=all_loans&q=' . urlencode($q) . '&p=';
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
</section>


