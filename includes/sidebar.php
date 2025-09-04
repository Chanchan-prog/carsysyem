<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Dashboard -->
    <li class="nav-item">
  <a class="nav-link <?php echo ($_GET['page'] ?? '') === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
    <i class="bi bi-grid"></i>
    <span>Dashboard</span>
  </a>
</li>

    <?php $role = $_SESSION['role'] ?? 'customer'; ?>
    <?php if ($role === 'customer'): ?>
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#loan-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-credit-card"></i><span>My Loans</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="loan-nav"
            class="nav-content collapse <?php echo in_array($_GET['page'] ?? '', ['loan_apply','my_loans','emi_schedule','my_emis']) ? 'show' : ''; ?>"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="index.php?page=loan_apply" class="<?php echo ($_GET['page'] ?? '') === 'loan_apply' ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>Apply for Loan</span>
            </a>
          </li>
          <li>
            <a href="index.php?page=my_loans" class="<?php echo ($_GET['page'] ?? '') === 'my_loans' ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>My Applications</span>
            </a>
          </li>
          <li>
            <a href="index.php?page=my_emis" class="<?php echo ($_GET['page'] ?? '') === 'my_emis' ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>My Payments</span>
            </a>
          </li>
        </ul>
      </li>
      <?php if ((int)($_SESSION['blocked'] ?? 0)===1): ?>
      <li class="nav-item">
        <a class="nav-link <?php echo ($_GET['page'] ?? '') === 'appeal' ? 'active' : ''; ?>" href="index.php?page=appeal">
          <i class="bi bi-shield-exclamation"></i>
          <span>Appeal</span>
        </a>
      </li>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#admin-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-shield-lock"></i><span>Admin</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="admin-nav"
            class="nav-content collapse <?php echo in_array($_GET['page'] ?? '', ['admin_applications','all_loans','users','cars','appeal']) ? 'show' : ''; ?>"
            data-bs-parent="#sidebar-nav">
          <li>
            <a href="index.php?page=admin_applications" class="<?php echo ($_GET['page'] ?? '') === 'admin_applications' ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>Loan Applications</span>
            </a>
          </li>
          <li>
            <a href="index.php?page=all_loans" class="<?php echo ($_GET['page'] ?? '') === 'all_loans' ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>All Loans & EMIs</span>
            </a>
          </li>
          <li>
            <a href="index.php?page=cars" class="<?php echo (($_GET['page'] ?? '') === 'cars' && (($_GET['archived'] ?? '')!=='1')) ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>Cars</span>
            </a>
          </li>
          <li>
            <a href="index.php?page=cars&archived=1" class="<?php echo (($_GET['page'] ?? '') === 'cars' && (($_GET['archived'] ?? '')==='1')) ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>Archived Cars</span>
            </a>
          </li>
          <li>
            <a href="index.php?page=users" class="<?php echo ($_GET['page'] ?? '') === 'users' ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>Users</span>
            </a>
          </li>
          <li>
            <a href="index.php?page=appeal" class="<?php echo ($_GET['page'] ?? '') === 'appeal' ? 'active' : ''; ?>">
              <i class="bi bi-circle"></i><span>Appeals</span>
            </a>
          </li>
          
        </ul>
      </li>
    <?php endif; ?>

  </ul>
</aside><!-- End Sidebar-->


