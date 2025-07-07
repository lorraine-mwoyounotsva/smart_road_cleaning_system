<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'supervisor') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if (isset($_GET['id'])) {
    $cleaner_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT c.*, u.name, u.email FROM cleaners c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->bind_param("i", $cleaner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cleaner = $result->fetch_assoc();
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($cleaner);
    exit();
}

header("HTTP/1.1 400 Bad Request");
?> 