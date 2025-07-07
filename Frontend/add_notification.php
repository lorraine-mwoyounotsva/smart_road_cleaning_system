<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $cleaner_id = !empty($_POST['cleaner_id']) ? (int)$_POST['cleaner_id'] : null;
    $priority = $_POST['priority'];
    
    try {
        if ($cleaner_id) {
            // Send to specific cleaner
            $stmt = $conn->prepare("SELECT user_id FROM cleaners WHERE id = ?");
            $stmt->bind_param("i", $cleaner_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cleaner = $result->fetch_assoc();
            $stmt->close();
            
            if ($cleaner) {
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, priority) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $cleaner['user_id'], $title, $message, $priority);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            // Send to all cleaners
            $cleaners = $conn->query("SELECT user_id FROM cleaners");
            while ($cleaner = $cleaners->fetch_assoc()) {
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, priority) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $cleaner['user_id'], $title, $message, $priority);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        $_SESSION['success'] = "Notification(s) sent successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error sending notification: " . $e->getMessage();
    }
    
    header("Location: cleaner_manager.php");
    exit();
}
?>