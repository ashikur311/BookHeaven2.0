<?php
// reset_password.php
session_start();
include __DIR__ . '/../db_connection.php';

// Check if user has verified OTP
if (!isset($_SESSION['forgot_user_id']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    $_SESSION['error'] = 'Access denied. Please verify OTP first.';
    header('Location: forgot_password.php');
    exit();
}

$user_id = $_SESSION['forgot_user_id'];

// Handle Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset_pwd'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Check if passwords match
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = 'Passwords do not match. Please try again.';
        }
        // Validate password strength
        elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $new_password)) {
            $_SESSION['error'] = 'Password must be at least 8 characters long, including letters, numbers, and special characters.';
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update in DB
            $update_query = "UPDATE users SET pass = ? WHERE user_id = ?";
            if ($stmt = $conn->prepare($update_query)) {
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    // Fetch the user's email to display in success message
                    $fetch_email_query = "SELECT email FROM users WHERE user_id = ?";
                    if ($f_stmt = $conn->prepare($fetch_email_query)) {
                        $f_stmt->bind_param("i", $user_id);
                        $f_stmt->execute();
                        $f_res = $f_stmt->get_result();
                        if ($f_res->num_rows > 0) {
                            $emailRow = $f_res->fetch_assoc();
                            $user_email = $emailRow['email'];
                        }
                        $f_stmt->close();
                    }

                    // Clear session
                    unset($_SESSION['forgot_user_id']);
                    unset($_SESSION['otp_verified']);

                    $_SESSION['success'] = "Password changed successfully for $user_email. Please log in.";
                    header('Location: authentication.php');
                    exit();
                } else {
                    $_SESSION['error'] = 'Error updating password. Please try again.';
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = 'Database error. Please try again.';
            }
        }
        header('Location: reset_password.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Reset Your Password</title>
    <!-- Include Font Awesome for the eye icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f0f2f5;
            font-family: 'Roboto', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            width: 400px;
            max-width: 90%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 40px 30px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        label {
            text-align: left;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .password-container {
            position: relative;
            margin-bottom: 20px;
        }

        .password-container input[type="password"],
        .password-container input[type="text"] {
            width: 100%;
            padding: 12px 40px 12px 12px;
            /* space for the toggle icon on the right */
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .password-container input[type="password"]:focus,
        .password-container input[type="text"]:focus {
            border-color: #007bff;
            outline: none;
        }

        /* Eye icon */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
        }

        .toggle-password:hover {
            color: #333;
        }

        button[type="submit"] {
            padding: 12px;
            background: #007bff;
            color: #fff;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        button[type="submit"]:hover {
            background: #0056b3;
        }

        .note {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            button[type="submit"] {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Reset Your Password</h2>

        <?php
        // Display success or error messages
        if (isset($_SESSION['success'])) {
            echo "<div class='message success'>" . htmlspecialchars($_SESSION['success']) . "</div>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo "<div class='message error'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
        ?>

        <form method="POST" action="">
            <label for="new_password">New Password:</label>

            <!-- Password container with toggle icon -->
            <div class="password-container">
                <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                <!-- Default to eye-slash (hidden) -->
                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword()"></i>
            </div>

            <label for="confirm_password">Confirm New Password:</label>
            <div class="password-container">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password"
                    required>
                <i class="fas fa-eye-slash toggle-password" onclick="toggleConfirmPassword()"></i>
            </div>

            <button type="submit" name="reset_pwd"><i class="fas fa-key button-icon"></i> Change Password</button>
            <div class="note">
                Password must be at least 8 characters and include letters, numbers, and special characters.
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('new_password');
            const toggleIcon = document.querySelector('.password-container .toggle-password');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }

        function toggleConfirmPassword() {
            const passwordField = document.getElementById('confirm_password');
            const toggleIcon = document.querySelectorAll('.password-container .toggle-password')[1];

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>

</html>