<?php
require_once __DIR__ . '/../db/db.php';

/**
 * Send notification to a user
 */
function send_notification($user_id, $title, $message, $type = 'info') {
    global $conn;
    
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)');
    $stmt->bind_param('isss', $user_id, $title, $message, $type);
    return $stmt->execute();
}

/**
 * Send notification to all users
 */
function send_notification_to_all($title, $message, $type = 'info') {
    global $conn;
    
    $result = $conn->query('SELECT id FROM users');
    $success_count = 0;
    
    while ($user = $result->fetch_assoc()) {
        if (send_notification($user['id'], $title, $message, $type)) {
            $success_count++;
        }
    }
    
    return $success_count;
}

/**
 * Get unread notification count for a user
 */
function get_unread_notification_count($user_id) {
    global $conn;
    
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id=? AND is_read=0');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return (int)$result['count'];
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id) {
    global $conn;
    
    $stmt = $conn->prepare('UPDATE notifications SET is_read=1, read_at=NOW() WHERE id=?');
    $stmt->bind_param('i', $notification_id);
    return $stmt->execute();
}

/**
 * Get system setting
 */
function get_system_setting($key, $default = null) {
    global $conn;
    
    $stmt = $conn->prepare('SELECT setting_value FROM system_settings WHERE setting_key=?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result ? $result['setting_value'] : $default;
}

/**
 * Set system setting
 */
function set_system_setting($key, $value) {
    global $conn;
    
    $stmt = $conn->prepare('INSERT INTO system_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?');
    $stmt->bind_param('sss', $key, $value, $value);
    return $stmt->execute();
}

/**
 * Log audit trail
 */
function log_audit($action, $table_name, $record_id = null, $old_values = null, $new_values = null) {
    global $conn;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $conn->prepare('INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('ississs', $user_id, $action, $table_name, $record_id, $old_values, $new_values, $ip_address, $user_agent);
    return $stmt->execute();
}

/**
 * Check if insurance is expiring soon
 */
function check_expiring_insurance($days_ahead = 30) {
    global $conn;
    
    $result = $conn->query("
        SELECT ci.*, c.model, c.plate_no, u.full_name 
        FROM car_insurance ci 
        JOIN cars c ON c.id = ci.car_id 
        JOIN loans l ON l.car_id = c.id 
        JOIN users u ON u.id = l.user_id 
        WHERE ci.status = 'active' 
        AND ci.end_date <= DATE_ADD(CURDATE(), INTERVAL $days_ahead DAY)
        AND ci.end_date > CURDATE()
    ");
    
    $expiring = [];
    while ($row = $result->fetch_assoc()) {
        $expiring[] = $row;
    }
    
    return $expiring;
}

/**
 * Check if maintenance is due soon
 */
function check_due_maintenance($days_ahead = 30) {
    global $conn;
    
    $result = $conn->query("
        SELECT c.model, c.plate_no, u.full_name, mr.next_service_date 
        FROM cars c 
        JOIN loans l ON l.car_id = c.id 
        JOIN users u ON u.id = l.user_id 
        LEFT JOIN maintenance_records mr ON mr.car_id = c.id 
        WHERE mr.next_service_date <= DATE_ADD(CURDATE(), INTERVAL $days_ahead DAY)
        AND mr.next_service_date > CURDATE()
        ORDER BY mr.next_service_date ASC
    ");
    
    $due = [];
    while ($row = $result->fetch_assoc()) {
        $due[] = $row;
    }
    
    return $due;
}

/**
 * Send automatic notifications for expiring insurance and due maintenance
 */
function send_automatic_notifications() {
    // Check expiring insurance
    $expiring_insurance = check_expiring_insurance();
    foreach ($expiring_insurance as $insurance) {
        $title = "Insurance Expiring Soon";
        $message = "Your insurance for {$insurance['model']} ({$insurance['plate_no']}) expires on " . date('M d, Y', strtotime($insurance['end_date'])) . ". Please renew it soon.";
        send_notification($insurance['user_id'], $title, $message, 'warning');
    }
    
    // Check due maintenance
    $due_maintenance = check_due_maintenance();
    foreach ($due_maintenance as $maintenance) {
        $title = "Maintenance Due Soon";
        $message = "Your vehicle {$maintenance['model']} ({$maintenance['plate_no']}) is due for maintenance on " . date('M d, Y', strtotime($maintenance['next_service_date'])) . ".";
        send_notification($maintenance['user_id'], $title, $message, 'reminder');
    }
}
?>
