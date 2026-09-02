<?php
header('Content-Type: application/json');
echo json_encode([
    'success'       => true,
    'unread_count'  => 0,
    'notifications' => [],
    'message'       => 'No new notifications.',
]);
