<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';

header('Content-Type: application/json');

$emiId = (int)($_GET['emi_id'] ?? 0);
if (!$emiId) {
    echo json_encode(['success' => false, 'message' => 'Invalid EMI ID']);
    exit();
}

$u = current_user();
if (!$u['id']) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    // Get EMI details with car and loan information
    $stmt = $conn->prepare("
        SELECT e.*, l.id as loan_id, l.user_id, l.car_id, 
               c.model, c.plate_no,
               p.method, p.amount as payment_amount, p.created_at
        FROM emis e
        JOIN loans l ON l.id = e.loan_id
        JOIN cars c ON c.id = l.car_id
        LEFT JOIN payments p ON p.emi_id = e.id
        WHERE e.id = ? AND l.user_id = ?
    ");
    $stmt->bind_param("ii", $emiId, $u['id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Payment record not found']);
        exit();
    }
    
    if (!$result['method']) {
        echo json_encode(['success' => false, 'message' => 'No payment record found for this EMI']);
        exit();
    }
    
    // Organize the data
    $response = [
        'success' => true,
        'record' => [
            'method' => $result['method'],
            'amount' => $result['payment_amount'],
            'created_at' => $result['created_at']
        ],
        'emi' => [
            'id' => $result['id'],
            'installment_no' => $result['installment_no'],
            'due_date' => $result['due_date'],
            'principal_component' => $result['principal_component'],
            'interest_component' => $result['interest_component'],
            'amount' => $result['amount'],
            'status' => $result['status']
        ],
        'loan' => [
            'id' => $result['loan_id'],
            'user_id' => $result['user_id'],
            'car_id' => $result['car_id']
        ],
        'car' => [
            'model' => $result['model'],
            'plate_no' => $result['plate_no']
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
