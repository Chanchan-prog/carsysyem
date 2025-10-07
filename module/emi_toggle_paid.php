<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';

$isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emiId = (int)($_POST['emi_id'] ?? 0);
    $method = trim($_POST['method'] ?? 'manual');
    $status = trim($_POST['status'] ?? 'paid');
    
    if ($emiId) {
        // Authorization: allow if admin or owner of the loan
        $u = current_user();
        $isAdmin = ($u['role'] ?? '') === 'admin';
        $authorized = false;
        
        if ($isAdmin) {
            $authorized = true;
        } else {
            $q = $conn->prepare('SELECT l.user_id FROM emis e JOIN loans l ON l.id=e.loan_id WHERE e.id=?');
            $q->bind_param('i', $emiId);
            $q->execute();
            $owner = $q->get_result()->fetch_assoc()['user_id'] ?? 0;
            if ((int)$owner === (int)($u['id'] ?? 0)) {
                $authorized = true;
            }
        }
        
        if ($authorized) {
            if ($status === 'paid') {
                $success = mark_emi_paid($emiId, $method);
            } else {
                $success = mark_emi_unpaid($emiId);
            }
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Payment status updated successfully' : 'Failed to update payment status'
                ]);
                exit();
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]);
                exit();
            }
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid EMI ID'
            ]);
            exit();
        }
    }
}

if (!$isAjax) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
    exit();
}

