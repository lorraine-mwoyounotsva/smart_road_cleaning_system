<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$assignment_id = intval($_POST['assignment_id']);
$issue = $conn->real_escape_string($_POST['issue']);

// Verify the cleaner owns this assignment
$check = $conn->query("
    SELECT a.id, a.assigned_by, r.name as route_name 
    FROM assignments a
    JOIN cleaners c ON a.cleaner_id = c.id
    JOIN routes r ON a.route_id = r.id
    WHERE a.id = $assignment_id AND c.user_id = {$_SESSION['user_id']}
");

if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment']);
    exit();
}

$assignment = $check->fetch_assoc();

// Create notification for supervisor
$title = "Issue Reported";
$message = "Issue with route {$assignment['route_name']}: $issue";
$conn->query("INSERT INTO notifications (user_id, title, message, type) 
              VALUES ({$assignment['assigned_by']}, '$title', '$message', 'alert')");

echo json_encode(['success' => true]);
?>