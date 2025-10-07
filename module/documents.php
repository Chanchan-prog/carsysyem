<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';

$message = '';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Create uploads directory if it doesn't exist
$upload_dir = __DIR__ . '/../uploads/documents/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload') {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $loan_id = (int)($_POST['loan_id'] ?? 0) ?: null;
        $document_type = $_POST['document_type'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        if ($user_id && $document_type && isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['document_file'];
            $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_extension, $allowed_types)) {
                $filename = uniqid() . '_' . $file['name'];
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $stmt = $conn->prepare('INSERT INTO documents (user_id, loan_id, document_type, document_name, file_path, file_size, mime_type) VALUES (?,?,?,?,?,?,?)');
                    $stmt->bind_param('iisssis', $user_id, $loan_id, $document_type, $file['name'], $filename, $file['size'], $file['type']);
                    if ($stmt->execute()) {
                        $message = 'Document uploaded successfully.';
                        $action = 'list';
                    } else {
                        $message = 'Error saving document information.';
                        unlink($file_path); // Remove uploaded file if database insert fails
                    }
                } else {
                    $message = 'Error uploading file.';
                }
            } else {
                $message = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types);
            }
        } else {
            $message = 'Please select a file and fill all required fields.';
        }
    } elseif ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        
        if ($id && $status) {
            $stmt = $conn->prepare('UPDATE documents SET status=?, admin_notes=? WHERE id=?');
            $stmt->bind_param('ssi', $status, $admin_notes, $id);
            if ($stmt->execute()) {
                $message = 'Document status updated successfully.';
            } else {
                $message = 'Error updating document status.';
            }
        }
        $action = 'list';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Get file path before deleting
            $stmt = $conn->prepare('SELECT file_path FROM documents WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result) {
                $file_path = $upload_dir . $result['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            $stmt = $conn->prepare('DELETE FROM documents WHERE id=?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'Document deleted successfully.';
            } else {
                $message = 'Error deleting document.';
            }
        }
        $action = 'list';
    }
}

// Get current user's documents
$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

if ($is_admin) {
    $result = $conn->query('
        SELECT d.*, u.full_name, u.username, l.id as loan_id 
        FROM documents d 
        JOIN users u ON u.id = d.user_id 
        LEFT JOIN loans l ON l.id = d.loan_id 
        ORDER BY d.uploaded_at DESC
    ');
} else {
    $stmt = $conn->prepare('
        SELECT d.*, l.id as loan_id 
        FROM documents d 
        LEFT JOIN loans l ON l.id = d.loan_id 
        WHERE d.user_id = ? 
        ORDER BY d.uploaded_at DESC
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$documents = [];
while ($doc = $result->fetch_assoc()) {
    $documents[] = $doc;
}

// Get user's loans for document upload
$user_loans = [];
if ($user_id) {
    $loans_result = $conn->query("SELECT id, car_id FROM loans WHERE user_id = $user_id ORDER BY created_at DESC");
    while ($loan = $loans_result->fetch_assoc()) {
        $user_loans[] = $loan;
    }
}
?>

<div class="pagetitle">
    <h1>Document Management</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Documents</li>
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
                    <h5 class="card-title mb-0"><?php echo $is_admin ? 'All Documents' : 'My Documents'; ?></h5>
                    <a href="index.php?page=documents&action=upload" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload Document
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <?php if ($is_admin): ?>
                                        <th>User</th>
                                    <?php endif; ?>
                                    <th>Document Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Uploaded</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                    <tr>
                                        <td colspan="<?php echo $is_admin ? '7' : '6'; ?>" class="text-center">No documents found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <?php if ($is_admin): ?>
                                                <td><?php echo htmlspecialchars($doc['full_name'] . ' (' . $doc['username'] . ')'); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></span>
                                            </td>
                                            <td><?php echo number_format($doc['file_size'] / 1024, 1); ?> KB</td>
                                            <td><?php echo date('M d, Y H:i', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                if ($doc['status'] === 'approved') $status_class = 'success';
                                                elseif ($doc['status'] === 'rejected') $status_class = 'danger';
                                                elseif ($doc['status'] === 'pending') $status_class = 'warning';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($doc['status']); ?></span>
                                            </td>
                                            <td>
                                                <a href="uploads/documents/<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-primary" title="View Document">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($is_admin): ?>
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            onclick="updateStatus(<?php echo $doc['id']; ?>, 'approved')" title="Approve">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="updateStatus(<?php echo $doc['id']; ?>, 'rejected')" title="Reject">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteDocument(<?php echo $doc['id']; ?>)" title="Delete">
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
    function updateStatus(id, status) {
        const notes = prompt('Enter admin notes (optional):');
        if (notes !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="status" value="${status}">
                <input type="hidden" name="admin_notes" value="${notes}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function deleteDocument(id) {
        if (confirm('Are you sure you want to delete this document?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>

<?php elseif ($action === 'upload'): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upload Document</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?page=documents" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="mb-3">
                            <label for="loan_id" class="form-label">Related Loan (Optional)</label>
                            <select class="form-select" id="loan_id" name="loan_id">
                                <option value="">Select Loan</option>
                                <?php foreach ($user_loans as $loan): ?>
                                    <option value="<?php echo $loan['id']; ?>">
                                        Loan #<?php echo $loan['id']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="document_type" class="form-label">Document Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="document_type" name="document_type" required>
                                <option value="">Select Type</option>
                                <option value="identity">Identity Document</option>
                                <option value="income">Income Proof</option>
                                <option value="employment">Employment Certificate</option>
                                <option value="vehicle">Vehicle Documents</option>
                                <option value="insurance">Insurance Documents</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="document_file" class="form-label">Document File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="document_file" name="document_file" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <div class="form-text">Allowed formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload Document
                            </button>
                            <a href="index.php?page=documents" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
