<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getPDO();
$offlineMode = !$pdo;

$userId = (int) $_SESSION['user_id'];

if ($offlineMode) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            exit;
        }

        $supportReply = 'Thanks for reaching out. A support specialist will review your request and get back to you shortly.';
        echo json_encode([
            'success' => true,
            'offline' => true,
            'messages' => [
                ['sender' => 'user', 'message' => $message, 'operator_name' => null, 'created_at' => date('h:i A')],
                ['sender' => 'support', 'message' => $supportReply, 'operator_name' => 'Support', 'created_at' => date('h:i A')],
            ],
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'offline' => true,
        'messages' => [
            ['sender' => 'support', 'message' => 'Hi there! Welcome to Defenza support. Tell us what happened and we will respond immediately.', 'operator_name' => 'Support', 'created_at' => date('h:i A')],
        ],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }

    // If the helpline_messages table does not exist, fallback to offline response
    try {
        $tblCheck = $pdo->query("SHOW TABLES LIKE 'helpline_messages'");
        $tblExists = $tblCheck && $tblCheck->rowCount() > 0;
    } catch (Throwable $e) {
        $tblExists = false;
    }

    if (!$tblExists) {
        $supportReply = 'Thanks for reaching out. A support specialist will review your request and get back to you shortly.';
        echo json_encode([
            'success' => true,
            'offline' => true,
            'messages' => [
                ['sender' => 'user', 'message' => $message, 'operator_name' => null, 'created_at' => date('h:i A')],
                ['sender' => 'support', 'message' => $supportReply, 'operator_name' => 'Support', 'created_at' => date('h:i A')],
            ],
        ]);
        exit;
    }

    try {
        $insert = $pdo->prepare('INSERT INTO helpline_messages (user_id, sender, message, operator_name) VALUES (:user_id, :sender, :message, :operator_name)');
        $insert->execute([
            ':user_id' => $userId,
            ':sender' => 'user',
            ':message' => $message,
            ':operator_name' => null,
        ]);

        $supportReply = 'Thanks for reaching out. A support specialist will review your request and get back to you shortly.';
        $insert->execute([
            ':user_id' => $userId,
            ':sender' => 'support',
            ':message' => $supportReply,
            ':operator_name' => 'Support',
        ]);

        $stmt = $pdo->prepare('SELECT sender, message, operator_name, DATE_FORMAT(created_at, "%h:%i %p") AS created_at FROM helpline_messages WHERE user_id = :user_id ORDER BY id DESC LIMIT 20');
        $stmt->execute([':user_id' => $userId]);
        $messages = array_reverse($stmt->fetchAll());

        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    } catch (Throwable $e) {
        // On any DB error, fallback to offline reply to avoid breaking pages that don't use this API
        $supportReply = 'Thanks for reaching out. A support specialist will review your request and get back to you shortly.';
        echo json_encode([
            'success' => true,
            'offline' => true,
            'messages' => [
                ['sender' => 'user', 'message' => $message, 'operator_name' => null, 'created_at' => date('h:i A')],
                ['sender' => 'support', 'message' => $supportReply, 'operator_name' => 'Support', 'created_at' => date('h:i A')],
            ],
        ]);
        exit;
    }
}

$stmt = $pdo->prepare('SELECT sender, message, operator_name, DATE_FORMAT(created_at, "%h:%i %p") AS created_at FROM helpline_messages WHERE user_id = :user_id ORDER BY id ASC LIMIT 50');
$stmt->execute([':user_id' => $userId]);
$messages = $stmt->fetchAll();

    if (empty($messages)) {
    $messages = [
        ['sender' => 'support', 'message' => 'Hi there! Welcome to Defenza support. Tell us what happened and we will respond immediately.', 'operator_name' => 'Support', 'created_at' => date('h:i A')],
    ];
}

echo json_encode(['success' => true, 'messages' => $messages]);
