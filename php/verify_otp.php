<?php
// verify_otp.php
session_start();
include __DIR__ . '/../db_connection.php';

// If there's no user ID from forgot_password flow, redirect or show error
if (!isset($_SESSION['forgot_user_id'])) {
    $_SESSION['error'] = 'No session found. Please start from the Forgot Password page.';
    header('Location: forgot_password.php');
    exit();
}

$user_id = $_SESSION['forgot_user_id'];

// Handle OTP Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $entered_otp = trim($_POST['otp_code']);

        // Validate OTP format
        if (!preg_match('/^\d{6}$/', $entered_otp)) {
            $_SESSION['error'] = 'Invalid OTP format. Please enter a 6-digit OTP.';
            header('Location: verify_otp.php');
            exit();
        }

        // Retrieve the most recent OTP from user_otp for this user
        $query = "SELECT otp_code, otp_time FROM user_otp 
                  WHERE user_id = ? 
                  ORDER BY id DESC 
                  LIMIT 1";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $otp_code = $row['otp_code'];
                $otp_time = $row['otp_time'];

                // Check if OTP is expired (15 minutes)
                $otpTimestamp = strtotime($otp_time);
                if (time() - $otpTimestamp > 900) { // 900 seconds = 15 minutes
                    $_SESSION['error'] = 'OTP has expired. Please request a new one.';
                } else {
                    // Check if entered OTP matches
                    if ($entered_otp === $otp_code) {
                        // Prevent session fixation
                        session_regenerate_id(true);
                        $_SESSION['otp_verified'] = true;
                        header('Location: reset_password.php');
                        exit();
                    } else {
                        $_SESSION['error'] = 'Invalid OTP. Please try again.';
                    }
                }
            } else {
                $_SESSION['error'] = 'No OTP found. Please request a new one.';
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = 'Database error. Please try again.';
        }
    }

    // Redirect back to verify_otp.php to display messages
    header('Location: verify_otp.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Basic Reset */
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
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
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
        input[type="text"] {
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 25px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus {
            border-color: #007bff;
            outline: none;
        }
        /* Button Styling */
        button {
            padding: 12px;
            background: #28a745;
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
            background: #218838;
        }
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
        <h2>Verify OTP</h2>

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
            <label for="otp_code"><i class="fas fa-key"></i> Enter OTP:</label>
            <input type="text" name="otp_code" id="otp_code" placeholder="Enter the 6-digit OTP" required pattern="\d{6}" title="Please enter a 6-digit OTP">
            <button type="submit" name="verify_otp"><i class="fas fa-check button-icon"></i> Verify OTP</button>
        </form>
    </div>
</body>
</html>
