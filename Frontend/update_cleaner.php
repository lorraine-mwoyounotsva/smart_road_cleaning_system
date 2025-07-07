<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cleaner_id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $shift = $_POST['shift'];
    $status = $_POST['status'];

    // Validate inputs
    if (empty($name) || empty($email)) {
        $_SESSION['error'] = "Please fill all required fields";
        header("Location: cleaner_manager.php");
        exit();
    }

    // Get current cleaner data
    $stmt = $conn->prepare("SELECT c.*, u.email as user_email FROM cleaners c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->bind_param("i", $cleaner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cleaner = $result->fetch_assoc();
    $stmt->close();

    if (!$cleaner) {
        $_SESSION['error'] = "Cleaner not found";
        header("Location: cleaner_manager.php");
        exit();
    }

    // Check if email is being changed to an existing one
    if ($email !== $cleaner['user_email']) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = "Email already exists";
            $stmt->close();
            header("Location: cleaner_manager.php");
            exit();
        }
        $stmt->close();
    }

    // Handle file upload
    $photo = $cleaner['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/cleaners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Delete old photo if exists
        if ($photo && file_exists($photo)) {
            unlink($photo);
        }
        
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photo = $targetPath;
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update users table
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $cleaner['user_id']);
        $stmt->execute();
        $stmt->close();

        // Update cleaners table
        $stmt = $conn->prepare("UPDATE cleaners SET shift = ?, status = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $shift, $status, $photo, $cleaner_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Cleaner updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error updating cleaner: " . $e->getMessage();
    }

    header("Location: cleaner_manager.php");
    exit();
}
?>