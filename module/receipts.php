<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/receipt_helper.php';

$message = '';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_receipt') {
        $payment_id = (int)($_POST['payment_id'] ?? 0);
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        
        if ($payment_id && $user_id) {
            $receipt_id = generate_payment_receipt($payment_id, $user_id);
            if ($receipt_id) {
                $message = 'Receipt generated successfully.';
                $action = 'list';
            } else {
                $message = 'Error generating receipt.';
            }
        } else {
            $message = 'Invalid payment ID.';
        }
    }
}

// Handle file downloads
if ($action === 'download' && $id) {
    $file_path = download_receipt($id);
    if ($file_path) {
        $receipt = get_receipt($id);
        if ($receipt) {
            $filename = 'receipt_' . $receipt['receipt_number'] . '.html';
            header('Content-Type: text/html');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            readfile($file_path);
            exit();
        }
    }
    $message = 'Receipt file not found.';
    $action = 'list';
}

// Get current user's receipts
$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

if ($is_admin) {
    $result = $conn->query('
        SELECT r.*, p.amount, p.created_at as payment_date, p.method as payment_method,
               e.installment_no, e.due_date,
               c.model, c.plate_no,
               u.full_name, u.username
        FROM receipts r
        JOIN payments p ON p.id = r.payment_id
        JOIN emis e ON e.id = p.emi_id
        JOIN loans l ON l.id = e.loan_id
        JOIN cars c ON c.id = l.car_id
        JOIN users u ON u.id = l.user_id
        ORDER BY r.generated_at DESC
    ');
} else {
    $result = $conn->query("
        SELECT r.*, p.amount, p.created_at as payment_date, p.method as payment_method,
               e.installment_no, e.due_date,
               c.model, c.plate_no
        FROM receipts r
        JOIN payments p ON p.id = r.payment_id
        JOIN emis e ON e.id = p.emi_id
        JOIN loans l ON l.id = e.loan_id
        JOIN cars c ON c.id = l.car_id
        WHERE r.user_id = $user_id
        ORDER BY r.generated_at DESC
    ");
}

$receipts = [];
while ($receipt = $result->fetch_assoc()) {
    $receipts[] = $receipt;
}

// Get payments without receipts for current user
$payments_without_receipts = [];
if ($user_id) {
    $result = $conn->query("
        SELECT p.*, e.installment_no, e.due_date, e.amount as emi_amount,
               c.model, c.plate_no
        FROM payments p
        JOIN emis e ON e.id = p.emi_id
        JOIN loans l ON l.id = e.loan_id
        JOIN cars c ON c.id = l.car_id
        WHERE l.user_id = $user_id
        AND p.id NOT IN (SELECT payment_id FROM receipts WHERE status = 'active')
        ORDER BY p.created_at DESC
    ");
    
    while ($payment = $result->fetch_assoc()) {
        $payments_without_receipts[] = $payment;
    }
}
?>

<div class="pagetitle">
    <h1>Payment Receipts</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Receipts</li>
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
                    <h5 class="card-title mb-0"><?php echo $is_admin ? 'All Receipts' : 'My Receipts'; ?></h5>
                    <?php if (!empty($payments_without_receipts)): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateReceiptModal">
                            <i class="bi bi-plus-circle"></i> Generate Receipt
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($receipts)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-receipt display-4 text-muted"></i>
                            <p class="text-muted mt-2">No receipts found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <?php if ($is_admin): ?>
                                            <th>Customer</th>
                                        <?php endif; ?>
                                        <th>Receipt Number</th>
                                        <th>Vehicle</th>
                                        <th>Installment</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Generated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($receipts as $receipt): ?>
                                        <tr>
                                            <?php if ($is_admin): ?>
                                                <td><?php echo htmlspecialchars($receipt['full_name'] . ' (' . $receipt['username'] . ')'); ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($receipt['receipt_number']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($receipt['model'] . ' (' . $receipt['plate_no'] . ')'); ?></td>
                                            <td>#<?php echo $receipt['installment_no']; ?></td>
                                            <td>₱<?php echo number_format($receipt['amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($receipt['payment_date'])); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($receipt['generated_at'])); ?></td>
                                            <td>
                                                <a href="index.php?page=receipts&action=view&id=<?php echo $receipt['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View Receipt">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="index.php?page=receipts&action=download&id=<?php echo $receipt['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success" title="Download Receipt">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Receipt Modal -->
    <?php if (!empty($payments_without_receipts)): ?>
        <div class="modal fade" id="generateReceiptModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Generate Receipt</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Select a payment to generate a receipt for:</p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Vehicle</th>
                                        <th>Installment</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments_without_receipts as $payment): ?>
                                        <tr>
                                            <td>#<?php echo $payment['id']; ?></td>
                                            <td><?php echo htmlspecialchars($payment['model'] . ' (' . $payment['plate_no'] . ')'); ?></td>
                                            <td>#<?php echo $payment['installment_no']; ?></td>
                                            <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" action="index.php?page=receipts" class="d-inline">
                                                    <input type="hidden" name="action" value="generate_receipt">
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-plus-circle"></i> Generate
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php elseif ($action === 'view' && $id): ?>
    <?php
    $receipt = get_receipt($id);
    if (!$receipt) {
        $message = 'Receipt not found.';
        $action = 'list';
    }
    ?>
    
    <?php if ($receipt): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Receipt #<?php echo htmlspecialchars($receipt['receipt_number']); ?></h5>
                        <div class="d-flex gap-2">
                            <a href="index.php?page=receipts&action=download&id=<?php echo $receipt['id']; ?>" 
                               class="btn btn-success">
                                <i class="bi bi-download"></i> Download
                            </a>
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <a href="index.php?page=receipts" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $file_path = __DIR__ . '/../uploads/receipts/' . $receipt['file_path'];
                        if (file_exists($file_path)) {
                            echo file_get_contents($file_path);
                        } else {
                            echo '<div class="alert alert-warning">Receipt file not found.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
