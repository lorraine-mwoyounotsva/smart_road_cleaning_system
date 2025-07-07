<?php
session_start();
require_once 'config.php';

// Authorization check
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

// Handle CRUD operations for routes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_route'])) {
        // Add new route with proper validation
        $name = $conn->real_escape_string(trim($_POST['name']));
        $coordinates = trim($_POST['coordinates']);
        $priority = $conn->real_escape_string($_POST['priority']);
        $estimated_time = (int)$_POST['estimated_time'];
        
        // Validate coordinates format
        $coordinates_valid = false;
        $coordinatesArray = [];
        try {
            $coordinatesArray = json_decode($coordinates, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($coordinatesArray) && !empty($coordinatesArray)) {
                $coordinates_valid = true;
                $coordinates = $conn->real_escape_string(json_encode($coordinatesArray));
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Invalid coordinates format: " . $e->getMessage();
            header("Location: route_manager.php");
            exit();
        }
        
        if (!$coordinates_valid) {
            $_SESSION['error'] = "Invalid coordinates format";
            header("Location: route_manager.php");
            exit();
        }
        
        $sql = "INSERT INTO routes (name, coordinates, priority, estimated_time) 
                VALUES ('$name', '$coordinates', '$priority', $estimated_time)";
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Route added successfully!";
        } else {
            $_SESSION['error'] = "Error adding route: " . $conn->error;
        }
        header("Location: route_manager.php");
        exit();
        
    } elseif (isset($_POST['update_route'])) {
        // Update existing route
        $id = (int)$_POST['route_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $coordinates = $conn->real_escape_string($_POST['coordinates']);
        $priority = $conn->real_escape_string($_POST['priority']);
        $estimated_time = (int)$_POST['estimated_time'];
        
        $sql = "UPDATE routes SET 
                name = '$name', 
                coordinates = '$coordinates', 
                priority = '$priority', 
                estimated_time = $estimated_time 
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Route updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating route: " . $conn->error;
        }
        header("Location: route_manager.php");
        exit();
        
    } elseif (isset($_POST['delete_route'])) {
        // Delete route
        $id = (int)$_POST['route_id'];
        $sql = "DELETE FROM routes WHERE id = $id";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Route deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting route: " . $conn->error;
        }
        header("Location: route_manager.php");
        exit();
    }
}

// Get all routes
$routes = $conn->query("SELECT * FROM routes ORDER BY name");

// Get cleaning logs for routes
$cleaning_logs = [];
$result = $conn->query("
    SELECT a.*, r.name as route_name, u.name as cleaner_name 
    FROM assignments a
    JOIN routes r ON a.route_id = r.id
    JOIN cleaners c ON a.cleaner_id = c.id
    JOIN users u ON c.user_id = u.id
    ORDER BY a.assigned_at DESC
");
if ($result) {
    $cleaning_logs = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Manager</title>
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            background: #225b25;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #225b25;
        }
        
        .btn-secondary {
            background: #3498db;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .form-container {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            font-family: monospace;
        }
        
        .priority-high {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .priority-medium {
            color: #f39c12;
            font-weight: bold;
        }
        
        .priority-low {
            color: #2ecc71;
            font-weight: bold;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
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
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-title {
            margin: 0;
            font-size: 20px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        /* Enhanced Modal Form Styles */
        .modal form {
            margin: 0;
            width: 100%;
        }
        
        .form-container {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #225b25;
            outline: none;
        }
        
        .form-group textarea {
            min-height: 100px;
            font-family: monospace;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        /* Add this to make the modal more responsive */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                padding: 15px;
            }
        }
        
        /* Responsive table */
        .table-responsive {
            overflow-x: auto;
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
        
        /* Map container */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
        }

        /* Add button edits */
.modal form {
    margin: 0;
    width: 100%;
}

.form-container {
    margin-top: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    min-height: 100px;
    font-family: monospace;
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
                    <li><a href="route_manager.php" class="active"><i class="fas fa-route"></i> Route Manager</a></li>
                    <li><a href="cleaner_manager.php"><i class="fas fa-users"></i> Cleaner Manager</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Route Management</h1>
                <div class="user-info">
                    <span><?= $_SESSION['name'] ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=225b25&color=fff" alt="User">
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Routes</h2>
                    <button class="btn btn-primary" onclick="openAddRouteModal()">
                        <i class="fas fa-plus"></i> Add New Route
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Priority</th>
                                <th>Est. Time</th>
                                <th>Coordinates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($route = $routes->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($route['name']) ?></td>
                                    <td class="priority-<?= $route['priority'] ?>">
                                        <?= ucfirst($route['priority']) ?>
                                    </td>
                                    <td><?= $route['estimated_time'] ?> mins</td>
                                    <td>
                                        <small><?= substr(htmlspecialchars($route['coordinates']), 0, 30) ?>...</small>
                                    </td>
                                    <td>
                                        <button class="btn btn-secondary" 
                                                onclick="openEditRouteModal(
                                                    <?= $route['id'] ?>,
                                                    '<?= htmlspecialchars($route['name'], ENT_QUOTES) ?>',
                                                    '<?= htmlspecialchars($route['priority'], ENT_QUOTES) ?>',
                                                    <?= $route['estimated_time'] ?>,
                                                    `<?= htmlspecialchars($route['coordinates'], ENT_QUOTES) ?>`
                                                )">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="route_id" value="<?= $route['id'] ?>">
                                            <button type="submit" name="delete_route" class="btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this route?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            

            
            <!-- Add Route Modal -->
            <div id="addRouteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Route</h3>
                <button type="button" class="close-modal" onclick="closeAddRouteModal()">&times;</button>
            </div>
            <form method="POST" action="route_manager.php" class="form-container" id="addRouteForm">
                <div class="form-group">
                    <label for="add_name">Route Name</label>
                    <input type="text" id="add_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="add_coordinates">Coordinates (JSON format)</label>
                    <textarea id="add_coordinates" name="coordinates" required></textarea>
                    <small>Example: [[-22.5609,17.0658],[-22.5610,17.0659]]</small>
                    <div id="coordinates-error" class="error-message">Invalid JSON format</div>
                </div>
                
                <div class="form-group">
                    <label for="add_priority">Priority</label>
                    <select id="add_priority" name="priority" required>
                        <option value="high">High</option>
                        <option value="medium" selected>Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="add_estimated_time">Estimated Time (minutes)</label>
                    <input type="number" id="add_estimated_time" name="estimated_time" min="1" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddRouteModal()">Cancel</button>
                    <button type="submit" name="add_route" class="btn btn-primary" id="submitRouteBtn">Add Route</button>
                </div>
            </form>
        </div>
    </div>
            
            <!-- Edit Route Modal -->
            <div id="editRouteModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Edit Route</h3>
                        <button class="close-modal" onclick="closeEditRouteModal()">&times;</button>
                    </div>
                    <form method="POST" class="form-container">
                        <input type="hidden" id="edit_route_id" name="route_id">
                        <div class="form-group">
                            <label for="edit_name">Route Name</label>
                            <input type="text" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_coordinates">Coordinates (JSON format)</label>
                            <textarea id="edit_coordinates" name="coordinates" required></textarea>
                            <small>Example: [[-22.5609,17.0658],[-22.5610,17.0659]]</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_priority">Priority</label>
                            <select id="edit_priority" name="priority" required>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_estimated_time">Estimated Time (minutes)</label>
                            <input type="number" id="edit_estimated_time" name="estimated_time" min="1" required>
                        </div>
                        
                        <div class="form-group" style="text-align: right;">
                            <button type="button" class="btn btn-secondary" onclick="closeEditRouteModal()">Cancel</button>
                            <button type="submit" name="update_route" class="btn btn-primary">Update Route</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([-22.5609, 17.0658], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Load routes and display on map
        <?php 
        $routes_result = $conn->query("SELECT * FROM routes");
        while($route = $routes_result->fetch_assoc()): 
        ?>
            try {
                const coords = JSON.parse('<?= addslashes($route['coordinates']) ?>');
                const polyline = L.polyline(coords, {
                    color: getPriorityColor('<?= $route['priority'] ?>'),
                    weight: 5,
                    opacity: 0.7
                }).addTo(map);
                
                polyline.bindPopup(`
                    <b><?= addslashes($route['name']) ?></b><br>
                    Priority: <?= ucfirst($route['priority']) ?><br>
                    Est. Time: <?= $route['estimated_time'] ?> mins
                `);
            } catch (e) {
                console.error('Error parsing coordinates for route <?= $route['id'] ?>:', e);
            }
        <?php endwhile; ?>
        
        // Fit map to show all routes
        if (<?= $routes->num_rows ?>) {
            const bounds = [];
            <?php 
            $routes_result = $conn->query("SELECT coordinates FROM routes");
            while($route = $routes_result->fetch_assoc()): 
            ?>
                try {
                    const coords = JSON.parse('<?= addslashes($route['coordinates']) ?>');
                    bounds.push(...coords);
                } catch (e) {}
            <?php endwhile; ?>
            
            if (bounds.length > 0) {
                map.fitBounds(bounds);
            }
        }
        
        function getPriorityColor(priority) {
            switch(priority) {
                case 'high': return '#e74c3c';
                case 'medium': return '#f39c12';
                case 'low': return '#2ecc71';
                default: return '#3498db';
            }
        }
        
        // Modal functions
        function openAddRouteModal() {
            document.getElementById('addRouteModal').style.display = 'flex';
            // Clear form when opening
            document.getElementById('add_name').value = '';
            document.getElementById('add_coordinates').value = '';
            document.getElementById('add_priority').value = 'medium';
            document.getElementById('add_estimated_time').value = '';
        }

        function closeAddRouteModal() {
            document.getElementById('addRouteModal').style.display = 'none';
        }

        function openEditRouteModal(id, name, priority, estimatedTime, coordinates) {
            document.getElementById('edit_route_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_priority').value = priority;
            document.getElementById('edit_estimated_time').value = estimatedTime;
            document.getElementById('edit_coordinates').value = coordinates;
            document.getElementById('editRouteModal').style.display = 'flex';
        }

        function closeEditRouteModal() {
            document.getElementById('editRouteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        // Enhanced Modal Functions with Validation
        function openAddRouteModal() {
            document.getElementById('addRouteModal').style.display = 'flex';
            // Clear form and errors when opening
            document.getElementById('addRouteForm').reset();
            document.getElementById('coordinates-error').style.display = 'none';
        }

        function closeAddRouteModal() {
            document.getElementById('addRouteModal').style.display = 'none';
        }

        // Validate coordinates format
        function validateCoordinates(coordinates) {
            try {
                const parsed = JSON.parse(coordinates);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    return true;
                }
                return false;
            } catch (e) {
                return false;
            }
        }

        // Add event listener for form submission
        document.getElementById('addRouteForm').addEventListener('submit', function(e) {
            const coordinates = document.getElementById('add_coordinates').value;
            const errorElement = document.getElementById('coordinates-error');
            
            if (!validateCoordinates(coordinates)) {
                e.preventDefault();
                errorElement.style.display = 'block';
                document.getElementById('add_coordinates').focus();
            } else {
                errorElement.style.display = 'none';
                // Form will submit normally if validation passes
            }
        });

        // Real-time coordinates validation
        document.getElementById('add_coordinates').addEventListener('input', function() {
            const coordinates = this.value;
            const errorElement = document.getElementById('coordinates-error');
            
            if (coordinates && !validateCoordinates(coordinates)) {
                errorElement.style.display = 'block';
            } else {
                errorElement.style.display = 'none';
            }
        });

    </script>
</body>
</html>