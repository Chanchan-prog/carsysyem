<?php
include __DIR__ . '/../db/db.php';

function get_active_cars() {
    global $conn;
    $cars = [];
    $res = $conn->query(
        "SELECT c.id, c.model, c.price\n" .
        "FROM cars c\n" .
        "LEFT JOIN loans l ON l.car_id=c.id AND l.status='approved'\n" .
        "WHERE c.is_active=1 AND l.id IS NULL\n" .
        "ORDER BY c.model"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cars[] = $row;
        }
    }
    return $cars;
}

function calculate_emi($principal, $annualRate, $months) {
    $r = ($annualRate / 12) / 100.0;
    if ($r == 0) {
        return $months > 0 ? round($principal / $months, 2) : 0;
    }
    $emi = $principal * $r * pow(1 + $r, $months) / (pow(1 + $r, $months) - 1);
    return round($emi, 2);
}

function create_loan($userId, $carId, $price, $downPayment, $annualRate, $months) {
    global $conn;
    // Prevent applying for a car that is already sold (approved loan exists)
    $chk = $conn->prepare("SELECT 1 FROM loans WHERE car_id=? AND status='approved' LIMIT 1");
    $chk->bind_param("i", $carId);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) {
        return false;
    }
    // Enforce trusted price and down payment (customer cannot change)
    $carStmt = $conn->prepare("SELECT price FROM cars WHERE id=? AND is_active=1");
    $carStmt->bind_param("i", $carId);
    $carStmt->execute();
    $carRow = $carStmt->get_result()->fetch_assoc();
    if (!$carRow) {
        return false;
    }
    $price = (float)$carRow['price'];
    $downPayment = 0.0;
    $principal = max(0, $price - $downPayment);
    $stmt = $conn->prepare("INSERT INTO loans (user_id, car_id, car_price, down_payment, principal, annual_interest_rate, term_months) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("iiidddi", $userId, $carId, $price, $downPayment, $principal, $annualRate, $months);
    if (!$stmt->execute()) {
        return false;
    }
    return $conn->insert_id;
}

function generate_emi_schedule($loanId) {
    global $conn;
    $q = $conn->prepare("SELECT principal, annual_interest_rate, term_months, created_at FROM loans WHERE id=?");
    $q->bind_param("i", $loanId);
    $q->execute();
    $loan = $q->get_result()->fetch_assoc();
    if (!$loan) return false;
    $principal = (float)$loan['principal'];
    $rate = (float)$loan['annual_interest_rate'];
    $months = (int)$loan['term_months'];
    $emi = calculate_emi($principal, $rate, $months);
    $r = ($rate / 12) / 100.0;
    $balance = $principal;
    $startDate = new DateTime();
    for ($i = 1; $i <= $months; $i++) {
        $interest = round($balance * $r, 2);
        $principalComponent = round($emi - $interest, 2);
        if ($i === $months) {
            $principalComponent = round($balance, 2);
        }
        $amount = round($principalComponent + $interest, 2);
        $due = (clone $startDate)->modify("+" . $i . " month");
        $stmt = $conn->prepare("INSERT INTO emis (loan_id, installment_no, due_date, principal_component, interest_component, amount) VALUES (?,?,?,?,?,?)");
        $dueDate = $due->format('Y-m-d');
        $stmt->bind_param("iisssd", $loanId, $i, $dueDate, $principalComponent, $interest, $amount);
        $stmt->execute();
        $balance = max(0, $balance - $principalComponent);
    }
    return true;
}

function list_user_loans($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT l.*, c.model FROM loans l JOIN cars c ON c.id=l.car_id WHERE l.user_id=? ORDER BY l.id DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result();
}

function list_loan_emis($loanId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM emis WHERE loan_id=? ORDER BY installment_no");
    $stmt->bind_param("i", $loanId);
    $stmt->execute();
    return $stmt->get_result();
}

function mark_emi_paid($emiId, $method = 'manual') {
    global $conn;
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE emis SET status='paid', paid_at=NOW() WHERE id=? AND status<>'paid'");
        $stmt->bind_param("i", $emiId);
        $stmt->execute();
        $stmt2 = $conn->prepare("INSERT INTO payments (emi_id, amount, method) SELECT id, amount, ? FROM emis WHERE id=?");
        $stmt2->bind_param("si", $method, $emiId);
        $stmt2->execute();
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
?>
