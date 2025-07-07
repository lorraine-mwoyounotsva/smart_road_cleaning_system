<?php
session_start();
require_once 'config.php';

if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $registration_code = $_POST['registration_code'];

    // Check if registration code is valid
    $codeCheck = $conn->query("SELECT id FROM users WHERE registration_code = '$registration_code' AND role = '$role'");
    if ($codeCheck->num_rows == 0) {
        $_SESSION['register_error'] = "Invalid registration code for this role!";
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }

    $checkEmail = $conn->query("SELECT email FROM users WHERE email = '$email'");
    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = "Email is already registered!";
        $_SESSION['active_form'] = 'register';
    } else {
        $conn->query("INSERT INTO users(name, email, password, role) VALUES ('$name', '$email', '$password', '$role')");
        // Mark registration code as used (optional)
        $conn->query("UPDATE users SET registration_code = NULL WHERE registration_code = '$registration_code'");
    }

    header("Location: login.php");
    exit();
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'cleaner') {
                header("Location: cleaner_dashboard.php");
            } else {
                header("Location: supervisor_dashboard.php");
            } 
            exit();
        }
    }
    
    $_SESSION['login_error'] = "Invalid email or password!";
    $_SESSION['active_form'] = 'login';
    header("Location: login.php");
    exit();
}
?>