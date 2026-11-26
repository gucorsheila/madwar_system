<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($amount) {
    return '₱' . number_format((float)$amount, 2);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function loggedIn() {
    return currentUser() !== null;
}

function requireLogin() {
    if (!loggedIn()) {
        header('Location: /madwar/login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();

    if (!in_array(currentUser()['role'], (array)$roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' .
        e(csrfToken()) . '">';
}

function verifyCsrf() {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(csrfToken(), $_POST['csrf_token'])
    ) {
        http_response_code(419);
        exit('Invalid request token. Please go back and try again.');
    }
}

function flash($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function showFlash() {
    if (empty($_SESSION['flash'])) {
        return;
    }

    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);

    echo '<div class="notice ' . e($f['type']) . '">' .
        e($f['message']) . '</div>';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function recordHistory($pdo, $orderId, $status, $userId) {
    $stmt = $pdo->prepare(
        "INSERT INTO order_status_history
        (order_id, status, changed_by)
        VALUES (?, ?, ?)"
    );

    $stmt->execute([$orderId, $status, $userId]);
}