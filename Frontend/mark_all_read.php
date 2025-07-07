<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = {$_SESSION['user_id']}");
echo json_encode(['success' => true]);
?>