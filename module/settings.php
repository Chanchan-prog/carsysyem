<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/notification_helper.php';

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $settings = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'max_loan_amount' => (float)($_POST['max_loan_amount'] ?? 0),
            'min_down_payment_percentage' => (float)($_POST['min_down_payment_percentage'] ?? 0),
            'late_payment_fee' => (float)($_POST['late_payment_fee'] ?? 0),
            'notification_email' => trim($_POST['notification_email'] ?? ''),
            'maintenance_reminder_days' => (int)($_POST['maintenance_reminder_days'] ?? 30)
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            if (!set_system_setting($key, $value)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = 'Settings updated successfully.';
            log_audit('update', 'system_settings', null, null, json_encode($settings));
        } else {
            $message = 'Error updating settings.';
        }
    }
}

// Get current settings
$current_settings = [
    'company_name' => get_system_setting('company_name', 'Car Loan Management System'),
    'max_loan_amount' => get_system_setting('max_loan_amount', '5000000'),
    'min_down_payment_percentage' => get_system_setting('min_down_payment_percentage', '20'),
    'late_payment_fee' => get_system_setting('late_payment_fee', '500'),
    'notification_email' => get_system_setting('notification_email', 'admin@example.com'),
    'maintenance_reminder_days' => get_system_setting('maintenance_reminder_days', '30')
];
?>

<div class="pagetitle">
    <h1>System Settings</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Settings</li>
        </ol>
    </nav>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">System Configuration</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?page=settings">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($current_settings['company_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="max_loan_amount" class="form-label">Maximum Loan Amount</label>
                            <input type="number" step="0.01" class="form-control" id="max_loan_amount" name="max_loan_amount" 
                                   value="<?php echo $current_settings['max_loan_amount']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_down_payment_percentage" class="form-label">Minimum Down Payment Percentage</label>
                            <input type="number" step="0.01" class="form-control" id="min_down_payment_percentage" name="min_down_payment_percentage" 
                                   value="<?php echo $current_settings['min_down_payment_percentage']; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="late_payment_fee" class="form-label">Late Payment Fee</label>
                            <input type="number" step="0.01" class="form-control" id="late_payment_fee" name="late_payment_fee" 
                                   value="<?php echo $current_settings['late_payment_fee']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="notification_email" class="form-label">Notification Email</label>
                            <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                   value="<?php echo htmlspecialchars($current_settings['notification_email']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="maintenance_reminder_days" class="form-label">Maintenance Reminder Days</label>
                            <input type="number" class="form-control" id="maintenance_reminder_days" name="maintenance_reminder_days" 
                                   value="<?php echo $current_settings['maintenance_reminder_days']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Settings
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Database Information</h6>
                        <ul class="list-unstyled">
                            <li><strong>Database:</strong> <?php echo $dbname; ?></li>
                            <li><strong>Host:</strong> <?php echo $host; ?></li>
                            <li><strong>User:</strong> <?php echo $user; ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>PHP Information</h6>
                        <ul class="list-unstyled">
                            <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                            <li><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></li>
                            <li><strong>Upload Max:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
