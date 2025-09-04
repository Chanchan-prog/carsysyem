<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

function current_user(): array {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['fullname'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
}

function require_role(string $role): void {
    if (!is_logged_in() || ($_SESSION['role'] ?? '') !== $role) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Forbidden';
        exit();
    }
}

function login_user(array $user): void {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullname'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    if (isset($user['blocked'])) {
        $_SESSION['blocked'] = (int)$user['blocked'];
    }
}

function logout_user(): void {
    session_unset();
    session_destroy();
}

?>


