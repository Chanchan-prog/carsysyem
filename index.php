<?php 
session_start();
require_once __DIR__ . '/includes/auth.php';
require_login();
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    logout_user();
    header("Location: login.php");
    exit();
}
$pageTitle = "Dashboard - NiceAdmin";
  include "includes/header.php"; 
  include "includes/navbar.php"; 
  include "includes/sidebar.php"; 
?>

<main id="main" class="main">
<?php
$page = $_GET['page'] ?? 'dashboard';

switch ($page) {
    case 'loan_apply':
        include __DIR__ . '/module/loan_apply.php';
        break;
    case 'my_loans':
        include __DIR__ . '/module/my_loans.php';
        break;
    case 'emi_schedule':
        include __DIR__ . '/module/emi_schedule.php';
        break;
    case 'my_emis':
        include __DIR__ . '/module/my_emis.php';
        break;
    case 'loan_edit':
        include __DIR__ . '/module/loan_edit.php';
        break;
    case 'loan_delete':
        include __DIR__ . '/module/loan_delete.php';
        break;
    case 'admin_applications':
        include __DIR__ . '/module/admin_applications.php';
        break;
    case 'all_loans':
        include __DIR__ . '/module/all_loans.php';
        break;
    
    case 'users':
        include __DIR__ . '/module/users.php';
        break;
    case 'cars':
        include __DIR__ . '/module/cars.php';
        break;
    case 'student_form':
        include __DIR__ . '/module/register_student.php';
        break;
    case 'display_student_list':
        include __DIR__ . '/module/enrollment_form.php';
        break;
    case 'profile':
        include __DIR__ . '/module/profile.php';
        break;
    case 'appeal':
        include __DIR__ . '/module/appeals.php';
        break;
    case 'logout':   // ✅ Added logout
        session_unset();    // remove all session variables
        session_destroy();  // destroy session
        header("Location: login.php"); // redirect to login page
        exit();
    case 'dashboard':
    default:
        include __DIR__ . '/module/dashboard.php';
        break;
}
?>
</main>

<?php include "includes/footer.php"; ?>
