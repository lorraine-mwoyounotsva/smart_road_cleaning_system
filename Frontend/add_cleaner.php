<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $shift = $_POST['shift'];
    $status = $_POST['status'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($_POST['password'])) {
        $_SESSION['error'] = "Please fill all required fields";
        header("Location: cleaner_manager.php");
        exit();
    }

    // Check if email already exists
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

    // Handle file upload
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/cleaners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
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
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'cleaner')");
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute();
        $user_id = $conn->insert_id;
        $stmt->close();

        // Insert into cleaners table
        $stmt = $conn->prepare("INSERT INTO cleaners (user_id, shift, status, photo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $shift, $status, $photo);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Cleaner added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error adding cleaner: " . $e->getMessage();
    }

    header("Location: cleaner_manager.php");
    exit();
}
?>