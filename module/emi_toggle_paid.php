<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../function/function.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emiId = (int)($_POST['emi_id'] ?? 0);
    $method = trim($_POST['method'] ?? 'manual');
    if ($emiId) {
        // Authorization: allow if admin or owner of the loan
        $u = current_user();
        $isAdmin = ($u['role'] ?? '') === 'admin';
        if ($isAdmin) {
            mark_emi_paid($emiId, $method);
        } else {
            $q = $conn->prepare('SELECT l.user_id FROM emis e JOIN loans l ON l.id=e.loan_id WHERE e.id=?');
            $q->bind_param('i', $emiId);
            $q->execute();
            $owner = $q->get_result()->fetch_assoc()['user_id'] ?? 0;
            if ((int)$owner === (int)($u['id'] ?? 0)) {
                mark_emi_paid($emiId, $method);
            }
        }
    }
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
exit();

