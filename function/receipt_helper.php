<?php
require_once __DIR__ . '/../db/db.php';

/**
 * Generate receipt number
 */
function generate_receipt_number() {
    $prefix = 'RCP';
    $date = date('Ymd');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $date . $random;
}

/**
 * Generate payment receipt
 */
function generate_payment_receipt($payment_id, $user_id) {
    global $conn;
    
    // Get payment details
    $stmt = $conn->prepare('
        SELECT p.*, e.installment_no, e.due_date, e.amount as emi_amount,
               l.principal, l.annual_interest_rate, l.term_months,
               c.model, c.plate_no,
               u.full_name, u.username
        FROM payments p
        JOIN emis e ON e.id = p.emi_id
        JOIN loans l ON l.id = e.loan_id
        JOIN cars c ON c.id = l.car_id
        JOIN users u ON u.id = l.user_id
        WHERE p.id = ? AND l.user_id = ?
    ');
    $stmt->bind_param('ii', $payment_id, $user_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        return false;
    }
    
    // Generate receipt number
    $receipt_number = generate_receipt_number();
    
    // Create receipt record
    $stmt = $conn->prepare('INSERT INTO receipts (payment_id, user_id, receipt_number, amount, payment_date, payment_method) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('iissss', $payment_id, $user_id, $receipt_number, $payment['amount'], $payment['created_at'], $payment['method']);
    $stmt->execute();
    $receipt_id = $conn->insert_id;
    
    // Generate PDF receipt
    $file_path = generate_receipt_pdf($receipt_id, $payment, $receipt_number);
    
    // Update receipt with file path
    if ($file_path) {
        $stmt = $conn->prepare('UPDATE receipts SET file_path = ? WHERE id = ?');
        $stmt->bind_param('si', $file_path, $receipt_id);
        $stmt->execute();
    }
    
    return $receipt_id;
}

/**
 * Generate PDF receipt
 */
function generate_receipt_pdf($receipt_id, $payment_data, $receipt_number) {
    // Create receipts directory if it doesn't exist
    $receipts_dir = __DIR__ . '/../uploads/receipts/';
    if (!is_dir($receipts_dir)) {
        mkdir($receipts_dir, 0755, true);
    }
    
    $filename = 'receipt_' . $receipt_id . '_' . $receipt_number . '.html';
    $file_path = $receipts_dir . $filename;
    
    // Generate HTML content for receipt
    $html = generate_receipt_html($payment_data, $receipt_number);
    
    // Save HTML file (can be converted to PDF using browser print function)
    file_put_contents($file_path, $html);
    
    return $filename;
}

/**
 * Generate HTML receipt content
 */
function generate_receipt_html($payment_data, $receipt_number) {
    require_once __DIR__ . '/pdf_generator.php';
    return generate_receipt_pdf_advanced($payment_data, $receipt_number);
}

/**
 * Get receipt by ID
 */
function get_receipt($receipt_id) {
    global $conn;
    
    $stmt = $conn->prepare('
        SELECT r.*, p.amount, p.created_at as payment_date, p.method as payment_method,
               e.installment_no, e.due_date, e.amount as emi_amount,
               l.principal, l.annual_interest_rate, l.term_months,
               c.model, c.plate_no,
               u.full_name, u.username
        FROM receipts r
        JOIN payments p ON p.id = r.payment_id
        JOIN emis e ON e.id = p.emi_id
        JOIN loans l ON l.id = e.loan_id
        JOIN cars c ON c.id = l.car_id
        JOIN users u ON u.id = l.user_id
        WHERE r.id = ?
    ');
    $stmt->bind_param('i', $receipt_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get user's receipts
 */
function get_user_receipts($user_id, $limit = 50) {
    global $conn;
    
    $stmt = $conn->prepare('
        SELECT r.*, p.amount, p.created_at as payment_date, p.method as payment_method,
               e.installment_no, e.due_date,
               c.model, c.plate_no
        FROM receipts r
        JOIN payments p ON p.id = r.payment_id
        JOIN emis e ON e.id = p.emi_id
        JOIN loans l ON l.id = e.loan_id
        JOIN cars c ON c.id = l.car_id
        WHERE r.user_id = ? AND r.status = "active"
        ORDER BY r.generated_at DESC
        LIMIT ?
    ');
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    
    $receipts = [];
    $result = $stmt->get_result();
    while ($receipt = $result->fetch_assoc()) {
        $receipts[] = $receipt;
    }
    
    return $receipts;
}

/**
 * Download receipt file
 */
function download_receipt($receipt_id) {
    global $conn;
    
    $stmt = $conn->prepare('SELECT file_path FROM receipts WHERE id = ?');
    $stmt->bind_param('i', $receipt_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || !$result['file_path']) {
        return false;
    }
    
    $file_path = __DIR__ . '/../uploads/receipts/' . $result['file_path'];
    
    if (!file_exists($file_path)) {
        return false;
    }
    
    return $file_path;
}
?>
