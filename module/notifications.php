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
        $user_id = (int)($_POST['user_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $message_text = trim($_POST['message'] ?? '');
        $type = $_POST['type'] ?? 'info';
        
        if ($user_id && $title && $message_text) {
            $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)');
            $stmt->bind_param('isss', $user_id, $title, $message_text, $type);
            if ($stmt->execute()) {
                $message = 'Notification sent successfully.';
                $action = 'list';
            } else {
                $message = 'Error sending notification.';
            }
        } else {
            $message = 'Please fill all required fields.';
        }
    } elseif ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('UPDATE notifications SET is_read=1, read_at=NOW() WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $action = 'list';
    } elseif ($action === 'mark_all_read') {
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        if ($user_id) {
            $stmt = $conn->prepare('UPDATE notifications SET is_read=1, read_at=NOW() WHERE user_id=? AND is_read=0');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
        }
        $action = 'list';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('DELETE FROM notifications WHERE id=?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'Notification deleted successfully.';
            } else {
                $message = 'Error deleting notification.';
            }
        }
        $action = 'list';
    }
}

// Get users list for admin
$users = [];
if (($_SESSION['role'] ?? '') === 'admin') {
    $users_result = $conn->query('SELECT id, username, full_name FROM users ORDER BY full_name');
    while ($user = $users_result->fetch_assoc()) {
        $users[] = $user;
    }
}

// Get current user's notifications
$user_id = (int)($_SESSION['user_id'] ?? 0);
$notifications = [];
if ($user_id) {
    $result = $conn->query("
        SELECT * FROM notifications 
        WHERE user_id = $user_id 
        ORDER BY created_at DESC
    ");
    while ($notification = $result->fetch_assoc()) {
        $notifications[] = $notification;
    }
}
?>

<div class="pagetitle">
    <h1>Notifications</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Notifications</li>
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
                    <h5 class="card-title mb-0">My Notifications</h5>
                    <div class="d-flex gap-2">
                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="index.php?page=notifications&action=create" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Send Notification
                            </a>
                        <?php endif; ?>
                        <form method="POST" action="index.php?page=notifications" class="d-inline">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-all"></i> Mark All Read
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-bell-slash display-4 text-muted"></i>
                            <p class="text-muted mt-2">No notifications found.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($notification['title']); ?>
                                                </h6>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary">New</span>
                                                <?php endif; ?>
                                                <span class="badge bg-<?php 
                                                    echo match($notification['type']) {
                                                        'info' => 'info',
                                                        'warning' => 'warning',
                                                        'success' => 'success',
                                                        'error' => 'danger',
                                                        'reminder' => 'secondary',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($notification['type']); ?>
                                                </span>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                                <?php if ($notification['read_at']): ?>
                                                    | Read: <?php echo date('M d, Y H:i', strtotime($notification['read_at'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <?php if (!$notification['is_read']): ?>
                                                <form method="POST" action="index.php?page=notifications" class="d-inline">
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as Read">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification(<?php echo $notification['id']; ?>)" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function deleteNotification(id) {
        if (confirm('Are you sure you want to delete this notification?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>

<?php elseif ($action === 'create' && ($_SESSION['role'] ?? '') === 'admin'): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Send Notification</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?page=notifications">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Recipient <span class="text-danger">*</span></label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Select User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="success">Success</option>
                                <option value="error">Error</option>
                                <option value="reminder">Reminder</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Send Notification
                            </button>
                            <a href="index.php?page=notifications" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
