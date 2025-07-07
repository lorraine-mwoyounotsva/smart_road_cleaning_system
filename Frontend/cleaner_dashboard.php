<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'cleaner') {
    header("Location: login.php");
    exit();
}

// Get cleaner info
$cleaner = [];
$result = $conn->query("
    SELECT c.*, u.name 
    FROM cleaners c 
    JOIN users u ON c.user_id = u.id 
    WHERE u.email = '".$conn->real_escape_string($_SESSION['email'])."'
");

if ($result && $result->num_rows > 0) {
    $cleaner = $result->fetch_assoc();
} else {
    die("Cleaner profile not found. Please contact your supervisor.");
}

// Get current assignment
$assignment = [];
$logs = [];

if (!empty($cleaner['id'])) {
    $result = $conn->query("
        SELECT a.*, r.name as route_name, r.coordinates 
        FROM assignments a
        JOIN routes r ON a.route_id = r.id
        WHERE a.cleaner_id = ".intval($cleaner['id'])."
        AND a.status IN ('pending', 'in-progress')
        ORDER BY a.assigned_at DESC
        LIMIT 1
    ");

    if ($result && $result->num_rows > 0) {
        $assignment = $result->fetch_assoc();
        
        // Get cleaning logs
        $result = $conn->query("
            SELECT * FROM cleaning_logs 
            WHERE assignment_id = ".intval($assignment['id'])."
            ORDER BY logged_at DESC
        ");
        
        if ($result) {
            $logs = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Get notifications
$notifications = [];
$result = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = ".intval($cleaner['user_id'])."
    ORDER BY created_at DESC
    LIMIT 10
");

if ($result) {
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    
    // Mark all unread notifications as read when loading the page
    if (!empty($notifications)) {
        $unread_ids = array_filter($notifications, function($n) { return !$n['is_read']; });
        if (!empty($unread_ids)) {
            $unread_ids = array_column($unread_ids, 'id');
            $conn->query("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id IN (".implode(',', $unread_ids).")
            ");
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleaner Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #225b25;
            --secondary-color: #17a2b8;
            --success-color: #28a745;
            --warning-color: #fd7e14;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background: var(--primary-color);
            color: white;
            padding: 20px;
        }
        
        .sidebar-header {
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            margin: 0;
            color: white;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 5px;
            transition: background-color 0.3s;
        }
        
        .sidebar-nav li a:hover, .sidebar-nav li a.active {
            background-color: rgba(255,255,255,0.1);
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
        
        .header h1 {
            margin: 0;
            color: var(--primary-color);
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
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-in-progress {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a471c;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #138496;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .map-container {
            height: 300px;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        #map {
            height: 100%;
            width: 100%;
        }
        
        .task-details {
            margin-bottom: 20px;
        }
        
        .task-details p {
            margin: 5px 0;
        }
        
        .task-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .activity-log {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .log-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .log-time {
            color: #666;
            font-size: 12px;
            margin-top: 3px;
        }
        
        .no-assignment {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        .no-assignment i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
        }

        .notifications-container {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    position: relative;
    padding-left: 40px;
    transition: background-color 0.2s;
}

.notification-item.unread {
    background-color: #f8f9fa;
}

.notification-item:hover {
    background-color: #f1f1f1;
}

.notification-icon {
    position: absolute;
    left: 15px;
    top: 14px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
}

.notification-info {
    margin-bottom: 5px;
}

.notification-title {
    font-weight: 600;
    margin-right: 8px;
}

.notification-time {
    color: #666;
    font-size: 12px;
    margin-top: 3px;
}

.notification-type-info .notification-icon {
    background-color: var(--secondary-color);
}

.notification-type-warning .notification-icon {
    background-color: var(--warning-color);
}

.notification-type-important .notification-icon {
    background-color: var(--danger-color);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: var(--danger-color);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.mark-all-read {
    font-size: 13px;
    color: var(--secondary-color);
    cursor: pointer;
    transition: color 0.2s;
}

.mark-all-read:hover {
    color: var(--primary-color);
    text-decoration: underline;
}

    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Smart Cleaning</h2>
                <p>Cleaner Dashboard</p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="cleaner_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span><?= $cleaner['name'] ?></span>
                    <img src="<?= !empty($cleaner['photo']) ? $cleaner['photo'] : 'https://ui-avatars.com/api/?name='.urlencode($cleaner['name']).'&background=225b25&color=fff' ?>" alt="User">
                </div>
            </div>
            
            <?php if (!empty($assignment)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Current Assignment: <?= $assignment['route_name'] ?></h3>
                        <span class="status-badge status-<?= str_replace('-', '_', $assignment['status']) ?>">
                            <?= ucfirst($assignment['status']) ?>
                        </span>
                    </div>
                    
                    <div class="map-container">
                        <div id="map"></div>
                    </div>
                    
                    <div class="task-details">
                        <p><strong>Assigned:</strong> <?= date('M d, Y H:i', strtotime($assignment['assigned_at'])) ?></p>
                        <?php if ($assignment['start_time']): ?>
                            <p><strong>Started:</strong> <?= date('M d, Y H:i', strtotime($assignment['start_time'])) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="task-actions">
                        <?php if ($assignment['status'] === 'pending'): ?>
                            <button class="btn btn-primary" onclick="updateTask('started')">
                                <i class="fas fa-play"></i> Start Task
                            </button>
                        <?php elseif ($assignment['status'] === 'in-progress'): ?>
                            <button class="btn btn-secondary" onclick="updateTask('in-progress')">
                                <i class="fas fa-sync-alt"></i> Update Progress
                            </button>
                            <button class="btn btn-success" onclick="updateTask('completed')">
                                <i class="fas fa-check"></i> Mark Complete
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-danger" onclick="reportIssue()">
                            <i class="fas fa-exclamation-triangle"></i> Report Issue
                        </button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Activity Log</h3>
                    </div>
                    <div class="activity-log">
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <div class="log-item">
                                    <div><?= ucfirst($log['status']) ?> - <?= $log['notes'] ?></div>
                                    <div class="log-time"><?= date('M d, H:i', strtotime($log['logged_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-assignment">
                                <p>No activity logged yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="no-assignment">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No Current Assignment</h3>
                        <p>You don't have any assigned routes at the moment.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
    <div class="card-header">
        <h3 class="card-title">Notifications</h3>
        <?php if (!empty($notifications)): ?>
            <span class="mark-all-read" onclick="markAllRead()">Mark all as read</span>
        <?php endif; ?>
    </div>
    <div class="notifications-container">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item notification-type-<?= $notification['type'] ?> <?= !$notification['is_read'] ? 'unread' : '' ?>">
                    <div class="notification-icon">
                        <i class="fas fa-<?= 
                            $notification['type'] === 'info' ? 'info' : 
                            ($notification['type'] === 'warning' ? 'exclamation' : 'exclamation-triangle') 
                        ?>"></i>
                    </div>
                    <div class="notification-info">
                        <span class="notification-title"><?= htmlspecialchars($notification['title']) ?></span>
                    </div>
                    <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                    <div class="notification-time">
                        <?= date('M d, H:i', strtotime($notification['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-assignment">
                <p>No notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialize map if there's an assignment
        <?php if (!empty($assignment)): ?>
            const map = L.map('map').setView([-22.5609, 17.0658], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            const coordinates = JSON.parse('<?= $assignment['coordinates'] ?>');
            const polyline = L.polyline(coordinates, {
                color: '#225b25',
                weight: 5,
                opacity: 0.7
            }).addTo(map);
            
            map.fitBounds(polyline.getBounds());
        <?php endif; ?>
        
        function updateTask(status) {
            let notes = '';
            if (status === 'in-progress') {
                notes = prompt('Enter progress update:');
                if (notes === null) return;
            }
            
            fetch('update_task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `assignment_id=<?= $assignment['id'] ?? 0 ?>&status=${status}&notes=${encodeURIComponent(notes)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Task updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function reportIssue() {
            const issue = prompt('Describe the issue you encountered:');
            if (issue && issue.trim() !== '') {
                fetch('report_issue.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `assignment_id=<?= $assignment['id'] ?? 0 ?>&issue=${encodeURIComponent(issue)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Issue reported successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function markAllRead() {
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=<?= $cleaner['user_id'] ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove unread styles
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Check for new notifications periodically
let lastNotificationId = <?= !empty($notifications) ? $notifications[0]['id'] : 0 ?>;
let notificationCheckInterval = setInterval(checkNewNotifications, 30000); // Check every 30 seconds

function checkNewNotifications() {
    fetch('check_notifications.php?last_id=' + lastNotificationId + '&user_id=<?= $cleaner['user_id'] ?>')
    .then(response => response.json())
    .then(data => {
        if (data.new_notifications && data.new_notifications.length > 0) {
            // Update last notification ID
            lastNotificationId = data.new_notifications[0].id;
            
            // Show notification alert
            showNewNotificationAlert(data.new_notifications.length);
        }
    });
}

function showNewNotificationAlert(count) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'notification-alert';
    alertDiv.innerHTML = `
        <div style="position: fixed; bottom: 20px; right: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000; display: flex; align-items: center;">
            <i class="fas fa-bell" style="color: var(--secondary-color); margin-right: 10px;"></i>
            <span>You have ${count} new notification${count > 1 ? 's' : ''}</span>
            <button onclick="location.reload()" style="margin-left: 15px; background: var(--primary-color); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                View
            </button>
        </div>
    `;
    document.body.appendChild(alertDiv);
    
    // Remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

    </script>
</body>
</html>