<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Get all routes with their current status
$result = $conn->query("
    SELECT r.id, r.name, r.coordinates, 
           COALESCE(a.status, 'unassigned') as status,
           u.name as cleaner_name
    FROM routes r
    LEFT JOIN assignments a ON a.route_id = r.id AND a.status IN ('pending', 'in-progress')
    LEFT JOIN cleaners c ON a.cleaner_id = c.id
    LEFT JOIN users u ON c.user_id = u.id
");

$routes = [];
while ($row = $result->fetch_assoc()) {
    $routes[] = $row;
}

echo json_encode($routes);
?>