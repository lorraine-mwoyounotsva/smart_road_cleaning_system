<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

// Handle cleaner deletion
if (isset($_GET['delete_cleaner'])) {
    $cleaner_id = (int)$_GET['delete_cleaner'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First get the user_id from cleaners table
        $stmt = $conn->prepare("SELECT user_id FROM cleaners WHERE id = ?");
        $stmt->bind_param("i", $cleaner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cleaner = $result->fetch_assoc();
        $stmt->close();
        
        if (!$cleaner) {
            throw new Exception("Cleaner not found");
        }
        
        // Delete from cleaners table
        $stmt = $conn->prepare("DELETE FROM cleaners WHERE id = ?");
        $stmt->bind_param("i", $cleaner_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete from users table
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $cleaner['user_id']);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        $_SESSION['success'] = "Cleaner deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting cleaner: " . $e->getMessage();
    }
    
    header("Location: cleaner_manager.php");
    exit();
}

// Handle assignment deletion
if (isset($_GET['delete_assignment'])) {
    $assignment_id = (int)$_GET['delete_assignment'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->bind_param("i", $assignment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Assignment deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting assignment";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: cleaner_manager.php");
    exit();
}

// Handle notification deletion
if (isset($_GET['delete_notification'])) {
    $notification_id = (int)$_GET['delete_notification'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $notification_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Notification deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting notification";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: cleaner_manager.php");
    exit();
}

// Get cleaners with their basic user information (without phone since it's not in your users table)
$cleaners = [];
$result = $conn->query("
    SELECT c.*, u.name, u.email 
    FROM cleaners c 
    JOIN users u ON c.user_id = u.id
");
if ($result) {
    $cleaners = $result->fetch_all(MYSQLI_ASSOC);
}

// Get routes
$routes = [];
$result = $conn->query("SELECT * FROM routes");
if ($result) {
    $routes = $result->fetch_all(MYSQLI_ASSOC);
}

// Get assignments with cleaner and route information
$assignments = [];
$result = $conn->query("
    SELECT a.*, r.name as route_name, u.name as cleaner_name, c.photo, c.shift
    FROM assignments a
    JOIN routes r ON a.route_id = r.id
    JOIN cleaners c ON a.cleaner_id = c.id
    JOIN users u ON c.user_id = u.id
    ORDER BY a.assigned_at DESC
");
if ($result) {
    $assignments = $result->fetch_all(MYSQLI_ASSOC);
}

// Get cleaning logs
$cleaning_logs = [];
$result = $conn->query("
    SELECT cl.*, a.cleaner_id, u.name as cleaner_name, r.name as route_name
    FROM cleaning_logs cl
    JOIN assignments a ON cl.assignment_id = a.id
    JOIN cleaners c ON a.cleaner_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN routes r ON a.route_id = r.id
    ORDER BY cl.logged_at DESC
    LIMIT 10
");
if ($result) {
    $cleaning_logs = $result->fetch_all(MYSQLI_ASSOC);
}

// Get notifications
$notifications = [];
$result = $conn->query("SELECT * FROM notifications WHERE user_id = {$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 5");
if ($result) {
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleaner Manager | Supervisor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Main Dashboard Layout */
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Sidebar Styles */
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
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .sidebar-header p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li a {
            display: block;
            padding: 10px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 5px;
            transition: background 0.3s;
            font-size: 0.95rem;
        }
        
        .sidebar-nav li a:hover, .sidebar-nav li a.active {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar-nav li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
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
            font-size: 1.8rem;
            color: #333;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-info span {
            font-weight: 500;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Tabs Navigation */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            color: #555;
            transition: all 0.3s;
        }
        
        .tab:hover {
            color: #225b25;
        }
        
        .tab.active {
            border-bottom: 3px solid #225b25;
            color: #225b25;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Section Styles */
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
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background: #f9f9f9;
            font-weight: 600;
            color: #555;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .shift-morning {
            background: #fff3cd;
            color: #856404;
        }
        
        .shift-afternoon {
            background: #cce5ff;
            color: #004085;
        }
        
        .shift-evening {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-pending {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-missed {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background: #225b25;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a471c;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }
        
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            max-height: 90vh;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            color: #333;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #777;
        }
        
        .close:hover {
            color: #333;
        }
        
        /* Cleaner Photo */
        .cleaner-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 3px solid #f0f0f0;
            max-height: 200px;
        }
        
        .table-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
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
                    <li><a href="supervisor_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="route_manager.php"><i class="fas fa-route"></i> Route Manager</a></li>
                    <li><a href="cleaner_manager.php" class="active"><i class="fas fa-users"></i> Cleaner Manager</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Cleaner Manager</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=225b25&color=fff" alt="User">
                </div>
            </div>
            
            <div class="tabs">
                <div class="tab active" onclick="openTab('cleaners')">Cleaners</div>
                <div class="tab" onclick="openTab('assignments')">Assignments</div>
                <div class="tab" onclick="openTab('logs')">Cleaning Logs</div>
                <div class="tab" onclick="openTab('notifications')">Notifications</div>
            </div>
            
            <!-- Cleaners Tab -->
            <div id="cleaners" class="tab-content active">
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Cleaners</h2>
                        <button class="btn btn-primary" onclick="openModal('addCleanerModal')">
                            <i class="fas fa-plus"></i> Add Cleaner
                        </button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Shift</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cleaners as $cleaner): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($cleaner['photo'] ? $cleaner['photo'] : 'https://ui-avatars.com/api/?name='.urlencode($cleaner['name']).'&background=225b25&color=fff') ?>" 
                                             class="table-photo"
                                             alt="<?= htmlspecialchars($cleaner['name']) ?>">
                                    </td>
                                    <td><?= htmlspecialchars($cleaner['name']) ?></td>
                                    <td><?= htmlspecialchars($cleaner['email']) ?></td>
                                    <td>
                                        <span class="status-badge shift-<?= htmlspecialchars($cleaner['shift']) ?>">
                                            <?= ucfirst(htmlspecialchars($cleaner['shift'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($cleaner['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($cleaner['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="openEditCleanerModal(<?= htmlspecialchars($cleaner['id']) ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="confirmDeleteCleaner(<?= htmlspecialchars($cleaner['id']) ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Assignments Tab -->
            <div id="assignments" class="tab-content">
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Assignments</h2>
                        <button class="btn btn-primary" onclick="openModal('assignTaskModal')">
                            <i class="fas fa-plus"></i> Assign Task
                        </button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Cleaner</th>
                                <th>Photo</th>
                                <th>Route</th>
                                <th>Shift</th>
                                <th>Assigned At</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($assignment['cleaner_name']) ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars($assignment['photo'] ? $assignment['photo'] : 'https://ui-avatars.com/api/?name='.urlencode($assignment['cleaner_name']).'&background=225b25&color=fff') ?>" 
                                             class="table-photo"
                                             alt="<?= htmlspecialchars($assignment['cleaner_name']) ?>">
                                    </td>
                                    <td><?= htmlspecialchars($assignment['route_name']) ?></td>
                                    <td>
                                        <span class="status-badge shift-<?= htmlspecialchars($assignment['shift']) ?>">
                                            <?= ucfirst(htmlspecialchars($assignment['shift'])) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($assignment['assigned_at'])) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace('-', '_', htmlspecialchars($assignment['status'])) ?>">
                                            <?= ucfirst(htmlspecialchars($assignment['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="openEditAssignmentModal(<?= $assignment['id'] ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDeleteAssignment(<?= $assignment['id'] ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Cleaning Logs Tab -->
            <div id="logs" class="tab-content">
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Cleaning Logs</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Cleaner</th>
                                <th>Route</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cleaning_logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['cleaner_name']) ?></td>
                                    <td><?= htmlspecialchars($log['route_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace('-', '_', htmlspecialchars($log['status'])) ?>">
                                            <?= ucfirst(htmlspecialchars($log['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(substr($log['notes'], 0, 50)) ?><?= strlen($log['notes']) > 50 ? '...' : '' ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($log['logged_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Notifications Tab -->
            <div id="notifications" class="tab-content">
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Notifications</h2>
                        <button class="btn btn-primary" onclick="openModal('addNotificationModal')">
                            <i class="fas fa-plus"></i> Add Notification
                        </button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td><?= htmlspecialchars($notification['title']) ?></td>
                                    <td><?= htmlspecialchars(substr($notification['message'], 0, 50)) ?>...</td>
                                    <td><?= date('M d, Y H:i', strtotime($notification['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= $notification['is_read'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $notification['is_read'] ? 'Read' : 'Unread' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="openEditNotificationModal(<?= $notification['id'] ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDeleteNotification(<?= $notification['id'] ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Cleaner Modal -->
    <div id="addCleanerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Cleaner</h3>
                <span class="close" onclick="closeModal('addCleanerModal')">&times;</span>
            </div>
            <form action="add_cleaner.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="shift">Shift</label>
                    <select id="shift" name="shift" required>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="evening">Evening</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="photo">Photo</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add Cleaner</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Cleaner Modal -->
    <div id="editCleanerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Cleaner</h3>
                <span class="close" onclick="closeModal('editCleanerModal')">&times;</span>
            </div>
            <form id="editCleanerForm" action="update_cleaner.php" method="post" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label>Current Photo</label>
                    <img id="current_photo" class="cleaner-photo" src="" alt="Cleaner Photo">
                </div>
                <div class="form-group">
                    <label for="edit_name">Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_shift">Shift</label>
                    <select id="edit_shift" name="shift" required>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="evening">Evening</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_photo">Update Photo</label>
                    <input type="file" id="edit_photo" name="photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Cleaner</button>
                <br>
            </form>
        </div>
    </div>
    
    <!-- Assign Task Modal -->
    <div id="assignTaskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign New Task</h3>
                <span class="close" onclick="closeModal('assignTaskModal')">&times;</span>
            </div>
            <form action="assign_task.php" method="post">
                <div class="form-group">
                    <label for="task_cleaner">Cleaner</label>
                    <select id="task_cleaner" name="cleaner_id" required>
                        <option value="">Select Cleaner</option>
                        <?php foreach ($cleaners as $cleaner): ?>
                            <option value="<?= htmlspecialchars($cleaner['id']) ?>"><?= htmlspecialchars($cleaner['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="task_route">Route</label>
                    <select id="task_route" name="route_id" required>
                        <option value="">Select Route</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?= htmlspecialchars($route['id']) ?>"><?= htmlspecialchars($route['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="task_shift">Shift</label>
                    <select id="task_shift" name="shift" required>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="evening">Evening</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="task_date">Date</label>
                    <input type="date" id="task_date" name="date" required>
                </div>
                <button type="submit" class="btn btn-primary">Assign Task</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Assignment Modal -->
    <div id="editAssignmentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Assignment</h3>
            <span class="close" onclick="closeModal('editAssignmentModal')">&times;</span>
        </div>
        <form id="editAssignmentForm" action="update_assignment.php" method="post">
            <input type="hidden" id="edit_assignment_id" name="id">
            <div class="form-group">
                <label for="edit_assignment_cleaner">Cleaner</label>
                <select id="edit_assignment_cleaner" name="cleaner_id" required>
                    <option value="">Select Cleaner</option>
                    <?php foreach ($cleaners as $cleaner): ?>
                        <option value="<?= htmlspecialchars($cleaner['id']) ?>"><?= htmlspecialchars($cleaner['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_assignment_route">Route</label>
                <select id="edit_assignment_route" name="route_id" required>
                    <option value="">Select Route</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= htmlspecialchars($route['id']) ?>"><?= htmlspecialchars($route['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_assignment_shift">Shift</label>
                <select id="edit_assignment_shift" name="shift" required>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="evening">Evening</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_assignment_date">Date</label>
                <input type="date" id="edit_assignment_date" name="date" required>
            </div>
            <div class="form-group">
                <label for="edit_assignment_status">Status</label>
                <select id="edit_assignment_status" name="status" required>
                    <option value="pending">Pending</option>
                    <option value="in-progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="missed">Missed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Assignment</button>
        </form>
    </div>
</div>
    
    <!-- Add Notification Modal -->
    <div id="addNotificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Notification</h3>
            <span class="close" onclick="closeModal('addNotificationModal')">&times;</span>
        </div>
        <form action="add_notification.php" method="post">
            <div class="form-group">
                <label for="notification_title">Title</label>
                <input type="text" id="notification_title" name="title" required>
            </div>
            <div class="form-group">
                <label for="notification_message">Message</label>
                <textarea id="notification_message" name="message" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="notification_cleaner">For Cleaner (optional)</label>
                <select id="notification_cleaner" name="cleaner_id">
                    <option value="">All Cleaners</option>
                    <?php foreach ($cleaners as $cleaner): ?>
                        <option value="<?= htmlspecialchars($cleaner['id']) ?>"><?= htmlspecialchars($cleaner['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="notification_priority">Priority</label>
                <select id="notification_priority" name="priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add Notification</button>
        </form>
    </div>
</div>
    
    <!-- Edit Notification Modal -->
    <div id="editNotificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Notification</h3>
            <span class="close" onclick="closeModal('editNotificationModal')">&times;</span>
        </div>
        <form id="editNotificationForm" action="update_notification.php" method="post">
            <input type="hidden" id="edit_notification_id" name="id">
            <div class="form-group">
                <label for="edit_notification_title">Title</label>
                <input type="text" id="edit_notification_title" name="title" required>
            </div>
            <div class="form-group">
                <label for="edit_notification_message">Message</label>
                <textarea id="edit_notification_message" name="message" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="edit_notification_cleaner">For Cleaner (optional)</label>
                <select id="edit_notification_cleaner" name="cleaner_id">
                    <option value="">All Cleaners</option>
                    <?php foreach ($cleaners as $cleaner): ?>
                        <option value="<?= htmlspecialchars($cleaner['id']) ?>"><?= htmlspecialchars($cleaner['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_notification_priority">Priority</label>
                <select id="edit_notification_priority" name="priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_notification_status">Status</label>
                <select id="edit_notification_status" name="is_read" required>
                    <option value="0">Unread</option>
                    <option value="1">Read</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Notification</button>
        </form>
    </div>
</div>
    
    <script>
    // Tab functionality
    function openTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Deactivate all tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Activate the selected tab and show its content
        document.getElementById(tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }
    
    // Modal functionality
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
    
    // Edit cleaner modal
    function openEditCleanerModal(cleanerId) {
        // Fetch cleaner data via AJAX
        fetch('get_cleaner.php?id=' + cleanerId)
            .then(response => response.json())
            .then(cleaner => {
                if (cleaner) {
                    document.getElementById('edit_id').value = cleaner.id;
                    document.getElementById('edit_name').value = cleaner.name;
                    document.getElementById('edit_email').value = cleaner.email;
                    document.getElementById('edit_shift').value = cleaner.shift;
                    document.getElementById('edit_status').value = cleaner.status;
                    
                    const photoUrl = cleaner.photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(cleaner.name) + '&background=225b25&color=fff';
                    document.getElementById('current_photo').src = photoUrl;
                    
                    openModal('editCleanerModal');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading cleaner data');
            });
    }
    
    // Edit assignment modal
    function openEditAssignmentModal(assignmentId) {
    fetch('get_assignment.php?id=' + assignmentId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(assignment => {
            if (!assignment) {
                throw new Error('No assignment data received');
            }
            
            // Format the date properly
            const assignedDate = new Date(assignment.assigned_at);
            const formattedDate = assignedDate.toISOString().split('T')[0];
            
            document.getElementById('edit_assignment_id').value = assignment.id;
            document.getElementById('edit_assignment_cleaner').value = assignment.cleaner_id;
            document.getElementById('edit_assignment_route').value = assignment.route_id;
            document.getElementById('edit_assignment_shift').value = assignment.shift;
            document.getElementById('edit_assignment_date').value = formattedDate;
            document.getElementById('edit_assignment_status').value = assignment.status;
            
            openModal('editAssignmentModal');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading assignment data: ' + error.message);
        });
}
    
    // Edit notification modal
    function openEditNotificationModal(notificationId) {
    fetch('get_notification.php?id=' + notificationId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(notification => {
            if (!notification) {
                throw new Error('No notification data received');
            }
            
            document.getElementById('edit_notification_id').value = notification.id;
            document.getElementById('edit_notification_title').value = notification.title;
            document.getElementById('edit_notification_message').value = notification.message;
            document.getElementById('edit_notification_cleaner').value = notification.cleaner_id || '';
            document.getElementById('edit_notification_priority').value = notification.priority;
            document.getElementById('edit_notification_status').value = notification.is_read ? '1' : '0';
            
            openModal('editNotificationModal');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading notification data: ' + error.message);
        });
}

// Initialize date fields with proper formatting
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    
    // Set default date for new assignments
    const taskDateField = document.getElementById('task_date');
    if (taskDateField) {
        taskDateField.value = formattedDate;
        taskDateField.min = formattedDate; // Optional: prevent selecting past dates
    }
    
    // Set default date for edit assignment
    const editDateField = document.getElementById('edit_assignment_date');
    if (editDateField && !editDateField.value) {
        editDateField.value = formattedDate;
    }
});

// Form validation for notifications
document.getElementById('addNotificationModal').addEventListener('submit', function(e) {
    const title = document.getElementById('notification_title').value.trim();
    const message = document.getElementById('notification_message').value.trim();
    
    if (!title || !message) {
        e.preventDefault();
        alert('Please fill in all required fields');
    }
});

// Form validation for assignments
document.getElementById('assignTaskModal').addEventListener('submit', function(e) {
    const cleaner = document.getElementById('task_cleaner').value;
    const route = document.getElementById('task_route').value;
    
    if (!cleaner || !route) {
        e.preventDefault();
        alert('Please select both a cleaner and a route');
    }
});
    
    // Confirm delete functions
    function confirmDeleteCleaner(cleanerId) {
        if (confirm('Are you sure you want to delete this cleaner? This action cannot be undone.')) {
            window.location.href = 'cleaner_manager.php?delete_cleaner=' + cleanerId;
        }
    }
    
    function confirmDeleteAssignment(assignmentId) {
        if (confirm('Are you sure you want to delete this assignment?')) {
            window.location.href = 'cleaner_manager.php?delete_assignment=' + assignmentId;
        }
    }
    
    function confirmDeleteNotification(notificationId) {
        if (confirm('Are you sure you want to delete this notification?')) {
            window.location.href = 'cleaner_manager.php?delete_notification=' + notificationId;
        }
    }
    
    // Initialize date field for task assignment
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('task_date').valueAsDate = new Date();
        if (document.getElementById('edit_assignment_date')) {
            document.getElementById('edit_assignment_date').valueAsDate = new Date();
        }
    });
</script>


</body>
</html>