<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "bkh");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
function getUserIP()
{
    return !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
        ? $_SERVER['HTTP_X_FORWARDED_FOR']
        : $_SERVER['REMOTE_ADDR'];
}
// Signup
if (isset($_POST['sign_up'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $address = $conn->real_escape_string($_POST['address']);
    $dob = $_POST['date_of_birth'];
    $contact = $conn->real_escape_string($_POST['contact']);

    $checkEmail = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($checkEmail->num_rows > 0) {
        echo "<script>alert('Email already registered. Try logging in.');</script>";
    } else {
        $conn->query("INSERT INTO users (username, email, pass) VALUES ('$username', '$email', '$password')");
        $user_id = $conn->insert_id;

        $conn->query("INSERT INTO user_info (user_id, birthday, phone, address) VALUES ($user_id, '$dob', '$contact', '$address')");

        echo "<script>alert('Registration successful! You can now log in.');</script>";
    }
}

// Login
if (isset($_POST['sign_in'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.');</script>";
    } else {
        $query = "SELECT user_id, pass, two_step_verification FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $hashed_password = $row['pass'];
            $user_id = $row['user_id'];
            $two_factor_enabled = $row['two_step_verification'];

            if (password_verify($password, $hashed_password)) {
                if ($two_factor_enabled == 1) {
                    // Initiate Two-Factor Authentication (2FA)
                    $otp = rand(100000, 999999);

                    // Insert OTP into the user_otp table
                    $insert_otp_query = "
                        INSERT INTO user_otp (user_id, otp_code, purpose,otp_attempts) 
                        VALUES (?, ?, 'two-factor', 1)
                    ";
                    $otp_stmt = $conn->prepare($insert_otp_query);
                    $otp_stmt->bind_param("is", $user_id, $otp);
                    $otp_stmt->execute();

                    // Store OTP and user information in session
                    $_SESSION['2fa_otp'] = $otp;
                    $_SESSION['2fa_user_id'] = $user_id;
                    $_SESSION['2fa_email'] = $email;

                    // Send OTP via Python script (ensure the Python script path is correct)
                    $python = "python";
                    $scriptPath = "C:/xampp/htdocs/BookHeaven2.0/sendotp.py";

                    $command = escapeshellcmd($python . ' ' . escapeshellarg($scriptPath) . ' '
                        . escapeshellarg($email) . ' '
                        . escapeshellarg($otp));
                    shell_exec($command);

                    echo "<script>window.location.href = 'verify_2fa.php';</script>";
                    exit();
                } else {
                    // Proceed with normal login
                    $_SESSION['user_id'] = $user_id;
                    $ip_address = getUserIP();
                    $update_query = "UPDATE users SET login_count = login_count + 1, last_login = NOW() WHERE user_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $user_id);
                    $update_stmt->execute();
                    $status = 'active';
                    $activity_query = "INSERT INTO user_activities (user_id, login_ip, login_timestamp, logout_time, status)
                                       VALUES (?, ?, current_timestamp(), current_timestamp(), ?)";
                    $activity_stmt = $conn->prepare($activity_query);
                    $activity_stmt->bind_param("iss", $user_id, $ip_address, $status);
                    $activity_stmt->execute();

                    $_SESSION['user_id'] = $user_id;
                    echo "<script>window.location.href = '/BookHeaven2.0/index.php';</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Invalid email or password.');</script>";
            }
        } else {
            echo "<script>alert('Invalid email or password.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <title>Login & Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/BookHeaven2.0/css/authentication.css">
</head>

<body>
    <!-- Background animation elements -->
    <div class="shelf"></div>
    <?php
    for ($i = 0; $i < 15; $i++) {
        echo '<div class="book" style="top: ' . rand(0, 100) . 'vh; left: ' . rand(0, 100) . 'vw; animation-delay: ' . rand(0, 10) . 's; animation-duration: ' . rand(10, 30) . 's;"></div>';
    }
    ?>
    <div class="container" id="container">
        <!-- Sign Up Form -->
        <div class="form-container sign-up">
            <form method="POST" action="">
                <h1>Create Account</h1>
                <span>Use your email for registration</span>
                <input type="text" name="username" placeholder="Name" required />
                <input type="email" name="email" placeholder="Email" required />
                <div class="password-container">
                    <input type="password" name="password" placeholder="Password" required />
                    <i class="fa-solid fa-eye-slash toggle-password"></i>
                </div>
                <input type="text" name="address" placeholder="Address" />
                <input type="date" name="date_of_birth" placeholder="Date of Birth" />
                <input type="text" name="contact" placeholder="Contact Number" />
                <button class="sign-up-btn" type="submit" name="sign_up">Sign Up</button>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in">
            <form method="POST" action="">
                <h1>Sign In</h1>
                <span>Use your email and password</span>
                <input type="email" name="email" placeholder="Email" required />
                <div class="password-container">
                    <input type="password" name="password" placeholder="Password" required />
                    <i class="fa-solid fa-eye-slash toggle-password"></i>
                </div>
                <a href="/BookHeaven2.0/php/forgot_password.php">Forgot Password?</a>
                <button type="submit" name="sign_in">Sign In</button>
            </form>
        </div>

        <!-- Panel Toggle -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of the site's features</p>
                    <button class="hidden" id="login">Log In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Friend!</h1>
                    <p>Register with your personal details to enjoy our services</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function () {
                const passwordField = this.previousElementSibling;
                const isPassword = passwordField.type === 'password';
                passwordField.type = isPassword ? 'text' : 'password';
                this.classList.toggle('fa-eye-slash');
                this.classList.toggle('fa-eye');
            });
        });

        // Toggle between login and signup forms
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        registerBtn.addEventListener('click', () => {
            container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
        });
    </script>
</body>
</html>