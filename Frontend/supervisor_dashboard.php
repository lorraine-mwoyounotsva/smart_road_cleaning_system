<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

// Get notifications
$notifications = [];
$result = $conn->query("SELECT * FROM notifications WHERE user_id = {$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 5");
if ($result) {
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
}

// Get cleaners
$cleaners = [];
$result = $conn->query("SELECT c.*, u.name FROM cleaners c JOIN users u ON c.user_id = u.id");
if ($result) {
    $cleaners = $result->fetch_all(MYSQLI_ASSOC);
}

// Get routes
$routes = [];
$result = $conn->query("SELECT * FROM routes");
if ($result) {
    $routes = $result->fetch_all(MYSQLI_ASSOC);
}

// Get assignments
$assignments = [];
$result = $conn->query("
    SELECT a.*, r.name as route_name, u.name as cleaner_name 
    FROM assignments a
    JOIN routes r ON a.route_id = r.id
    JOIN cleaners cl ON a.cleaner_id = cl.id
    JOIN users u ON cl.user_id = u.id
    ORDER BY a.assigned_at DESC
");
if ($result) {
    $assignments = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="styles.css">
    <style>
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background-color: #f5f7fa;
        }
        
        .sidebar {
            background: #225b25;
            color: white;
            padding: 20px;
        }
        
        .sidebar-header {
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-nav ul {
            list-style: none;
        }
        
        .sidebar-nav li a {
            display: block;
            padding: 10px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 5px;
            transition: background 0.3s;
        }
        
        .sidebar-nav li a:hover, .sidebar-nav li a.active {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar-nav li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #225b25;
        }
        
        .map-container {
            height: 400px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        #map {
            height: 100%;
            width: 100%;
        }
        
        .section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background: #f9f9f9;
            font-weight: 500;
            color: #555;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-in-progress {
            background: rgb(255, 165, 0);
            color:rgb(100, 81, 22);
        }

        .stats-grid .stat-card.stat-in-progress {
            background: rgba(255, 165, 0, 0.1);
            border-left: 4px solid orange;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-missed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            padding: 8px 16px;
            background: #225b25;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #1a471c;
        }
        
        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .notification-item.unread {
            background: #f0f7ff;
        }
        
        .notification-title {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .notification-time {
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Smart Cleaning</h2>
                <p>Supervisor Dashboard</p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="supervisor_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="route_manager.php"><i class="fas fa-route"></i> Route Manager</a></li>
                    <li><a href="cleaner_manager.php"><i class="fas fa-users"></i> Cleaner Manager</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Dashboard Overview</h1>
                <div class="user-info">
                    <span><?= $_SESSION['name'] ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=225b25&color=fff" alt="User">
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Cleaners</h3>
                    <div class="value"><?= count($cleaners) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Routes</h3>
                    <div class="value"><?= count(array_filter($assignments, fn($a) => $a['status'] === 'in-progress')) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Completed Today</h3>
                    <div class="value">
                        <?= count(array_filter($assignments, fn($a) => $a['status'] === 'completed' && date('Y-m-d', strtotime($a['end_time'])) === date('Y-m-d'))) ?>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Missed Routes</h3>
                    <div class="value"><?= count(array_filter($assignments, fn($a) => $a['status'] === 'missed')) ?></div>
                </div>
            </div>
            
            <div class="map-container">
                <div id="map"></div>
            </div>
            
            
            
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Recent Assignments</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Cleaner</th>
                            <th>Route</th>
                            <th>Status</th>
                            <th>Assigned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?= $assignment['cleaner_name'] ?></td>
                                <td><?= $assignment['route_name'] ?></td>
                                <td>
                                    <span class="status-badge status-<?= str_replace('-', '_', $assignment['status']) ?>">
                                        <?= ucfirst($assignment['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($assignment['assigned_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Notifications</h2>
                </div>
                <div>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                            <div class="notification-title"><?= $notification['title'] ?></div>
                            <div><?= $notification['message'] ?></div>
                            <div class="notification-time">
                                <?= date('M d, H:i', strtotime($notification['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([-22.5609, 17.0658], 13); // Windhoek coordinates
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Load routes data and display on map
        fetch('get_routes.php')
            .then(response => response.json())
            .then(data => {
                data.forEach(route => {
                    const coordinates = JSON.parse(route.coordinates);
                    const polyline = L.polyline(coordinates, {
                        color: getStatusColor(route.status),
                        weight: 5,
                        opacity: 0.7
                    }).addTo(map);
                    
                    polyline.bindPopup(`
                        <b>${route.name}</b><br>
                        Status: ${route.status}<br>
                        ${route.cleaner_name ? 'Cleaner: ' + route.cleaner_name : ''}
                    `);
                });
                
                // Fit map to bounds of all routes
                if (data.length > 0) {
                    const bounds = data.flatMap(route => JSON.parse(route.coordinates));
                    map.fitBounds(bounds);
                }
            });
        
        function getStatusColor(status) {
            switch(status) {
                case 'completed': return 'green';
                case 'in-progress': return 'orange';
                case 'missed': return 'red';
                default: return 'gray';
            }
        }
        
        // Refresh data every 30 seconds
        setInterval(() => {
        }, 30000);
    </script>
    <!-- Leaflet JS (for maps) -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <!-- custom JS files -->
    <script src="js/map.js"></script>
    <script src="js/notifications.js"></script>
    <script src="js/form-validator.js"></script>
</body>
</html>