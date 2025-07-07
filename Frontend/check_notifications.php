<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'cleaner') {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $last_id = intval($_GET['last_id']);
    $user_id = intval($_GET['user_id']);
    
    $result = $conn->query("
        SELECT * FROM notifications 
        WHERE user_id = $user_id AND id > $last_id
        ORDER BY created_at DESC
    ");
    
    if ($result) {
        $new_notifications = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            'success' => true,
            'new_notifications' => $new_notifications
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>