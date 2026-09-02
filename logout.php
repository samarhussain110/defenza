<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    clearRememberUser((int) $userId, getDB());
}

session_destroy();
header('Location: ' . APP_URL . '/auth/login.php');
exit;
