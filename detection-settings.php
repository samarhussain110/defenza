<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voiceEnabled = isset($_POST['voice_enabled']) ? 1 : 0;
    $keyword = trim($_POST['emergency_keyword'] ?? 'Help');
    $keyword = substr($keyword, 0, 64);
    $shakeEnabled = isset($_POST['shake_enabled']) ? 1 : 0;
    $shakeSensitivity = in_array($_POST['shake_sensitivity'] ?? 'medium', ['low', 'medium', 'high'], true) ? $_POST['shake_sensitivity'] : 'medium';

    $stmt = $db->prepare('UPDATE user_settings SET voice_command_enabled = ?, emergency_keyword = ?, shake_detection_enabled = ?, shake_sensitivity = ? WHERE user_id = ?');
    $stmt->bind_param('isssi', $voiceEnabled, $keyword, $shakeEnabled, $shakeSensitivity, $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Detection settings updated.']);
    exit;
}

$settings = getUserSettings($userId, $db);
echo json_encode(['success' => true, 'settings' => $settings]);
