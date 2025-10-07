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
        $maintenance_type = $_POST['maintenance_type'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $service_provider = trim($_POST['service_provider'] ?? '');
        $cost = (float)($_POST['cost'] ?? 0);
        $maintenance_date = $_POST['maintenance_date'] ?? '';
        $next_service_date = $_POST['next_service_date'] ?? '';
        $mileage = (int)($_POST['mileage'] ?? 0);
        $status = $_POST['status'] ?? 'completed';
        
        if ($car_id && $maintenance_type && $description && $cost >= 0 && $maintenance_date) {
            $stmt = $conn->prepare('INSERT INTO maintenance_records (car_id, maintenance_type, description, service_provider, cost, maintenance_date, next_service_date, mileage, status) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('isssdssis', $car_id, $maintenance_type, $description, $service_provider, $cost, $maintenance_date, $next_service_date, $mileage, $status);
            if ($stmt->execute()) {
                $message = 'Maintenance record added successfully.';
                $action = 'list';
            } else {
                $message = 'Error adding maintenance record.';
            }
        } else {
            $message = 'Please fill all required fields.';
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $car_id = (int)($_POST['car_id'] ?? 0);
        $maintenance_type = $_POST['maintenance_type'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $service_provider = trim($_POST['service_provider'] ?? '');
        $cost = (float)($_POST['cost'] ?? 0);
        $maintenance_date = $_POST['maintenance_date'] ?? '';
        $next_service_date = $_POST['next_service_date'] ?? '';
        $mileage = (int)($_POST['mileage'] ?? 0);
        $status = $_POST['status'] ?? 'completed';
        
        if ($id && $car_id && $maintenance_type && $description && $cost >= 0 && $maintenance_date) {
            $stmt = $conn->prepare('UPDATE maintenance_records SET car_id=?, maintenance_type=?, description=?, service_provider=?, cost=?, maintenance_date=?, next_service_date=?, mileage=?, status=? WHERE id=?');
            $stmt->bind_param('isssdssisi', $car_id, $maintenance_type, $description, $service_provider, $cost, $maintenance_date, $next_service_date, $mileage, $status, $id);
            if ($stmt->execute()) {
                $message = 'Maintenance record updated successfully.';
                $action = 'list';
            } else {
                $message = 'Error updating maintenance record.';
            }
        } else {
            $message = 'Please fill all required fields.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('DELETE FROM maintenance_records WHERE id=?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'Maintenance record deleted successfully.';
            } else {
                $message = 'Error deleting maintenance record.';
            }
        }
        $action = 'list';
    }
}

// Get maintenance data for editing
$maintenance = null;
if ($action === 'edit' && $id) {
    $stmt = $conn->prepare('SELECT * FROM maintenance_records WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $maintenance = $stmt->get_result()->fetch_assoc();
    if (!$maintenance) {
        $message = 'Maintenance record not found.';
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
    <h1>Vehicle Maintenance Management</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Maintenance</li>
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
                    <h5 class="card-title mb-0">Maintenance Records</h5>
                    <a href="index.php?page=maintenance&action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Maintenance Record
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Car</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Service Provider</th>
                                    <th>Cost</th>
                                    <th>Date</th>
                                    <th>Next Service</th>
                                    <th>Mileage</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query('
                                    SELECT mr.*, c.model, c.plate_no 
                                    FROM maintenance_records mr 
                                    JOIN cars c ON c.id = mr.car_id 
                                    ORDER BY mr.maintenance_date DESC
                                ');
                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['model'] . ' (' . $row['plate_no'] . ')'); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $row['maintenance_type'])); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . (strlen($row['description']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['service_provider'] ?: 'N/A'); ?></td>
                                        <td>₱<?php echo number_format($row['cost'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['maintenance_date'])); ?></td>
                                        <td><?php echo $row['next_service_date'] ? date('M d, Y', strtotime($row['next_service_date'])) : 'N/A'; ?></td>
                                        <td><?php echo $row['mileage'] ? number_format($row['mileage']) : 'N/A'; ?></td>
                                        <td>
                                            <?php
                                            $status_class = 'secondary';
                                            if ($row['status'] === 'completed') $status_class = 'success';
                                            elseif ($row['status'] === 'pending') $status_class = 'warning';
                                            elseif ($row['status'] === 'cancelled') $status_class = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span>
                                        </td>
                                        <td>
                                            <a href="index.php?page=maintenance&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMaintenance(<?php echo $row['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="11" class="text-center">No maintenance records found.</td>
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
    function deleteMaintenance(id) {
        if (confirm('Are you sure you want to delete this maintenance record?')) {
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
                        <?php echo $action === 'create' ? 'Add New Maintenance Record' : 'Edit Maintenance Record'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?page=maintenance">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $maintenance['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="car_id" class="form-label">Car <span class="text-danger">*</span></label>
                                <select class="form-select" id="car_id" name="car_id" required>
                                    <option value="">Select Car</option>
                                    <?php foreach ($cars as $car): ?>
                                        <option value="<?php echo $car['id']; ?>" <?php echo ($maintenance && $maintenance['car_id'] == $car['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($car['model'] . ' (' . $car['plate_no'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                    <option value="">Select Type</option>
                                    <option value="routine" <?php echo ($maintenance && $maintenance['maintenance_type'] === 'routine') ? 'selected' : ''; ?>>Routine</option>
                                    <option value="repair" <?php echo ($maintenance && $maintenance['maintenance_type'] === 'repair') ? 'selected' : ''; ?>>Repair</option>
                                    <option value="inspection" <?php echo ($maintenance && $maintenance['maintenance_type'] === 'inspection') ? 'selected' : ''; ?>>Inspection</option>
                                    <option value="emergency" <?php echo ($maintenance && $maintenance['maintenance_type'] === 'emergency') ? 'selected' : ''; ?>>Emergency</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($maintenance['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="service_provider" class="form-label">Service Provider</label>
                                <input type="text" class="form-control" id="service_provider" name="service_provider" 
                                       value="<?php echo htmlspecialchars($maintenance['service_provider'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="cost" class="form-label">Cost <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="cost" name="cost" 
                                       value="<?php echo $maintenance['cost'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="maintenance_date" class="form-label">Maintenance Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="maintenance_date" name="maintenance_date" 
                                       value="<?php echo $maintenance['maintenance_date'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="next_service_date" class="form-label">Next Service Date</label>
                                <input type="date" class="form-control" id="next_service_date" name="next_service_date" 
                                       value="<?php echo $maintenance['next_service_date'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mileage" class="form-label">Mileage</label>
                                <input type="number" class="form-control" id="mileage" name="mileage" 
                                       value="<?php echo $maintenance['mileage'] ?? ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="completed" <?php echo ($maintenance && $maintenance['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo ($maintenance && $maintenance['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="cancelled" <?php echo ($maintenance && $maintenance['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> <?php echo $action === 'create' ? 'Add Maintenance' : 'Update Maintenance'; ?>
                            </button>
                            <a href="index.php?page=maintenance" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
