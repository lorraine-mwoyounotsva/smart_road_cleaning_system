<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if (isset($_GET['id'])) {
    $notification_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT n.*, c.id as cleaner_id 
                           FROM notifications n
                           LEFT JOIN cleaners c ON n.user_id = c.user_id
                           WHERE n.id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $notification = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($notification);
    } else {
        header("HTTP/1.1 404 Not Found");
    }
    
    $stmt->close();
} else {
    header("HTTP/1.1 400 Bad Request");
}
?>