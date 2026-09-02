<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();
$user = getAuthenticatedUser();
$userId = (int) ($user['id'] ?? 0);

if ($userId <= 0) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$db = getDB();
if (!$db) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

try {
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $deleted = $stmt->execute();
    $stmt->close();

    session_destroy();
    header('Content-Type: application/json');

    if ($deleted) {
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to delete your account right now.']);
    }
} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to delete your account right now.']);
    error_log('[delete_account.php] ' . $e->getMessage());
}
