<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';

$message = '';
$action = $_GET['action'] ?? 'list';
$report_type = $_GET['type'] ?? '';

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $generated_by = (int)($_SESSION['user_id'] ?? 0);
    
    if ($report_type && $title && $generated_by) {
        $report_data = [];
        
        switch ($report_type) {
            case 'loan_summary':
                $result = $conn->query('
                    SELECT 
                        COUNT(*) as total_loans,
                        SUM(principal) as total_principal,
                        AVG(principal) as avg_principal,
                        COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_loans,
                        COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_loans,
                        COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected_loans
                    FROM loans
                ');
                $report_data = $result->fetch_assoc();
                break;
                
            case 'payment_history':
                $result = $conn->query('
                    SELECT 
                        DATE(created_at) as payment_date,
                        COUNT(*) as payment_count,
                        SUM(amount) as total_amount
                    FROM payments 
                    GROUP BY DATE(created_at) 
                    ORDER BY payment_date DESC 
                    LIMIT 30
                ');
                $report_data = [];
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
                break;
                
            case 'customer_analysis':
                $result = $conn->query('
                    SELECT 
                        u.full_name,
                        u.username,
                        COUNT(l.id) as loan_count,
                        SUM(l.principal) as total_borrowed,
                        COUNT(CASE WHEN l.status = "approved" THEN 1 END) as approved_count
                    FROM users u 
                    LEFT JOIN loans l ON l.user_id = u.id 
                    WHERE u.role = "customer"
                    GROUP BY u.id 
                    ORDER BY total_borrowed DESC
                ');
                $report_data = [];
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
                break;
                
            case 'financial_summary':
                $result = $conn->query('
                    SELECT 
                        (SELECT SUM(principal) FROM loans WHERE status = "approved") as total_loans_outstanding,
                        (SELECT SUM(amount) FROM payments) as total_payments_received,
                        (SELECT SUM(amount) FROM emis WHERE status = "due") as total_due_amount,
                        (SELECT COUNT(*) FROM emis WHERE status = "paid") as total_paid_emis,
                        (SELECT COUNT(*) FROM emis WHERE status = "due") as total_due_emis
                ');
                $report_data = $result->fetch_assoc();
                break;
                
            case 'maintenance_summary':
                $result = $conn->query('
                    SELECT 
                        c.model,
                        c.plate_no,
                        COUNT(mr.id) as maintenance_count,
                        SUM(mr.cost) as total_maintenance_cost,
                        AVG(mr.cost) as avg_maintenance_cost
                    FROM cars c 
                    LEFT JOIN maintenance_records mr ON mr.car_id = c.id 
                    GROUP BY c.id 
                    ORDER BY total_maintenance_cost DESC
                ');
                $report_data = [];
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
                break;
        }
        
        $stmt = $conn->prepare('INSERT INTO reports (report_type, title, description, generated_by, report_data) VALUES (?,?,?,?,?)');
        $json_data = json_encode($report_data);
        $stmt->bind_param('sssis', $report_type, $title, $description, $generated_by, $json_data);
        
        if ($stmt->execute()) {
            $message = 'Report generated successfully.';
            $action = 'list';
        } else {
            $message = 'Error generating report.';
        }
    } else {
        $message = 'Please fill all required fields.';
    }
}

// Get reports list
$reports = [];
$result = $conn->query('
    SELECT r.*, u.full_name as generated_by_name 
    FROM reports r 
    JOIN users u ON u.id = r.generated_by 
    ORDER BY r.generated_at DESC
');
while ($report = $result->fetch_assoc()) {
    $reports[] = $report;
}
?>

<div class="pagetitle">
    <h1>Reports & Analytics</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Reports</li>
        </ol>
    </nav>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Generated Reports</h5>
                    <a href="index.php?page=reports&action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Generate Report
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Generated By</th>
                                    <th>Generated At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No reports found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo $report['id']; ?></td>
                                            <td><?php echo htmlspecialchars($report['title']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($report['generated_by_name']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($report['generated_at'])); ?></td>
                                            <td>
                                                <a href="index.php?page=reports&action=view&id=<?php echo $report['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View Report">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteReport(<?php echo $report['id']; ?>)" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function deleteReport(id) {
        if (confirm('Are you sure you want to delete this report?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>

<?php elseif ($action === 'create'): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Generate New Report</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?page=reports">
                        <div class="mb-3">
                            <label for="report_type" class="form-label">Report Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="report_type" name="report_type" required>
                                <option value="">Select Report Type</option>
                                <option value="loan_summary">Loan Summary</option>
                                <option value="payment_history">Payment History</option>
                                <option value="customer_analysis">Customer Analysis</option>
                                <option value="financial_summary">Financial Summary</option>
                                <option value="maintenance_summary">Maintenance Summary</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Report Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="generate_report" class="btn btn-primary">
                                <i class="bi bi-file-earmark-text"></i> Generate Report
                            </button>
                            <a href="index.php?page=reports" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'view' && isset($_GET['id'])): ?>
    <?php
    $report_id = (int)$_GET['id'];
    $stmt = $conn->prepare('SELECT * FROM reports WHERE id=?');
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    
    if (!$report) {
        $message = 'Report not found.';
        $action = 'list';
    } else {
        $report_data = json_decode($report['report_data'], true);
    }
    ?>
    
    <?php if ($report): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($report['title']); ?></h5>
                        <a href="index.php?page=reports" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Reports
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($report['description']): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($report['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <?php if ($report['report_type'] === 'loan_summary'): ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Metric</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Total Loans</td><td><?php echo number_format($report_data['total_loans']); ?></td></tr>
                                        <tr><td>Total Principal</td><td>₱<?php echo number_format($report_data['total_principal'], 2); ?></td></tr>
                                        <tr><td>Average Principal</td><td>₱<?php echo number_format($report_data['avg_principal'], 2); ?></td></tr>
                                        <tr><td>Approved Loans</td><td><?php echo number_format($report_data['approved_loans']); ?></td></tr>
                                        <tr><td>Pending Loans</td><td><?php echo number_format($report_data['pending_loans']); ?></td></tr>
                                        <tr><td>Rejected Loans</td><td><?php echo number_format($report_data['rejected_loans']); ?></td></tr>
                                    </tbody>
                                </table>
                            <?php elseif ($report['report_type'] === 'payment_history'): ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Payment Date</th>
                                            <th>Payment Count</th>
                                            <th>Total Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $payment): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo number_format($payment['payment_count']); ?></td>
                                                <td>₱<?php echo number_format($payment['total_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif ($report['report_type'] === 'customer_analysis'): ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Loan Count</th>
                                            <th>Total Borrowed</th>
                                            <th>Approved Loans</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $customer): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                                <td><?php echo number_format($customer['loan_count']); ?></td>
                                                <td>₱<?php echo number_format($customer['total_borrowed'], 2); ?></td>
                                                <td><?php echo number_format($customer['approved_count']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif ($report['report_type'] === 'financial_summary'): ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Metric</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Total Loans Outstanding</td><td>₱<?php echo number_format($report_data['total_loans_outstanding'], 2); ?></td></tr>
                                        <tr><td>Total Payments Received</td><td>₱<?php echo number_format($report_data['total_payments_received'], 2); ?></td></tr>
                                        <tr><td>Total Due Amount</td><td>₱<?php echo number_format($report_data['total_due_amount'], 2); ?></td></tr>
                                        <tr><td>Total Paid EMIs</td><td><?php echo number_format($report_data['total_paid_emis']); ?></td></tr>
                                        <tr><td>Total Due EMIs</td><td><?php echo number_format($report_data['total_due_emis']); ?></td></tr>
                                    </tbody>
                                </table>
                            <?php elseif ($report['report_type'] === 'maintenance_summary'): ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Vehicle</th>
                                            <th>Maintenance Count</th>
                                            <th>Total Cost</th>
                                            <th>Average Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $vehicle): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vehicle['model'] . ' (' . $vehicle['plate_no'] . ')'); ?></td>
                                                <td><?php echo number_format($vehicle['maintenance_count']); ?></td>
                                                <td>₱<?php echo number_format($vehicle['total_maintenance_cost'], 2); ?></td>
                                                <td>₱<?php echo number_format($vehicle['avg_maintenance_cost'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
