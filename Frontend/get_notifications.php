<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$userId = $_SESSION['user_id'];
$result = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = $userId 
    ORDER BY created_at DESC 
    LIMIT 10
");

echo json_encode($result->fetch_all(MYSQLI_ASSOC));
?>