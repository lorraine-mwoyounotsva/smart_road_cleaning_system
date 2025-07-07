<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$start_date = $_POST['start_date'] ?? date('Y-m-d');
$end_date = $_POST['end_date'] ?? date('Y-m-d');
$export_type = $_POST['export'] ?? 'csv';
$report_type = $_POST['type'] ?? 'summary';

// Fetch data based on report type
if ($report_type === 'summary') {
    $result = $conn->query("
        SELECT 
            r.name as route_name,
            SUM(CASE WHEN cl.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN cl.status = 'missed' THEN 1 ELSE 0 END) as missed_count,
            COUNT(cl.id) as total_assignments
        FROM routes r
        LEFT JOIN assignments a ON r.id = a.route_id
        LEFT JOIN cleaning_logs cl ON a.id = cl.assignment_id
        WHERE DATE(a.assigned_at) BETWEEN '$start_date' AND '$end_date'
        GROUP BY r.id
    ");
} else {
    $result = $conn->query("
        SELECT 
            u.name as cleaner_name,
            COUNT(DISTINCT a.id) as total_assignments,
            SUM(CASE WHEN cl.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN cl.status = 'missed' THEN 1 ELSE 0 END) as missed_count,
            ROUND(SUM(CASE WHEN cl.status = 'completed' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.id) * 100, 2) as completion_rate
        FROM users u
        JOIN cleaners c ON u.id = c.user_id
        LEFT JOIN assignments a ON c.id = a.cleaner_id
        LEFT JOIN cleaning_logs cl ON a.id = cl.assignment_id
        WHERE DATE(a.assigned_at) BETWEEN '$start_date' AND '$end_date'
        GROUP BY u.id
    ");
}

$data = $result->fetch_all(MYSQLI_ASSOC);

// Call Python API
$api_url = 'http://localhost:5000/export'; // Update with your Python API URL

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'data' => $data,
    'type' => $report_type
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_code === 200) {
    // Forward the response
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$report_type.'_report_'.date('Ymd').'.csv"');
    echo $response;
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Export failed']);
}