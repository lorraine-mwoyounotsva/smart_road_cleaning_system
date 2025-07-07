
<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cleaner_id = $_POST['cleaner_id'];
    $route_id = $_POST['route_id'];
    $shift = $_POST['shift'];    // new field from form
    $date = $_POST['date'];      // new field from form
    $assigned_by = $_SESSION['user_id'];

    // Check if cleaner already has an active assignment (pending or in-progress)
    $stmtCheck = $conn->prepare("SELECT id FROM assignments WHERE cleaner_id = ? AND status IN ('pending', 'in-progress')");
    $stmtCheck->bind_param('i', $cleaner_id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $_SESSION['error'] = "This cleaner already has an active assignment!";
        $stmtCheck->close();
        header("Location: supervisor_dashboard.php");
        exit();
    }
    $stmtCheck->close();

    // Assign the task using prepared statement
    $stmtInsert = $conn->prepare("INSERT INTO assignments (cleaner_id, route_id, assigned_by, status, shift, date) VALUES (?, ?, ?, 'pending', ?, ?)");
    $stmtInsert->bind_param('iiiss', $cleaner_id, $route_id, $assigned_by, $shift, $date);

    if ($stmtInsert->execute()) {
        // Create notification for cleaner
        $stmtCleaner = $conn->prepare("SELECT user_id FROM cleaners WHERE id = ?");
        $stmtCleaner->bind_param('i', $cleaner_id);
        $stmtCleaner->execute();
        $resultCleaner = $stmtCleaner->get_result();
        $cleaner = $resultCleaner->fetch_assoc();
        $stmtCleaner->close();

        $stmtRoute = $conn->prepare("SELECT name FROM routes WHERE id = ?");
        $stmtRoute->bind_param('i', $route_id);
        $stmtRoute->execute();
        $resultRoute = $stmtRoute->get_result();
        $route = $resultRoute->fetch_assoc();
        $stmtRoute->close();

        $title = "New Assignment";
        $message = "You have been assigned to clean: " . $route['name'];

        $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'assignment')");
        $stmtNotif->bind_param('iss', $cleaner['user_id'], $title, $message);
        $stmtNotif->execute();
        $stmtNotif->close();

        $_SESSION['success'] = "Task assigned successfully!";
    } else {
        $_SESSION['error'] = "Error assigning task: " . $conn->error;
    }

    $stmtInsert->close();
    header("Location: supervisor_dashboard.php");
    exit();
}
?>