<?php
session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? '',
];

$activeForm = $_SESSION['active_form'] ?? 'login';

session_unset();

function showError($error) {
    return !empty($error) ? "<div class='error-message'><i class='fas fa-exclamation-circle'></i> $error</div>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Road Cleaning | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #225b25;
            --secondary-color: #c1dfc4;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --error-color: #e74c3c;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            display: flex;
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .welcome-section {
            flex: 1;
            background: linear-gradient(rgba(34, 91, 37, 0.9), rgba(34, 91, 37, 0.95));
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .welcome-section h1 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .welcome-section p {
            font-size: 0.9rem;
            margin-bottom: 25px;
            opacity: 0.9;
        }
        
        .features-list {
            list-style: none;
            margin-bottom: 30px;
        }
        
        .features-list li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        
        .features-list i {
            margin-right: 10px;
            font-size: 0.9rem;
        }
        
        .contact-info {
            margin-top: auto;
            font-size: 0.85rem;
        }
        
        .contact-info p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .contact-info i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        .contact-info a {
            color: white;
            text-decoration: none;
        }
        
        .form-section {
            flex: 1;
            padding: 30px;
            max-width: 400px;
        }
        
        .form-box {
            display: none;
        }
        
        .form-box.active {
            display: block;
        }
        
        .form-box h2 {
            color: var(--primary-color);
            font-size: 1.3rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.8rem;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(34, 91, 37, 0.1);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
            font-size: 0.9rem;
        }
        
        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s;
        }
        
        button[type="submit"]:hover {
            background: #1a471c;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 15px;
            font-size: 0.8rem;
            color: #666;
        }
        
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 8px 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .toggle-forms {
            display: none;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
            }
            
            .welcome-section {
                padding: 25px;
                text-align: center;
            }
            
            .form-section {
                padding: 25px;
                max-width: 100%;
            }
            
            .features-list {
                display: none;
            }
            
            .toggle-forms {
                display: flex;
                justify-content: center;
                margin-bottom: 15px;
            }
            
            .toggle-forms button {
                background: none;
                border: none;
                color: var(--primary-color);
                font-size: 0.9rem;
                cursor: pointer;
                padding: 5px 15px;
            }
            
            .toggle-forms button.active {
                font-weight: 600;
                text-decoration: underline;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome to Smart Road Cleaning</h1>
            <p>Efficiently manage and track road cleaning operations across Windhoek with our comprehensive management system.</p>
            
            <ul class="features-list">
                <li><i class="fas fa-check-circle"></i> Real-time tracking of cleaning progress</li>
                <li><i class="fas fa-check-circle"></i> Automated task assignment</li>
                <li><i class="fas fa-check-circle"></i> Detailed performance reports</li>
            </ul>
            
            <div class="contact-info">
                <p><i class="fas fa-phone"></i> +264 61 123 4567</p>
                <p><i class="fas fa-envelope"></i> <a href="mailto:cleaning@windhoek.gov.na">smartroadcleaning@windhoek.na</a></p>
            </div>
        </div>
        
        <!-- Form Section -->
        <div class="form-section">
            
            <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
                <form action="login_register.php" method="post">
                    <h2>Login to Your Account</h2>
                    <?= showError($errors['login']); ?>
                    
                    <div class="form-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" id="login-email" name="email" placeholder="Enter Email Address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <div class="password-container">
                            <input type="password" id="login-password" name="password" placeholder="Enter Password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('login-password', this)"></i>
                        </div>
                    </div>
                    
                    <button type="submit" name="login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                    
                    <div class="form-footer">
                        <p>Don't have an account? <a href="#" onclick="showForm('register-form')">Register here</a></p>
                    </div>
                </form>
            </div>

            <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
                <form action="login_register.php" method="post">
                    <h2>Create New Account</h2>
                    <?= showError($errors['register']); ?>
                    
                    <div class="form-group">
                        <label for="register-name">Full Name</label>
                        <input type="text" id="register-name" name="name" placeholder="Enter Full Name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Email Address</label>
                        <input type="email" id="register-email" name="email" placeholder="Enter Email Address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <div class="password-container">
                            <input type="password" id="register-password" name="password" placeholder="Enter Password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('register-password', this)"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="register-code">Registration Code</label>
                        <input type="text" id="register-code" name="registration_code" placeholder="Enter your registration code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-role">Role</label>
                        <select id="register-role" name="role" required>
                            <option value="" disabled selected>Select role</option>
                            <option value="cleaner">Cleaner</option>
                            <option value="supervisor">Supervisor</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="register">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                    
                    <div class="form-footer">
                        <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        function showForm(formId) {
            // Hide all forms
            document.querySelectorAll('.form-box').forEach(form => {
                form.classList.remove('active');
            });
            
            // Show selected form
            document.getElementById(formId).classList.add('active');
            
            // Update active tab on mobile
            const toggleButtons = document.querySelectorAll('.toggle-forms button');
            if (toggleButtons.length > 0) {
                toggleButtons.forEach(btn => {
                    btn.classList.remove('active');
                });
                event.target.classList.add('active');
            }
        }
    </script>
    <!-- Leaflet JS (for maps) -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <!-- Your custom JS files -->
    <script src="js/map.js"></script>
    <script src="js/notifications.js"></script>
    <script src="js/form-validator.js"></script>
</body>
</html>