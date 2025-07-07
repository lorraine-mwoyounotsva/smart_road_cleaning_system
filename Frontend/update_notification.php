<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notification_id = (int)$_POST['id'];
    $title = $_POST['title'];
    $message = $_POST['message'];
    $cleaner_id = !empty($_POST['cleaner_id']) ? (int)$_POST['cleaner_id'] : null;
    $priority = $_POST['priority'];
    $is_read = (int)$_POST['is_read'];
    
    try {
        // Get the original notification to check if we need to update the cleaner
        $stmt = $conn->prepare("SELECT user_id FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $notification_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $original = $result->fetch_assoc();
        $stmt->close();
        
        if (!$original) {
            throw new Exception("Notification not found");
        }
        
        $user_id = $original['user_id'];
        
        // If a specific cleaner was selected, update the user_id
        if ($cleaner_id) {
            $stmt = $conn->prepare("SELECT user_id FROM cleaners WHERE id = ?");
            $stmt->bind_param("i", $cleaner_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cleaner = $result->fetch_assoc();
            $stmt->close();
            
            if ($cleaner) {
                $user_id = $cleaner['user_id'];
            }
        }
        
        // Update the notification
        $stmt = $conn->prepare("UPDATE notifications SET user_id = ?, title = ?, message = ?, priority = ?, is_read = ? WHERE id = ?");
        $stmt->bind_param("isssii", $user_id, $title, $message, $priority, $is_read, $notification_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Notification updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating notification: " . $conn->error;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: cleaner_manager.php");
    exit();
}
?>