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

$sosId = isset($_POST['sos_id']) ? (int) $_POST['sos_id'] : 0;
if ($sosId <= 0) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid SOS alert id.']);
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
    $stmt = $db->prepare('UPDATE sos_alerts SET status = "resolved", updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND status = "active"');
    $stmt->bind_param('ii', $sosId, $userId);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($affectedRows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    $checkStmt = $db->prepare('SELECT id FROM sos_alerts WHERE id = ? AND user_id = ?');
    $checkStmt->bind_param('ii', $sosId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $exists = $checkResult && $checkResult->num_rows > 0;
    $checkStmt->close();

    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exists ? 'This alert is already resolved.' : 'Alert not found for this user.'
    ]);
    exit;
} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to resolve alert right now.']);
    error_log('[resolve_sos.php] ' . $e->getMessage());
    exit;
}
