<?php
// bkash_payment.php

session_start();
include_once("../db_connection.php");
// Enable MySQLi error reporting for debugging (disable in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1) Check if `subscription_order_id` is provided
if (!isset($_GET['subscription_order_id'])) {
    die("No subscription_order_id provided.");
}
$subscription_order_id = intval($_GET['subscription_order_id']);

// 2) Fetch subscription order info (user_id, subscription_plan_id, amount, invoice_number, etc.)
$sql = "SELECT user_id, subscription_plan_id, amount, invoice_number FROM subscription_orders WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subscription_order_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows < 1) {
    die("Invalid subscription_order_id or order not found.");
}
$orderRow   = $res->fetch_assoc();
$user_id    = $orderRow['user_id'];
$subscription_plan_id = $orderRow['subscription_plan_id'];
$amount     = $orderRow['amount'];
$invoiceNo  = $orderRow['invoice_number'];
$stmt->close();

// 3) Fetch user's name & email
$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt= $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows < 1) {
    die("User not found for this order.");
}
$userRow   = $res->fetch_assoc();
$userName  = $userRow['username'];
$userEmail = $userRow['email'];
$stmt->close();

// 4) Fetch subscription plan details
$sql = "SELECT name, duration FROM subscription_plans WHERE id = ?";
$stmt= $conn->prepare($sql);
$stmt->bind_param("i", $subscription_plan_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows < 1) {
    die("Subscription plan not found.");
}
$planRow = $res->fetch_assoc();
$planName = $planRow['name'];
$planDuration = intval($planRow['duration']); // Assuming duration is in months
$stmt->close();

// 5) Manage a 2-step flow: step=1 => phone, step=2 => OTP
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

$error   = "";
$success = "";

// ---------- STEP 1: Enter bKash Number ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bkash_number']) && $step == 1) {
    $bkashNumber = trim($_POST['bkash_number']);
    if (empty($bkashNumber)) {
        $error = "Please enter your bKash number.";
    } else {
        // Validate bKash number format (basic validation)
        if (!preg_match('/^01\d{9}$/', $bkashNumber)) {
            $error = "Invalid bKash number format. It should start with '01' followed by 9 digits.";
        } else {
            // Store phone in session
            $_SESSION['bkash_number'] = $bkashNumber;

            // Generate a 6-digit OTP
            $otp = rand(100000, 999999);

            // Insert into user_otp
            $insertOTP = "INSERT INTO user_otp (user_id, otp_code, otp_time, purpose, otp_attempts)
                          VALUES (?, ?, NOW(), 'bkash_subscription', 0)";
            $stmt = $conn->prepare($insertOTP);
            $stmt->bind_param("is", $user_id, $otp);
            if (!$stmt->execute()) {
                $error = "Failed to generate OTP: " . $stmt->error;
            } else {
                // Send OTP via Python script
                $python     = "python"; // Ensure 'python' is the correct command
                $scriptPath = "C:/xampp/htdocs/BookHeaven/sendotp.py"; // **Update the path accordingly**

                // Ensure the Python script is executable and the path is correct
                $command = escapeshellcmd($python . ' ' . escapeshellarg($scriptPath) . ' '
                          . escapeshellarg($userEmail) . ' '
                          . escapeshellarg($otp));
                shell_exec($command);

                // Go to step=2
                header("Location: bkash-payment.php?subscription_order_id=$subscription_order_id&step=2");
                exit;
            }
            $stmt->close();
        }
    }
}

// ---------- STEP 2: Enter OTP ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_code']) && $step == 2) {
    $enteredOtp = trim($_POST['otp_code']);
    if (empty($enteredOtp)) {
        $error = "Please enter the OTP.";
    } else {
        // Validate OTP format (basic validation)
        if (!preg_match('/^\d{6}$/', $enteredOtp)) {
            $error = "Invalid OTP format. It should be a 6-digit number.";
        } else {
            // Find the latest 'bkash_subscription' OTP
            $sel = "SELECT id, otp_code, otp_time, otp_attempts FROM user_otp
                    WHERE user_id = ? AND purpose='bkash_subscription'
                    ORDER BY id DESC LIMIT 1";
            $stmt= $conn->prepare($sel);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $otpRow= $res->fetch_assoc();
            $stmt->close();

            if (!$otpRow) {
                $error = "No OTP found. Please try again.";
            } else {
                // Check OTP expiration (e.g., valid for 10 minutes)
                $otpTime = strtotime($otpRow['otp_time']);
                $currentTime = time();
                if (($currentTime - $otpTime) > 600) { // 600 seconds = 10 minutes
                    $error = "OTP has expired. Please request a new one.";
                } else {
                    // Check OTP attempts
                    if ($otpRow['otp_attempts'] >= 5) {
                        $error = "Maximum OTP attempts exceeded. Please request a new OTP.";
                    } else {
                        $actualOTP = $otpRow['otp_code'];
                        if ($enteredOtp === $actualOTP) {
                            // Payment success
                            $bkashNumber = $_SESSION['bkash_number'] ?? 'Unknown';
                            $success = "Payment Successful! ৳" . number_format($amount, 2) . " has been deducted from your bKash: {$bkashNumber}";

                            // Calculate issue_date and expire_date based on plan duration
                            $issue_date = date('Y-m-d');
                            $expire_date = date('Y-m-d', strtotime("+$planDuration months", strtotime($issue_date)));

                            // Update subscription_orders: set status='confirm', payment_status='complete', etc.
                            $updateOrder = "
                               UPDATE subscription_orders
                                  SET status='confirm',
                                      payment_status='complete',
                                      issue_date=?,
                                      expire_date=?,
                                      payment_method='online',
                                      payment_details=?
                                WHERE id=?
                            ";
                            // You can add more details as needed, e.g., transaction ID
                            $paymentDetails = "bKash Payment from number: {$bkashNumber}.";
                            $stmtUO = $conn->prepare($updateOrder);
                            $stmtUO->bind_param("sssi", $issue_date, $expire_date, $paymentDetails, $subscription_order_id);
                            $stmtUO->execute();
                            $stmtUO->close();

                            // Optionally, update user subscription status if needed
                            // Example:
                            // $updateUser = "UPDATE users SET subscription_status='active', subscription_expire=? WHERE id=?";
                            // $stmtU = $conn->prepare($updateUser);
                            // $stmtU->bind_param("si", $expire_date, $user_id);
                            // $stmtU->execute();
                            // $stmtU->close();

                            // Clear phone from session
                            unset($_SESSION['bkash_number']);
                        } else {
                            // Increment OTP attempts
                            $updateAttempts = "UPDATE user_otp SET otp_attempts = otp_attempts + 1 WHERE id = ?";
                            $stmtUA = $conn->prepare($updateAttempts);
                            $stmtUA->bind_param("i", $otpRow['id']);
                            $stmtUA->execute();
                            $stmtUA->close();

                            $error = "Invalid OTP. Please try again.";
                        }
                    }
                }
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>bKash Subscription Payment</title>
  <link rel="stylesheet" href="/BookHeaven2.0/css/bkash-payment.css">
</head>
<body>
<div class="bkash-popup">
    <!-- Header -->
    <div class="bkash-header">
        <img src="/BookHeaven/asset/bkash.png" alt="bKash"> <!-- Update the path if necessary -->
    </div>
    <hr class="bkash-header-hr">

    <!-- Body -->
    <div class="bkash-body">
        <div class="merchant-info">
            <span><?php echo htmlspecialchars($userName); ?></span><br>
            Invoice:
            <span class="invoice-label"><?php echo htmlspecialchars($invoiceNo); ?></span>
        </div>
        <div class="payment-amount">
            Your Payment Amount: ৳<?php echo number_format($amount, 2); ?>
        </div>

        <!-- Show any messages -->
        <?php if (!empty($error)): ?>
          <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Step 1: Enter Phone -->
        <?php if (empty($success) && $step == 1): ?>
          <form id="step1Form" method="POST" action="?subscription_order_id=<?php echo $subscription_order_id; ?>&step=1">
            <div class="bkash-input">
              <label>Your bKash Account number</label>
              <input type="text" name="bkash_number" placeholder="e.g. 01XXXXXXXXX" required />
            </div>
            <div class="terms-text">
              By clicking on Confirm, you are agreeing to the
              <a href="#" target="_blank">terms & conditions</a>
            </div>
          </form>
        <?php endif; ?>

        <!-- Step 2: Enter OTP -->
        <?php if (empty($success) && $step == 2): ?>
          <form id="step2Form" method="POST" action="?subscription_order_id=<?php echo $subscription_order_id; ?>&step=2">
            <div class="bkash-input">
              <label>Enter the OTP we sent to your email</label>
              <input type="text" name="otp_code" placeholder="6-digit OTP" required />
            </div>
          </form>
        <?php endif; ?>

        <!-- If $success is set, show "Thank you" message. Then auto-redirect home. -->
        <?php if (!empty($success)): ?>
          <p style="margin-top:15px; font-weight:bold;">
            Thank you for your payment!
          </p>
          <!-- We'll auto-redirect in JavaScript below -->
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="bkash-footer">
      <div class="footer-buttons">
        <!-- Cancel Always -->
        <button onclick="closePopup()">Cancel</button>

        <?php if (empty($success) && $step == 1): ?>
          <!-- Step 1: Send OTP -->
          <button type="submit" form="step1Form">Send OTP</button>
        <?php elseif (empty($success) && $step == 2): ?>
          <!-- Step 2: Verify OTP -->
          <button type="submit" form="step2Form">Verify OTP</button>
        <?php endif; ?>
      </div>
      <div class="footer-call">
        Call: 16247
      </div>
    </div>
</div>

<script>
function closePopup() {
  // Cancel => redirect to subscription plans or home
  window.location.href = "/BookHeaven/php/subscription_plans.php"; // Update the path as needed
}

// If success => auto-redirect home after 3 seconds
<?php if (!empty($success)): ?>
  setTimeout(function(){
    window.location.href = "/BookHeaven/index.php"; // Update the path as needed
  }, 3000);
<?php endif; ?>
</script>
</body>
</html>
