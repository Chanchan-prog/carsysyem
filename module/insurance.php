<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';

$message = '';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $car_id = (int)($_POST['car_id'] ?? 0);
        $insurance_company = trim($_POST['insurance_company'] ?? '');
        $policy_number = trim($_POST['policy_number'] ?? '');
        $coverage_type = $_POST['coverage_type'] ?? '';
        $premium_amount = (float)($_POST['premium_amount'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if ($car_id && $insurance_company && $policy_number && $coverage_type && $premium_amount > 0 && $start_date && $end_date) {
            $stmt = $conn->prepare('INSERT INTO car_insurance (car_id, insurance_company, policy_number, coverage_type, premium_amount, start_date, end_date) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('isssdss', $car_id, $insurance_company, $policy_number, $coverage_type, $premium_amount, $start_date, $end_date);
            if ($stmt->execute()) {
                $message = 'Insurance policy added successfully.';
                $action = 'list';
            } else {
                $message = 'Error adding insurance policy.';
            }
        } else {
            $message = 'Please fill all required fields.';
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $car_id = (int)($_POST['car_id'] ?? 0);
        $insurance_company = trim($_POST['insurance_company'] ?? '');
        $policy_number = trim($_POST['policy_number'] ?? '');
        $coverage_type = $_POST['coverage_type'] ?? '';
        $premium_amount = (float)($_POST['premium_amount'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $status = $_POST['status'] ?? 'active';
        
        if ($id && $car_id && $insurance_company && $policy_number && $coverage_type && $premium_amount > 0 && $start_date && $end_date) {
            $stmt = $conn->prepare('UPDATE car_insurance SET car_id=?, insurance_company=?, policy_number=?, coverage_type=?, premium_amount=?, start_date=?, end_date=?, status=? WHERE id=?');
            $stmt->bind_param('isssdsssi', $car_id, $insurance_company, $policy_number, $coverage_type, $premium_amount, $start_date, $end_date, $status, $id);
            if ($stmt->execute()) {
                $message = 'Insurance policy updated successfully.';
                $action = 'list';
            } else {
                $message = 'Error updating insurance policy.';
            }
        } else {
            $message = 'Please fill all required fields.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('DELETE FROM car_insurance WHERE id=?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'Insurance policy deleted successfully.';
            } else {
                $message = 'Error deleting insurance policy.';
            }
        }
        $action = 'list';
    }
}

// Get insurance data for editing
$insurance = null;
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare('SELECT * FROM car_insurance WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $insurance = $stmt->get_result()->fetch_assoc();
    if (!$insurance) {
        $message = 'Insurance policy not found.';
        $action = 'list';
    }
}

// Get cars list
$cars_result = $conn->query('SELECT id, model, plate_no FROM cars WHERE is_active=1 ORDER BY model');
$cars = [];
while ($car = $cars_result->fetch_assoc()) {
    $cars[] = $car;
}
?>

<div class="pagetitle">
    <h1>Car Insurance Management</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Insurance</li>
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
                    <h5 class="card-title mb-0">Insurance Policies</h5>
                    <a href="index.php?page=insurance&action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Insurance
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Car</th>
                                    <th>Insurance Company</th>
                                    <th>Policy Number</th>
                                    <th>Coverage Type</th>
                                    <th>Premium</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query('
                                    SELECT ci.*, c.model, c.plate_no 
                                    FROM car_insurance ci 
                                    JOIN cars c ON c.id = ci.car_id 
                                    ORDER BY ci.created_at DESC
                                ');
                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['model'] . ' (' . $row['plate_no'] . ')'); ?></td>
                                        <td><?php echo htmlspecialchars($row['insurance_company']); ?></td>
                                        <td><?php echo htmlspecialchars($row['policy_number']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst($row['coverage_type']); ?></span>
                                        </td>
                                        <td>₱<?php echo number_format($row['premium_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                                        <td>
                                            <?php
                                            $status_class = 'secondary';
                                            if ($row['status'] === 'active') $status_class = 'success';
                                            elseif ($row['status'] === 'expired') $status_class = 'danger';
                                            elseif ($row['status'] === 'cancelled') $status_class = 'warning';
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span>
                                        </td>
                                        <td>
                                            <a href="index.php?page=insurance&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteInsurance(<?php echo $row['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No insurance policies found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function deleteInsurance(id) {
        if (confirm('Are you sure you want to delete this insurance policy?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <?php echo $action === 'create' ? 'Add New Insurance Policy' : 'Edit Insurance Policy'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?page=insurance">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $insurance['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="car_id" class="form-label">Car <span class="text-danger">*</span></label>
                                <select class="form-select" id="car_id" name="car_id" required>
                                    <option value="">Select Car</option>
                                    <?php foreach ($cars as $car): ?>
                                        <option value="<?php echo $car['id']; ?>" <?php echo ($insurance && $insurance['car_id'] == $car['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($car['model'] . ' (' . $car['plate_no'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="insurance_company" class="form-label">Insurance Company <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="insurance_company" name="insurance_company" 
                                       value="<?php echo htmlspecialchars($insurance['insurance_company'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="policy_number" class="form-label">Policy Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="policy_number" name="policy_number" 
                                       value="<?php echo htmlspecialchars($insurance['policy_number'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="coverage_type" class="form-label">Coverage Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="coverage_type" name="coverage_type" required>
                                    <option value="">Select Coverage Type</option>
                                    <option value="comprehensive" <?php echo ($insurance && $insurance['coverage_type'] === 'comprehensive') ? 'selected' : ''; ?>>Comprehensive</option>
                                    <option value="third_party" <?php echo ($insurance && $insurance['coverage_type'] === 'third_party') ? 'selected' : ''; ?>>Third Party</option>
                                    <option value="collision" <?php echo ($insurance && $insurance['coverage_type'] === 'collision') ? 'selected' : ''; ?>>Collision</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="premium_amount" class="form-label">Premium Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="premium_amount" name="premium_amount" 
                                       value="<?php echo $insurance['premium_amount'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $insurance['start_date'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $insurance['end_date'] ?? ''; ?>" required>
                            </div>
                            
                            <?php if ($action === 'edit'): ?>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo ($insurance && $insurance['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="expired" <?php echo ($insurance && $insurance['status'] === 'expired') ? 'selected' : ''; ?>>Expired</option>
                                    <option value="cancelled" <?php echo ($insurance && $insurance['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> <?php echo $action === 'create' ? 'Add Insurance' : 'Update Insurance'; ?>
                            </button>
                            <a href="index.php?page=insurance" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
