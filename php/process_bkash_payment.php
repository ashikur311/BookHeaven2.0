<?php
session_start();
require_once '../db_connection.php'; // Database connection

// Initialize variables
$error = '';
$success = '';
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Check if user is logged in
if (!$user_id) {
    header("Location: authentication.php");
    exit;
}

// Get user email
$userEmail = "";
$query = "SELECT email FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($userEmail);
$stmt->fetch();
$stmt->close();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sanitize inputs
$payment_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
$payment_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Validate payment type
if (!in_array($payment_type, ['subscription', 'book_order']) || !$payment_id) {
    die("Invalid payment request");
}

// Get payment details based on type
if ($payment_type == 'subscription') {
    // Get subscription plan details
    $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE plan_id = ?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment_details = $result->fetch_assoc();
    $stmt->close();
    
    if (!$payment_details) {
        die("Invalid subscription plan");
    }
    
    $amount = $payment_details['price'];
    $description = "Subscription: " . $payment_details['plan_name'];
    
    // Check for existing subscription order or create new one
    $stmt = $conn->prepare("SELECT id FROM subscription_orders 
                           WHERE user_id = ? AND plan_id = ? 
                           AND status = 'pending' 
                           ORDER BY issue_date DESC LIMIT 1");
    $stmt->bind_param("ii", $user_id, $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription_order = $result->fetch_assoc();
    $stmt->close();
    
    if ($subscription_order) {
        $id = $subscription_order['id'];
    } else {
        // Create new subscription order
        $invoice_number = "INV-" . date('Ymd') . "-" . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $conn->prepare("INSERT INTO subscription_orders 
                              (user_id, plan_id, amount, invoice_number, status, 
                              payment_status, issue_date, expire_date) 
                              VALUES (?, ?, ?, ?, 'pending', 'unpaid', 
                              NOW(), DATE_ADD(NOW(), INTERVAL ? DAY))");
        $stmt->bind_param("iidsi", $user_id, $payment_id, $amount, $invoice_number, $payment_details['validity_days']);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
    }
} else {
    // For book orders
    $stmt = $conn->prepare("SELECT o.total_amount, o.order_id 
                           FROM orders o 
                           WHERE o.order_id = ? AND o.user_id = ?");
    $stmt->bind_param("ii", $payment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        die("Invalid order");
    }
    
    $stmt = $conn->prepare("SELECT b.title, oi.quantity, oi.price 
                           FROM order_items oi 
                           JOIN books b ON oi.book_id = b.book_id 
                           WHERE oi.order_id = ?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $description = "Book Purchase: ";
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row['title'] . " (x" . $row['quantity'] . ")";
    }
    $description .= implode(", ", $items);
    $amount = $order['total_amount'];
    $stmt->close();
}

// Handle resend OTP request
if (isset($_GET['resend'])) {
    // Clear any existing OTP
    $stmt = $conn->prepare("DELETE FROM user_otp 
                           WHERE user_id = ? AND purpose = 'bkash_payment'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to step 1 to enter phone number again
    header("Location: process_bkash_payment.php?type=$payment_type&id=$payment_id&step=1");
    exit;
}

// ---------- STEP 1: Enter bKash Number ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bkash_number']) && $step == 1) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $bkashNumber = trim($_POST['bkash_number']);
    if (empty($bkashNumber)) {
        $error = "Please enter your bKash number.";
    } else {
        // Validate bKash number format
        if (!preg_match('/^01[3-9]\d{8}$/', $bkashNumber)) {
            $error = "Invalid bKash number format. It should start with '01' followed by 9 digits (total 11 digits).";
        } else {
            // Store phone in session
            $_SESSION['bkash_number'] = $bkashNumber;

            // Generate a 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Insert into user_otp table (similar to first example)
            $insertOTP = "INSERT INTO user_otp (user_id, otp_code, otp_time, purpose, otp_attempts)
                          VALUES (?, ?, NOW(), 'bkash_payment', 0)";
            $stmt = $conn->prepare($insertOTP);
            $stmt->bind_param("is", $user_id, $otp);
            
            if ($stmt->execute()) {
                // Send OTP via Python script (same as first example)
                $python = "python"; // or "python3" depending on your system
                $scriptPath = "C:/xampp/htdocs/BookHeaven2.0/sendotp.py"; // Update path as needed
                
                $command = escapeshellcmd($python . ' ' . escapeshellarg($scriptPath) . ' ' 
                         . escapeshellarg($userEmail) . ' ' 
                         . escapeshellarg($otp));
                
                // Execute the command and capture output
                $output = shell_exec($command);
                
                // Log the output for debugging
                error_log("OTP script output: " . $output);
                
                // Proceed to OTP verification step
                header("Location: process_bkash_payment.php?type=$payment_type&id=$payment_id&step=2");
                exit;
            } else {
                $error = "Failed to generate OTP. Please try again.";
            }
            $stmt->close();
        }
    }
}

// ---------- STEP 2: Verify OTP ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp']) && $step == 2) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $otp = trim($_POST['otp']);
    
    if (empty($otp)) {
        $error = "Please enter the OTP.";
    } else {
        // Verify OTP (similar to first example)
        $stmt = $conn->prepare("SELECT id, otp_code, otp_time, otp_attempts FROM user_otp
                               WHERE user_id = ? AND purpose = 'bkash_payment'
                               ORDER BY otp_time DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $otpRow = $result->fetch_assoc();
        $stmt->close();

        if (!$otpRow) {
            $error = "No OTP found. Please request a new one.";
        } else {
            // Check OTP expiration (10 minutes)
            $otpTime = strtotime($otpRow['otp_time']);
            $currentTime = time();
            if (($currentTime - $otpTime) > 600) {
                $error = "OTP has expired. Please request a new one.";
            } else {
                // Check OTP attempts
                if ($otpRow['otp_attempts'] >= 5) {
                    $error = "Maximum OTP attempts exceeded. Please request a new OTP.";
                } else {
                    if ($otp === $otpRow['otp_code']) {
                        // OTP verified - process payment
                        $transaction_id = "BKH" . time() . rand(100, 999);
                        
                        // Start transaction
                        $conn->begin_transaction();
                        
                        try {
                            if ($payment_type == 'subscription') {
                                // For subscription payment
                                
                                // Create user subscription if not exists
                                $stmt = $conn->prepare("INSERT INTO user_subscriptions 
                                                      (user_id, subscription_plan_id, start_date, 
                                                      end_date, status, available_audio, available_rent_book)
                                                      VALUES (?, ?, NOW(), 
                                                      DATE_ADD(NOW(), INTERVAL ? DAY), 'active', ?, ?)
                                                      ON DUPLICATE KEY UPDATE
                                                      start_date = NOW(),
                                                      end_date = DATE_ADD(NOW(), INTERVAL ? DAY),
                                                      status = 'active',
                                                      available_audio = ?,
                                                      available_rent_book = ?");
                                $validity_days = $payment_details['validity_days'];
                                $audio_quantity = $payment_details['audiobook_quantity'];
                                $book_quantity = $payment_details['book_quantity'];
                                $stmt->bind_param("iiiiiiii", 
                                    $user_id, $payment_id, $validity_days, 
                                    $audio_quantity, $book_quantity,
                                    $validity_days, $audio_quantity, $book_quantity);
                                $stmt->execute();
                                
                                $user_subscription_id = $conn->insert_id ?: $conn->query("SELECT user_subscription_id FROM user_subscriptions WHERE user_id = $user_id AND subscription_plan_id = $payment_id")->fetch_assoc()['user_subscription_id'];
                                $stmt->close();
                                
                                // Record the transaction
                                $stmt = $conn->prepare("INSERT INTO subscription_transactions 
                                                      (user_subscription_id, amount, payment_method, 
                                                      payment_status, transaction_code, payment_provider, transaction_date)
                                                      VALUES (?, ?, 'bkash', 'paid', ?, 'bKash', NOW())");
                                $stmt->bind_param("ids", $user_subscription_id, $amount, $transaction_id);
                                $stmt->execute();
                                $stmt->close();
                                
                                // Update subscription order status
                                $stmt = $conn->prepare("UPDATE subscription_orders 
                                                      SET payment_status = 'paid', 
                                                          payment_method = 'bkash',
                                                          status = 'active',
                                                          issue_date = NOW(),
                                                          expire_date = DATE_ADD(NOW(), INTERVAL ? DAY),
                                                          user_subscription_id = ?
                                                      WHERE id = ?");
                                $stmt->bind_param("iii", $validity_days, $user_subscription_id, $id);
                                $stmt->execute();
                                $stmt->close();
                                
                                $success = "Subscription payment successful! Your subscription is now active.";
                                $redirect = "subscription.php";
                            } else {
                                // For book purchase
                                
                                // Record the transaction
                                $stmt = $conn->prepare("INSERT INTO transactions 
                                                      (order_id, payment_method, payment_status, 
                                                      transaction_date, payment_reference)
                                                      VALUES (?, 'bkash', 'paid', NOW(), ?)");
                                $stmt->bind_param("is", $payment_id, $transaction_id);
                                $stmt->execute();
                                $stmt->close();
                                
                                // Update order status
                                $stmt = $conn->prepare("UPDATE orders 
                                                      SET status = 'confirmed', 
                                                          payment_method = 'online',
                                                          payment_status = 'paid'
                                                      WHERE order_id = ?");
                                $stmt->bind_param("i", $payment_id);
                                $stmt->execute();
                                $stmt->close();
                                
                                $success = "Book purchase successful! Your order has been confirmed.";
                                $redirect = "user_orders.php";
                            }
                            
                            $conn->commit();
                            
                            // Clear session data
                            unset($_SESSION['bkash_number']);
                            unset($_SESSION['csrf_token']);
                            
                            // Delete used OTP
                            $stmt = $conn->prepare("DELETE FROM user_otp 
                                                  WHERE user_id = ? AND purpose = 'bkash_payment'");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $stmt->close();
                            
                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = "Payment processing failed. Please try again or contact support.";
                            error_log("Payment failed - User: $user_id, Error: " . $e->getMessage());
                        }
                    } else {
                        // Invalid OTP - increment attempts
                        $stmt = $conn->prepare("UPDATE user_otp 
                                              SET otp_attempts = otp_attempts + 1 
                                              WHERE id = ?");
                        $stmt->bind_param("i", $otpRow['id']);
                        $stmt->execute();
                        
                        $attempts = $otpRow['otp_attempts'] + 1;
                        $remaining = 5 - $attempts;
                        
                        $error = "Invalid OTP. You have $remaining attempts remaining.";
                        $stmt->close();
                        
                        if ($remaining <= 0) {
                            $error = "Too many failed attempts. Please start over.";
                            header("Location: process_bkash_payment.php?type=$payment_type&id=$payment_id&step=1");
                            exit;
                        }
                    }
                }
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
    <title>BookHeaven - bKash Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bkash-header {
            background-color: #e2136e;
            color: white;
            padding: 15px 0;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .bkash-logo {
            max-width: 100px;
            margin-bottom: 15px;
        }
        .payment-card {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 30px auto;
        }
        .payment-body {
            padding: 25px;
            border: 1px solid #eee;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .form-control:focus {
            border-color: #e2136e;
            box-shadow: 0 0 0 0.25rem rgba(226, 19, 110, 0.25);
        }
        .btn-bkash {
            background-color: #e2136e;
            color: white;
            font-weight: bold;
        }
        .btn-bkash:hover {
            background-color: #c0105d;
            color: white;
        }
        .payment-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .otp-input {
            letter-spacing: 10px;
            font-size: 24px;
            text-align: center;
            padding: 10px;
        }
        .attempts-warning {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="bkash-header">
                <img src="/BookHeaven2.0/assets/images/bkashlogo.png" alt="bKash Logo" class="bkash-logo">
                <h3>bKash Payment</h3>
            </div>
            
            <div class="payment-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <p>You will be redirected shortly...</p>
                        <div class="progress mt-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                        </div>
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = "<?php echo $redirect; ?>";
                        }, 3000);
                    </script>
                <?php else: ?>
                    <div class="payment-summary">
                        <h5>Payment Summary</h5>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($description); ?></p>
                        <p><strong>Amount:</strong> ৳<?php echo number_format($amount, 2); ?></p>
                        <?php if ($step == 2 && isset($remaining)): ?>
                            <p class="attempts-warning">Remaining attempts: <?php echo $remaining; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($step == 1): ?>
                        <form method="POST" action="process_bkash_payment.php?type=<?php echo $payment_type; ?>&id=<?php echo $payment_id; ?>&step=1">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="mb-3">
                                <label for="bkash_number" class="form-label">Enter Your bKash Number</label>
                                <input type="tel" class="form-control" id="bkash_number" name="bkash_number" 
                                       placeholder="01XXXXXXXXX" pattern="01[3-9]\d{8}" required
                                       value="<?php echo $_SESSION['bkash_number'] ?? ''; ?>">
                                <div class="form-text">Must start with 01 and be 11 digits total</div>
                            </div>
                            <button type="submit" class="btn btn-bkash w-100">Send OTP</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="process_bkash_payment.php?type=<?php echo $payment_type; ?>&id=<?php echo $payment_id; ?>&step=2">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="mb-3">
                                <label for="otp" class="form-label">Enter OTP Sent to <?php echo htmlspecialchars($userEmail); ?></label>
                                <input type="text" class="form-control otp-input" id="otp" name="otp" 
                                       placeholder="------" maxlength="6" pattern="\d{6}" required
                                       autocomplete="off">
                                <div class="form-text">Check your email for the 6-digit OTP (valid for 10 minutes)</div>
                            </div>
                            <button type="submit" class="btn btn-bkash w-100">Confirm Payment</button>
                            <div class="text-center mt-3">
                                <a href="process_bkash_payment.php?type=<?php echo $payment_type; ?>&id=<?php echo $payment_id; ?>&step=1" class="text-muted">Change bKash Number</a> | 
                                <a href="process_bkash_payment.php?type=<?php echo $payment_type; ?>&id=<?php echo $payment_id; ?>&resend=1" class="text-muted">Resend OTP</a>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus the OTP input
        <?php if ($step == 2): ?>
            document.getElementById('otp').focus();
        <?php endif; ?>
        
        // Format bKash number input
        document.getElementById('bkash_number')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11);
        });
        
        // Format OTP input
        document.getElementById('otp')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
        });
        
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>