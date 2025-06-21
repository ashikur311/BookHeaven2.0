<?php
session_start();
include __DIR__ . '/../db_connection.php';

// If no OTP in session, prompt user to sign in again
if (!isset($_SESSION['2fa_otp']) || !isset($_SESSION['2fa_user_id'])) {
    echo "<script>alert('No OTP session found. Please sign in again.'); window.location.href='/BookHeaven2.0/php/authentication.php';</script>";
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'];
    $session_otp = $_SESSION['2fa_otp'];
    $user_id = $_SESSION['2fa_user_id'];
    if ($entered_otp == $session_otp) {
        unset($_SESSION['2fa_otp']);

        // Mark user as logged in
        $_SESSION['user_id'] = $user_id;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        // Update login count and last login
        $update_query = "
            UPDATE users 
            SET login_count = login_count + 1, last_login = NOW() 
            WHERE user_id = ?
        ";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $user_id);
        $update_stmt->execute();

        // Insert into user_activities
        $status = 'active';
        $activity_query = "
            INSERT INTO user_activities (user_id,login_ip, login_timestamp, logout_time, status)
            VALUES (?, ?, current_timestamp(), current_timestamp(), ?)
        ";
        $activity_stmt = $conn->prepare($activity_query);
        $activity_stmt->bind_param("iss", $user_id, $ip_address, $status);
        $activity_stmt->execute();

        // Redirect to dashboard
        echo "<script>window.location.href = '/BookHeaven2.0/index.php';</script>";
        exit();
    } else {
        // Invalid OTP: Check attempts
        $otp_attempts_query = "SELECT otp_attempts FROM user_otp WHERE user_id = ? AND otp_code = ? AND purpose = 'two-factor'";
        $otp_attempts_stmt = $conn->prepare($otp_attempts_query);
        $otp_attempts_stmt->bind_param("is", $user_id, $entered_otp);
        $otp_attempts_stmt->execute();
        $otp_attempts_result = $otp_attempts_stmt->get_result();

        if ($otp_attempts_result->num_rows > 0) {
            $otp_data = $otp_attempts_result->fetch_assoc();
            $attempts = $otp_data['otp_attempts'];

            if ($attempts >= 3) {
                echo "<script>alert('Maximum attempts reached. Please request a new OTP.');</script>";
            } else {
                // Increment OTP attempts
                $update_attempts_query = "UPDATE user_otp SET otp_attempts = otp_attempts + 1 WHERE user_id = ? AND otp_code = ?";
                $update_attempts_stmt = $conn->prepare($update_attempts_query);
                $update_attempts_stmt->bind_param("is", $user_id, $entered_otp);
                $update_attempts_stmt->execute();
                echo "<script>alert('Invalid OTP. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Invalid OTP. Please try again.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4 shadow" style="max-width: 400px; width: 100%;">
            <h1 class="text-center mb-4">Verify OTP</h1>
            <p class="text-center">Enter the 6-digit OTP sent to your email</p>
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="otp" class="form-label">OTP</label>
                    <input type="text" class="form-control" id="otp" name="otp" placeholder="6-digit OTP" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
