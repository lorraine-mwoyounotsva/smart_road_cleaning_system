<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$assignment_id = intval($_POST['assignment_id']);
$status = $conn->real_escape_string($_POST['status']);
$notes = $conn->real_escape_string($_POST['notes'] ?? '');

// Verify the cleaner owns this assignment
$check = $conn->query("
    SELECT a.id 
    FROM assignments a
    JOIN cleaners c ON a.cleaner_id = c.id
    WHERE a.id = $assignment_id AND c.user_id = {$_SESSION['user_id']}
");

if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment']);
    exit();
}

// Update assignment status
$update_data = ['status' => $status];
if ($status === 'started') {
    $update_data['status'] = 'in-progress';
    $update_data['start_time'] = date('Y-m-d H:i:s');
} elseif ($status === 'completed') {
    $update_data['end_time'] = date('Y-m-d H:i:s');
}

$set_clause = implode(', ', array_map(fn($k, $v) => "$k = '$v'", array_keys($update_data), $update_data));

$sql = "UPDATE assignments SET $set_clause WHERE id = $assignment_id";
if (!$conn->query($sql)) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

// Log this update
$log_sql = "INSERT INTO cleaning_logs (assignment_id, status, notes) 
            VALUES ($assignment_id, '$status', '$notes')";
if (!$conn->query($log_sql)) {
    echo json_encode(['success' => false, 'message' => 'Failed to log update']);
    exit();
}

// If task is completed, create notification for supervisor
if ($status === 'completed') {
    $assignment = $conn->query("
        SELECT r.name as route_name, a.assigned_by 
        FROM assignments a
        JOIN routes r ON a.route_id = r.id
        WHERE a.id = $assignment_id
    ")->fetch_assoc();
    
    $title = "Task Completed";
    $message = "Route {$assignment['route_name']} has been completed";
    $conn->query("INSERT INTO notifications (user_id, title, message, type) 
                  VALUES ({$assignment['assigned_by']}, '$title', '$message', 'system')");
}

echo json_encode(['success' => true]);
?>