<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if (isset($_GET['id'])) {
    $assignment_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT a.*, r.name as route_name, u.name as cleaner_name 
                           FROM assignments a
                           JOIN routes r ON a.route_id = r.id
                           JOIN cleaners c ON a.cleaner_id = c.id
                           JOIN users u ON c.user_id = u.id
                           WHERE a.id = ?");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $assignment = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($assignment);
    } else {
        header("HTTP/1.1 404 Not Found");
    }
    
    $stmt->close();
} else {
    header("HTTP/1.1 400 Bad Request");
}
?>