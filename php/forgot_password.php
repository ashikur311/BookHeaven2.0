<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bkh");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}  

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_forgot'])) {
    $email = trim($_POST['email']);

    // 1. Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
    } else {
        // 2. Check if email exists in users table
        $query = "SELECT user_id FROM users WHERE email = ?";
        $stmt  = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Email found
            $userRow = $result->fetch_assoc();
            $user_id = $userRow['user_id'];

            // 3. Generate OTP (6-digit code) using random_int for better randomness
            $otp_code = random_int(100000, 999999);

            // 4. Store OTP in user_otp table (ensure this table exists)
            $insert_otp = "INSERT INTO user_otp (user_id, otp_code, otp_time,purpose,otp_attempts) VALUES (?, ?, NOW(), 'password_reset',1)";
            $otp_stmt   = $conn->prepare($insert_otp);
            $otp_stmt->bind_param("is", $user_id, $otp_code);
            if ($otp_stmt->execute()) {
                // 5. Call Python script using shell_exec/exec
                // Ensure the paths are correct and Python is accessible
                $python     = "python"; // If Python is not in PATH, provide the full path e.g., "C:/Python39/python.exe"
                $scriptPath = "C:/xampp/htdocs/BookHeaven2.0/sendotp.py"; // Update this path as needed

                // Build the command with proper escaping
                $command = escapeshellcmd("$python " . escapeshellarg($scriptPath) . " " 
                                        . escapeshellarg($email) . " " 
                                        . escapeshellarg($otp_code));
                
                // Execute the command and capture output for debugging
                $output = shell_exec("$command 2>&1");
                
                // (Optional) Log the output for debugging purposes
                // file_put_contents('otp_log.txt', $output, FILE_APPEND);

                // 6. Save user_id in session for later use in verify_otp.php
                $_SESSION['forgot_user_id'] = $user_id;

                // 7. Set success message
                $_SESSION['success'] = 'OTP has been sent to your email. Please check your inbox/spam folder.';
                header('Location: verify_otp.php');
                exit();
            } else {
                $_SESSION['error'] = 'Failed to generate OTP. Please try again.';
            }
            $otp_stmt->close();
        } else {
            $_SESSION['error'] = 'Email not found in our records.';
        }
        $stmt->close();
    }

    // Redirect back to the same page to display messages
    header('Location: forgot_password.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <!-- Include Google Fonts for better typography -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body Styling */
        body {
            background: #f0f2f5;
            font-family: 'Roboto', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        /* Container Styling */
        .container {
            width: 400px;
            max-width: 90%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            padding: 40px 30px;
            text-align: center;
        }

        /* Heading Styling */
        .container h2 {
            margin-bottom: 20px;
            color: #333;
        }

        /* Message Styling */
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

        /* Form Styling */
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

        input[type="email"] {
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 25px;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
            border-color: #007bff;
            outline: none;
        }

        /* Button Styling */
        button {
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

        button:hover {
            background: #0056b3;
        }

        /* Icon Styling */
        .button-icon {
            margin-right: 8px;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            button {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>

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
            <label for="email"><i class="fas fa-envelope"></i> Enter your email:</label>
            <input type="email" name="email" id="email" placeholder="you@example.com" required>
            <button type="submit" name="submit_forgot"><i class="fas fa-paper-plane button-icon"></i> Send OTP</button>
        </form>
    </div>
</body>
</html>
