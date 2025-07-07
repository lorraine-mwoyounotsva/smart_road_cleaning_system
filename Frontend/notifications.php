<?php
session_start();
require_once 'config.php';
require_once 'notification_functions.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'cleaner') {
    header("Location: login.php");
    exit();
}

// Get cleaner info
$cleaner = [];
$result = $conn->query("
    SELECT c.*, u.name, u.id as user_id 
    FROM cleaners c 
    JOIN users u ON c.user_id = u.id 
    WHERE u.email = '".$conn->real_escape_string($_SESSION['email'])."'
");

if ($result && $result->num_rows > 0) {
    $cleaner = $result->fetch_assoc();
} else {
    die("Cleaner profile not found. Please contact your supervisor.");
}

// Get all notifications
$notifications = [];
if (!empty($cleaner['user_id'])) {
    $result = $conn->query("
        SELECT * FROM notifications 
        WHERE user_id = ".intval($cleaner['user_id'])."
        ORDER BY created_at DESC
    ");
    
    if ($result) {
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    markAllNotificationsAsRead($cleaner['user_id']);
    header("Location: notifications.php");
    exit();
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
    <title>All Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Include all the CSS from cleaner_dashboard.php */
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
            gap: 15px;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f9f9f9;
        }
        
        .notification-item.unread {
            background-color: #f0f8ff;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }
        
        .notification-message {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }
        
        .notification-time {
            font-size: 12px;
            color: #999;
        }
        
        .notification-type {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .type-info {
            background: #e1f0ff;
            color: #0066cc;
        }
        
        .type-warning {
            background: #fff8e6;
            color: #ff9900;
        }
        
        .type-urgent {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .mark-all-read {
            color: var(--primary-color);
            font-size: 14px;
            cursor: pointer;
        }
        
        .no-notifications {
            padding: 40px;
            text-align: center;
            color: #999;
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
                    <li><a href="cleaner_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a></li>
                    <li><a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Notifications</h1>
                <div class="user-info">
                    <span><?= $cleaner['name'] ?></span>
                    <img src="<?= !empty($cleaner['photo']) ? $cleaner['photo'] : 'https://ui-avatars.com/api/?name='.urlencode($cleaner['name']).'&background=225b25&color=fff' ?>" alt="User">
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Notifications</h3>
                    <a href="?mark_all_read=1" class="btn btn-primary">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </a>
                </div>
                
                <div class="notification-list">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>">
                                <div class="notification-title">
                                    <span><?= htmlspecialchars($notification['title']) ?></span>
                                    <span class="notification-type type-<?= $notification['type'] ?>">
                                        <?= ucfirst($notification['type']) ?>
                                    </span>
                                </div>
                                <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                                <div class="notification-time">
                                    <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-notifications">
                            <i class="far fa-bell-slash" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <h3>No notifications yet</h3>
                            <p>You'll see notifications here when your supervisor sends you messages.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>