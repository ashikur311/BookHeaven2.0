<?php
session_start();
include 'db.php'; // This includes your PDO connection

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ----- ADMIN LOGIN -----
    if (isset($_POST['admin_login'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        try {
            $query = "SELECT admin_id, username, password, full_name FROM admin WHERE username = :username";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashed_password = $row['password'];
                $admin_id = $row['admin_id'];
                $full_name = $row['full_name'];

                if (password_verify($password, $hashed_password)) {
                    $_SESSION['admin_id'] = $admin_id;
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_full_name'] = $full_name;
                    
                    // Update last login time
                    $update_query = "UPDATE admin SET updated_at = NOW() WHERE admin_id = :admin_id";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->bindParam(':admin_id', $admin_id);
                    $update_stmt->execute();

                    echo "<script>window.location.href = 'admin_dashboard.php';</script>";
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }

    // ----- ADMIN REGISTRATION -----
    if (isset($_POST['admin_register'])) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = sanitizeInput($_POST['full_name']);

        // Validate inputs
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "All fields are required except Full Name.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            try {
                // Check if username or email already exists
                $check_query = "SELECT admin_id FROM admin WHERE username = :username OR email = :email";
                $check_stmt = $pdo->prepare($check_query);
                $check_stmt->bindParam(':username', $username);
                $check_stmt->bindParam(':email', $email);
                $check_stmt->execute();

                if ($check_stmt->rowCount() > 0) {
                    $error = "Username or email already exists.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert_query = "INSERT INTO admin (username, password, email, full_name) VALUES (:username, :password, :email, :full_name)";
                    $insert_stmt = $pdo->prepare($insert_query);
                    $insert_stmt->bindParam(':username', $username);
                    $insert_stmt->bindParam(':password', $hashed_password);
                    $insert_stmt->bindParam(':email', $email);
                    $insert_stmt->bindParam(':full_name', $full_name);
                    
                    if ($insert_stmt->execute()) {
                        $success = "Admin account created successfully! You can now login.";
                        // Clear form
                        $_POST = array();
                    } else {
                        $error = "Error creating account. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #4fc3f7;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .container {
            position: relative;
            width: 900px;
            max-width: 100%;
            min-height: 600px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25), 0 10px 10px rgba(0, 0, 0, 0.22);
            overflow: hidden;
        }
        
        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }
        
        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 2;
        }
        
        .sign-up-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }
        
        .container.right-panel-active .sign-in-container {
            transform: translateX(100%);
        }
        
        .container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: show 0.6s;
        }
        
        @keyframes show {
            0%, 49.99% {
                opacity: 0;
                z-index: 1;
            }
            50%, 100% {
                opacity: 1;
                z-index: 5;
            }
        }
        
        .toggle-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
            border-radius: 0 10px 10px 0;
            z-index: 100;
        }
        
        .container.right-panel-active .toggle-container {
            transform: translateX(-100%);
            border-radius: 10px 0 0 10px;
        }
        
        .toggle {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: #fff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }
        
        .container.right-panel-active .toggle {
            transform: translateX(50%);
        }
        
        .toggle-panel {
            position: absolute;
            width: 50%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 40px;
            text-align: center;
            top: 0;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }
        
        .toggle-left {
            transform: translateX(-200%);
        }
        
        .container.right-panel-active .toggle-left {
            transform: translateX(0);
        }
        
        .toggle-right {
            right: 0;
            transform: translateX(0);
        }
        
        .container.right-panel-active .toggle-right {
            transform: translateX(200%);
        }
        
        form {
            background: #fff;
            display: flex;
            flex-direction: column;
            padding: 0 50px;
            height: 100%;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        h1 {
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        .social-icons {
            margin: 20px 0;
        }
        
        .social-icons a {
            border: 1px solid #ddd;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 5px;
            height: 40px;
            width: 40px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        input {
            background: #eee;
            border: none;
            padding: 12px 15px;
            margin: 8px 0;
            width: 100%;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .password-container {
            position: relative;
            width: 100%;
        }
        
        .password-container input {
            padding-right: 40px;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
        
        button {
            border-radius: 20px;
            border: 1px solid var(--primary-color);
            background: var(--primary-color);
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 20px 0;
            cursor: pointer;
            transition: transform 80ms ease-in, background 0.3s;
        }
        
        button:active {
            transform: scale(0.95);
        }
        
        button:hover {
            background: var(--secondary-color);
        }
        
        button.hidden {
            background: transparent;
            border-color: #fff;
        }
        
        button.hidden:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        p {
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        span {
            font-size: 12px;
            color: #777;
            margin-bottom: 15px;
            display: block;
        }
        
        a {
            color: #333;
            font-size: 14px;
            text-decoration: none;
            margin: 15px 0;
            transition: color 0.3s;
        }
        
        a:hover {
            color: var(--primary-color);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            width: 100%;
            text-align: center;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: var(--danger-color);
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }
        
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: none;
            color: var(--dark-color);
            font-size: 24px;
            cursor: pointer;
            z-index: 1000;
        }
        
        .admin-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--dark-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            z-index: 1000;
        }
        
        @media (max-width: 768px) {
            .container {
                width: 100%;
                height: auto;
                min-height: 100vh;
                border-radius: 0;
            }
            
            .sign-in-container,
            .sign-up-container {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .toggle-container {
                display: none;
            }
            
            .container.right-panel-active .sign-in-container,
            .container.right-panel-active .sign-up-container {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="container" id="container">
        <div class="admin-badge">ADMIN PORTAL</div>
        
        <!-- Admin Sign Up Form -->
        <div class="form-container sign-up-container">
            <form method="POST" action="">
                <h1>Create Admin Account</h1>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php elseif(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <input type="text" name="username" placeholder="Username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <input type="email" name="email" placeholder="Email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <div class="password-container">
                    <input type="password" name="password" placeholder="Password (min 8 chars)" required>
                    <i class="fa-solid fa-eye-slash toggle-password"></i>
                </div>
                <div class="password-container">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <i class="fa-solid fa-eye-slash toggle-password"></i>
                </div>
                <input type="text" name="full_name" placeholder="Full Name (optional)" 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                <button type="submit" name="admin_register">Register</button>
                
            </form>
        </div>
        
        <!-- Admin Sign In Form -->
        <div class="form-container sign-in-container">
            <form method="POST" action="">
                <h1>Admin Sign In</h1>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <input type="text" name="username" placeholder="Username" required>
                <div class="password-container">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fa-solid fa-eye-slash toggle-password"></i>
                </div>
                <button type="submit" name="admin_login">Sign In</button>
            </form>
        </div>
        
        <!-- Panel Toggle (Sign In / Sign Up) -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your admin credentials to access the dashboard</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Admin!</h1>
                    <p>Register a new admin account with secure credentials</p>
                    <button class="hidden" id="register">Register</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');
        const signUpLink = document.getElementById('signUpLink');
        const signInLink = document.getElementById('signInLink');

        registerBtn.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

        signUpLink.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.add("right-panel-active");
        });

        signInLink.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.remove("right-panel-active");
        });

        // Show/Hide Password
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function () {
                const passwordField = this.previousElementSibling || 
                                    this.parentNode.querySelector('input[type="password"]');
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                } else {
                    passwordField.type = 'password';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                }
            });
        });

        // Auto switch to sign-in if there's a success message from registration
        <?php if(isset($success)): ?>
            container.classList.remove("right-panel-active");
        <?php endif; ?>
    </script>
</body>
</html>