<div class="pagetitle">
  <h1>Dashboard</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Home</a></li>
      <li class="breadcrumb-item active">Dashboard</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
      <div class="row">
        <?php 
          require_once __DIR__ . '/../includes/auth.php'; 
          require_once __DIR__ . '/../db/db.php';
          $u = current_user();

          // Helpers for formatted values
          $fmtInt = function ($n) { return number_format((int)$n); };
          $fmtMoney = function ($n) { return '₱' . number_format((float)$n, 2); };

          $isAdmin = ($u['role'] ?? '') === 'admin';

          // Defaults
          $card1_title = '';
          $card1_icon = 'bi-people';
          $card1_value = '0';

          $card2_title = '';
          $card2_icon = 'bi-currency-dollar';
          $card2_value = '0';

          $card3_title = '';
          $card3_icon = 'bi-people';
          $card3_value = '0';

          if ($isAdmin) {
            // Admin metrics
            $q1 = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='customer'");
            $customers = $q1 ? ((int)$q1->fetch_assoc()['c']) : 0;

            $q2 = $conn->query("SELECT COUNT(*) AS c FROM loans");
            $loansTotal = $q2 ? ((int)$q2->fetch_assoc()['c']) : 0;

            $q3 = $conn->query("SELECT COUNT(*) AS c FROM loans WHERE status='pending'");
            $pendingApps = $q3 ? ((int)$q3->fetch_assoc()['c']) : 0;

            $q4 = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM emis WHERE status='due'");
            $receivables = $q4 ? ((float)$q4->fetch_assoc()['s']) : 0.0;

            $card1_title = 'Customers';
            $card1_icon = 'bi-people';
            $card1_value = $fmtInt($customers);

            $card2_title = 'Total Loans';
            $card2_icon = 'bi-kanban';
            $card2_value = $fmtInt($loansTotal);

            $card3_title = 'Pending Approvals';
            $card3_icon = 'bi-hourglass-split';
            $card3_value = $fmtInt($pendingApps);
          } else {
            // Customer metrics
            $uid = (int)($u['id'] ?? 0);
            $stmt1 = $conn->prepare("SELECT COUNT(*) AS c FROM loans WHERE user_id=?");
            $stmt1->bind_param('i', $uid);
            $stmt1->execute();
            $loansTotal = (int)($stmt1->get_result()->fetch_assoc()['c'] ?? 0);

            $stmt2 = $conn->prepare("SELECT COUNT(*) AS c FROM loans WHERE user_id=? AND status='approved'");
            $stmt2->bind_param('i', $uid);
            $stmt2->execute();
            $approved = (int)($stmt2->get_result()->fetch_assoc()['c'] ?? 0);

            $stmt3 = $conn->prepare("SELECT COUNT(*) AS c FROM loans WHERE user_id=? AND status='pending'");
            $stmt3->bind_param('i', $uid);
            $stmt3->execute();
            $pending = (int)($stmt3->get_result()->fetch_assoc()['c'] ?? 0);

            $stmt4 = $conn->prepare("SELECT COALESCE(SUM(e.amount),0) AS s FROM emis e JOIN loans l ON l.id=e.loan_id WHERE l.user_id=? AND e.status='due'");
            $stmt4->bind_param('i', $uid);
            $stmt4->execute();
            $dueAmount = (float)($stmt4->get_result()->fetch_assoc()['s'] ?? 0);

            $card1_title = 'My Loans';
            $card1_icon = 'bi-kanban';
            $card1_value = $fmtInt($loansTotal);

            $card2_title = 'Approved Loans';
            $card2_icon = 'bi-check2-circle';
            $card2_value = $fmtInt($approved);

            $card3_title = 'Pending Loans';
            $card3_icon = 'bi-hourglass-split';
            $card3_value = $fmtInt($pending);
          }
        ?>
        <div class="col-12">
          <div class="alert alert-primary">Welcome, <?php echo htmlspecialchars($u['full_name'] ?? ''); ?>!</div>
          <?php if (($u['role'] ?? '')==='customer' && ((int)($_SESSION['blocked'] ?? 0)===1)): ?>
            <div class="alert alert-danger d-flex justify-content-between align-items-center">
              <div>Your account is currently blocked by admin. You cannot apply for loans.</div>
              <form method="post" action="index.php?page=appeal" class="mb-0">
                <button class="btn btn-sm btn-light">Appeal</button>
              </form>
            </div>
          <?php endif; ?>
        </div>

        <!-- Left side columns -->
        <div class="col-lg-8">
          <div class="row">

            <!-- Card 1 -->
            <div class="col-xxl-4 col-md-6">
  <div class="card info-card students-card">

    <div class="filter">
      <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
      <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
        <li class="dropdown-header text-start">
          <h6>Filter</h6>
        </li>
        <li><a class="dropdown-item" href="#">Today</a></li>
        <li><a class="dropdown-item" href="#">This Month</a></li>
        <li><a class="dropdown-item" href="#">This Year</a></li>
      </ul>
    </div>

    <div class="card-body">
      <h5 class="card-title"><?php echo htmlspecialchars($card1_title); ?> <span>| Summary</span></h5>

      <div class="d-flex align-items-center">
        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi <?php echo htmlspecialchars($card1_icon); ?>"></i>
        </div>
        <div class="ps-3">
          <h6><?php echo $card1_value; ?></h6>
        </div>
      </div>
    </div>

  </div>
</div><!-- End Students Card -->

            <!-- Card 2 -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card revenue-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title"><?php echo htmlspecialchars($card2_title); ?> <span>| Summary</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi <?php echo htmlspecialchars($card2_icon); ?>"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?php echo $card2_value; ?></h6>

                    </div>
                  </div>
                </div>

              </div>
            </div><!-- End Revenue Card -->

            <!-- Card 3 -->
            <div class="col-xxl-4 col-xl-12">

              <div class="card info-card customers-card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title"><?php echo htmlspecialchars($card3_title); ?> <span>| Summary</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi <?php echo htmlspecialchars($card3_icon); ?>"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?php echo $card3_value; ?></h6>

                    </div>
                  </div>

                </div>
              </div>

            </div><!-- End Customers Card -->

            <!-- Reports -->
            <div class="col-12">
              <div class="card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Today</a></li>
                    <li><a class="dropdown-item" href="#">This Month</a></li>
                    <li><a class="dropdown-item" href="#">This Year</a></li>
                  </ul>
                </div>

                
            <?php if ($isAdmin): ?>
            <div class="col-12">
              <div class="row">
                <div class="col-md-4">
                  <div class="card info-card revenue-card">
                    <div class="card-body">
                      <h5 class="card-title">Receivables <span>| Due EMIs</span></h5>
                      <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                          <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="ps-3">
                          <h6><?php echo $fmtMoney($receivables ?? 0); ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card info-card customers-card">
                    <div class="card-body">
                      <h5 class="card-title">Pending Applications</h5>
                      <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                          <i class="bi bi-hourglass"></i>
                        </div>
                        <div class="ps-3">
                          <h6><?php echo $fmtInt($pendingApps ?? 0); ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card info-card students-card">
                    <div class="card-body">
                      <h5 class="card-title">Active Customers</h5>
                      <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                          <i class="bi bi-people"></i>
                        </div>
                        <div class="ps-3">
                          <h6><?php echo $fmtInt($customers ?? 0); ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">Recent Loan Applications</h5>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Applicant</th>
                          <th>Car</th>
                          <th>Principal</th>
                          <th>Status</th>
                          <th>Applied At</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
                          $recent = $conn->query("SELECT l.id, u.full_name, u.username, c.model, l.principal, l.status, l.created_at FROM loans l JOIN users u ON u.id=l.user_id JOIN cars c ON c.id=l.car_id ORDER BY l.id DESC LIMIT 5");
                          if ($recent && $recent->num_rows > 0):
                            while ($r = $recent->fetch_assoc()):
                        ?>
                          <tr>
                            <td><?php echo (int)$r["id"]; ?></td>
                            <td><?php echo htmlspecialchars($r["full_name"] ?: $r["username"]); ?></td>
                            <td><?php echo htmlspecialchars($r["model"]); ?></td>
                            <td><?php echo $fmtMoney($r["principal"]); ?></td>
                            <td>
                              <?php 
                                $status = $r["status"]; 
                                $badge = 'secondary';
                                if ($status === 'approved') $badge = 'success';
                                elseif ($status === 'pending') $badge = 'warning';
                                elseif ($status === 'rejected') $badge = 'danger';
                              ?>
                              <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo htmlspecialchars($status); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($r["created_at"]); ?></td>
                          </tr>
                        <?php 
                            endwhile; 
                          else:
                        ?>
                          <tr><td colspan="6" class="text-center">No recent applications.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                  <div class="mt-3">
                    <a href="index.php?page=admin_applications" class="btn btn-primary btn-sm"><i class="bi bi-list-check"></i> Review Applications</a>
                    <a href="index.php?page=all_loans" class="btn btn-outline-secondary btn-sm"><i class="bi bi-collection"></i> View All Loans</a>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>
         </script>