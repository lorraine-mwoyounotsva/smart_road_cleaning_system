<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_id = (int)$_POST['id'];
    $cleaner_id = (int)$_POST['cleaner_id'];
    $route_id = (int)$_POST['route_id'];
    $shift = $_POST['shift'];
    $date = $_POST['date'];
    $status = $_POST['status'];
    
    try {
        // Get the original assignment data
        $stmt = $conn->prepare("SELECT cleaner_id, route_id, shift, assigned_at, status FROM assignments WHERE id = ?");
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $original = $result->fetch_assoc();
        $stmt->close();
        
        if (!$original) {
            throw new Exception("Assignment not found");
        }
        
        // Check if the update would create a duplicate assignment
        if ($original['cleaner_id'] != $cleaner_id || $original['shift'] != $shift || date('Y-m-d', strtotime($original['assigned_at'])) != $date) {
            $check_stmt = $conn->prepare("SELECT id FROM assignments WHERE cleaner_id = ? AND shift = ? AND DATE(assigned_at) = ? AND id != ?");
            $check_stmt->bind_param("issi", $cleaner_id, $shift, $date, $assignment_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $_SESSION['error'] = "This cleaner already has an assignment for the selected shift and date.";
                header("Location: cleaner_manager.php");
                exit();
            }
            
            $check_stmt->close();
        }
        
        // Update the assignment
        $stmt = $conn->prepare("UPDATE assignments SET cleaner_id = ?, route_id = ?, shift = ?, assigned_at = ?, status = ? WHERE id = ?");
        $assigned_at = $date . ' ' . ($shift == 'morning' ? '08:00:00' : ($shift == 'afternoon' ? '12:00:00' : '16:00:00'));
        $stmt->bind_param("iisssi", $cleaner_id, $route_id, $shift, $assigned_at, $status, $assignment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Assignment updated successfully!";
            
            // Create a notification if the cleaner was changed or status was updated
            if ($original['cleaner_id'] != $cleaner_id || $original['status'] != $status) {
                $cleaner_stmt = $conn->prepare("SELECT user_id FROM cleaners WHERE id = ?");
                $cleaner_stmt->bind_param("i", $cleaner_id);
                $cleaner_stmt->execute();
                $cleaner_result = $cleaner_stmt->get_result();
                $cleaner = $cleaner_result->fetch_assoc();
                $cleaner_stmt->close();
                
                if ($cleaner) {
                    $route_stmt = $conn->prepare("SELECT name FROM routes WHERE id = ?");
                    $route_stmt->bind_param("i", $route_id);
                    $route_stmt->execute();
                    $route_result = $route_stmt->get_result();
                    $route = $route_result->fetch_assoc();
                    $route_stmt->close();
                    
                    $title = "Assignment Updated";
                    $message = "Your assignment for " . $route['name'] . " during the " . $shift . " shift on " . date('M d, Y', strtotime($date)) . " has been updated. New status: " . $status;
                    
                    $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, priority) VALUES (?, ?, ?, 'medium')");
                    $notification_stmt->bind_param("iss", $cleaner['user_id'], $title, $message);
                    $notification_stmt->execute();
                    $notification_stmt->close();
                }
            }
        } else {
            $_SESSION['error'] = "Error updating assignment: " . $conn->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: cleaner_manager.php");
    exit();
}
?>