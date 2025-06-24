<?php
session_start();
require_once '../db_connection.php'; // Database connection

// Initialize variables
$error = '';
$success = '';
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$subscription_order_id = isset($_GET['subscription_order_id']) ? intval($_GET['subscription_order_id']) : 0;
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$payment_type = '';
// Determine payment type
if ($subscription_order_id > 0) {
    $payment_type = 'subscription';
} elseif ($order_id > 0) {
    $payment_type = 'book';
}

// Check if user is logged in
if ($user_id == 0) {
    header("Location: login.php");
    exit;
}

// Get user email
$user_email = '';
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_email = $user['email'];
}
$stmt->close();

// STEP 1: Enter email and send OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && $step == 1) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store email in session
        $_SESSION['payment_email'] = $email;
        
        // Insert into user_otp
        $stmt = $conn->prepare("INSERT INTO user_otp (user_id, otp_code, otp_time, purpose, otp_attempts) 
                               VALUES (?, ?, NOW(), 'card_payment', 0)");
        $stmt->bind_param("is", $user_id, $otp);
        
        if ($stmt->execute()) {
            // Send OTP using Python script
            $python = "python"; // or "python3" depending on your system
            $scriptPath = __DIR__ . "/sendotp.py"; // Path to the Python script
            
            // Build the command to execute the Python script
            $command = escapeshellcmd($python . ' ' . escapeshellarg($scriptPath) . ' ' . 
                     escapeshellarg($email) . ' ' . escapeshellarg($otp));
            
            // Execute the command
            $output = shell_exec($command);
            
            if ($output !== null && strpos($output, "OTP sent successfully") !== false) {
                // Proceed to OTP verification step
                header("Location: process_card_payment.php?step=2&" . 
                      ($payment_type == 'subscription' ? "subscription_order_id=$subscription_order_id" : "order_id=$order_id"));
                exit;
            } else {
                $error = "Failed to send OTP. Please try again. Error: " . htmlspecialchars($output);
            }
        } else {
            $error = "Failed to generate OTP. Please try again.";
        }
        $stmt->close();
    }
}

// STEP 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp']) && $step == 2) {
    $entered_otp = trim($_POST['otp']);
    
    if (empty($entered_otp)) {
        $error = "Please enter the OTP.";
    } else {
        // Get the latest OTP for this user
        $stmt = $conn->prepare("SELECT id, otp_code FROM user_otp 
                               WHERE user_id = ? AND purpose = 'card_payment' 
                               ORDER BY otp_time DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $otp_data = $result->fetch_assoc();
            
            if ($entered_otp == $otp_data['otp_code']) {
                // OTP matched - process payment
                if ($payment_type == 'subscription') {
                    // Get subscription details
                    $stmt = $conn->prepare("SELECT * FROM subscription_orders 
                                           WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $subscription_order_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $subscription = $result->fetch_assoc();
                        
                        // Update subscription status
                        $update = $conn->prepare("UPDATE subscription_orders 
                                                SET status = 'active', payment_status = 'paid', 
                                                    payment_method = 'credit_card', updated_at = NOW()
                                                WHERE id = ?");
                        $update->bind_param("i", $subscription_order_id);
                        
                        if ($update->execute()) {
                            // Update user subscription
                            $insert_sub = $conn->prepare("INSERT INTO user_subscriptions 
                                                        (user_id, subscription_plan_id, start_date, end_date, 
                                                         status, auto_renew, available_audio, available_rent_book)
                                                        VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), 
                                                               'active', 0, ?, ?)");
                            $insert_sub->bind_param("iiiii", $user_id, $subscription['plan_id'], 
                                                   $subscription['validity_days'], 
                                                   $subscription['audiobook_quantity'], 
                                                   $subscription['book_quantity']);
                            
                            if ($insert_sub->execute()) {
                                // Record transaction
                                $txn = $conn->prepare("INSERT INTO subscription_transactions 
                                                      (user_subscription_id, amount, payment_method, 
                                                       payment_status, transaction_code, transaction_date)
                                                      VALUES (?, ?, 'credit_card', 'paid', ?, NOW())");
                                $txn_code = 'TXN-' . strtoupper(uniqid());
                                $txn->bind_param("ids", $conn->insert_id, $subscription['amount'], $txn_code);
                                $txn->execute();
                                
                                $success = "Subscription payment successful!";
                                $_SESSION['payment_success'] = true;
                                header("Location: subscription_plan.php?id=" . $subscription_order_id);
                                exit;
                            } else {
                                $error = "Failed to activate subscription.";
                            }
                        } else {
                            $error = "Failed to update subscription status.";
                        }
                    } else {
                        $error = "Subscription not found.";
                    }
                } else {
                    // Book purchase transaction
                    // Get order details
                    $stmt = $conn->prepare("SELECT * FROM orders 
                                           WHERE order_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $order_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $order = $result->fetch_assoc();
                        
                        // Update order status
                        $update = $conn->prepare("UPDATE orders 
                                                SET status = 'confirmed', payment_method = 'credit_card'
                                                WHERE order_id = ?");
                        $update->bind_param("i", $order_id);
                        
                        if ($update->execute()) {
                            // Record transaction
                            $txn = $conn->prepare("INSERT INTO transactions 
                                                  (order_id, payment_method, payment_status, 
                                                   transaction_date, payment_reference)
                                                  VALUES (?, 'credit_card', 'paid', NOW(), ?)");
                            $txn_ref = 'TXN-' . strtoupper(uniqid());
                            $txn->bind_param("is", $order_id, $txn_ref);
                            $txn->execute();
                            
                            // Update book quantities
                            $items = $conn->prepare("SELECT book_id, quantity FROM order_items 
                                                    WHERE order_id = ?");
                            $items->bind_param("i", $order_id);
                            $items->execute();
                            $item_result = $items->get_result();
                            
                            while ($item = $item_result->fetch_assoc()) {
                                $update_book = $conn->prepare("UPDATE books 
                                                              SET quantity = quantity - ? 
                                                              WHERE book_id = ?");
                                $update_book->bind_param("ii", $item['quantity'], $item['book_id']);
                                $update_book->execute();
                            }
                            
                            $success = "Payment successful! Your order is being processed.";
                            $_SESSION['payment_success'] = true;
                            header("Location: user_orders.php?id=" . $order_id);
                            exit;
                        } else {
                            $error = "Failed to update order status.";
                        }
                    } else {
                        $error = "Order not found.";
                    }
                }
            } else {
                $error = "Invalid OTP. Please try again.";
            }
        } else {
            $error = "No OTP found. Please request a new one.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment | BookHeaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .payment-header h2 {
            color: #2c3e50;
            font-weight: 600;
        }
        .payment-header p {
            color: #7f8c8d;
        }
        .payment-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .payment-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .payment-card-header i {
            font-size: 24px;
            margin-right: 10px;
            color: #3498db;
        }
        .payment-card-header h4 {
            margin: 0;
            color: #2c3e50;
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        .btn-pay {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.3s;
        }
        .btn-pay:hover {
            background-color: #2980b9;
        }
        .payment-methods {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .payment-method {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            flex: 1;
            margin: 0 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #3498db;
        }
        .payment-method.active {
            border-color: #3498db;
            background-color: #f0f8ff;
        }
        .payment-method i {
            font-size: 30px;
            margin-bottom: 10px;
            color: #3498db;
        }
        .otp-input {
            letter-spacing: 10px;
            font-size: 24px;
            text-align: center;
        }
        .resend-otp {
            text-align: center;
            margin-top: 15px;
        }
        .resend-otp a {
            color: #3498db;
            text-decoration: none;
        }
        .payment-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-total {
            font-weight: 600;
            font-size: 18px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <h2>Secure Payment</h2>
                <p>Complete your purchase with our secure payment gateway</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Payment Summary -->
            <div class="payment-summary">
                <h5>Order Summary</h5>
                <?php if ($payment_type == 'subscription'): ?>
                    <?php 
                    $stmt = $conn->prepare("SELECT sp.plan_name, sp.price, so.amount 
                                           FROM subscription_orders so
                                           JOIN subscription_plans sp ON so.plan_id = sp.plan_id
                                           WHERE so.id = ? AND so.user_id = ?");
                    $stmt->bind_param("ii", $subscription_order_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $subscription = $result->fetch_assoc();
                    $stmt->close();
                    ?>
                    <div class="summary-item">
                        <span>Subscription Plan:</span>
                        <span><?php echo htmlspecialchars($subscription['plan_name']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Amount:</span>
                        <span>৳<?php echo number_format($subscription['amount'], 2); ?></span>
                    </div>
                    <div class="summary-item summary-total">
                        <span>Total:</span>
                        <span>৳<?php echo number_format($subscription['amount'], 2); ?></span>
                    </div>
                <?php else: ?>
                    <?php 
                    $stmt = $conn->prepare("SELECT o.total_amount 
                                           FROM orders o
                                           WHERE o.order_id = ? AND o.user_id = ?");
                    $stmt->bind_param("ii", $order_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $order = $result->fetch_assoc();
                    $stmt->close();
                    
                    $stmt = $conn->prepare("SELECT b.title, oi.price, oi.quantity 
                                           FROM order_items oi
                                           JOIN books b ON oi.book_id = b.book_id
                                           WHERE oi.order_id = ?");
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    $items_result = $stmt->get_result();
                    $stmt->close();
                    ?>
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <div class="summary-item">
                            <span><?php echo htmlspecialchars($item['title']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span>৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endwhile; ?>
                    <div class="summary-item">
                        <span>Shipping:</span>
                        <span>৳0.00</span>
                    </div>
                    <div class="summary-item summary-total">
                        <span>Total:</span>
                        <span>৳<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Steps -->
            <?php if ($step == 1): ?>
                <!-- Step 1: Enter Email -->
                <form method="POST" action="">
                    <div class="payment-card">
                        <div class="payment-card-header">
                            <i class="fas fa-envelope"></i>
                            <h4>Verify Your Email</h4>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user_email); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-pay">Send Verification OTP</button>
                    </div>
                </form>
            <?php elseif ($step == 2): ?>
                <!-- Step 2: Verify OTP -->
                <form method="POST" action="">
                    <div class="payment-card">
                        <div class="payment-card-header">
                            <i class="fas fa-shield-alt"></i>
                            <h4>Verify OTP</h4>
                        </div>
                        <p>We've sent a 6-digit verification code to <?php echo htmlspecialchars($_SESSION['payment_email']); ?></p>
                        <div class="mb-3">
                            <label for="otp" class="form-label">Enter OTP</label>
                            <input type="text" class="form-control otp-input" id="otp" name="otp" 
                                   maxlength="6" pattern="\d{6}" required>
                        </div>
                        <button type="submit" class="btn btn-pay">Verify & Complete Payment</button>
                        <div class="resend-otp">
                            <a href="#" id="resend-otp">Resend OTP</a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Payment Methods -->
            <div class="payment-card">
                <div class="payment-card-header">
                    <i class="fas fa-credit-card"></i>
                    <h4>Payment Methods</h4>
                </div>
                <div class="payment-methods">
                    <div class="payment-method active">
                        <i class="fab fa-cc-visa"></i>
                        <div>Credit Card</div>
                    </div>
                    <div class="payment-method">
                        <i class="fas fa-mobile-alt"></i>
                        <div>bKash</div>
                    </div>
                    <div class="payment-method">
                        <i class="fas fa-university"></i>
                        <div>Bank Transfer</div>
                    </div>
                </div>
                
                <!-- Credit Card Form (hidden initially) -->
                <div id="credit-card-form" style="display: none;">
                    <div class="mb-3">
                        <label for="card-number" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="card-number" placeholder="1234 5678 9012 3456">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expiry-date" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiry-date" placeholder="MM/YY">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" placeholder="123">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="card-name" class="form-label">Name on Card</label>
                        <input type="text" class="form-control" id="card-name" placeholder="John Doe">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('active');
                });
                this.classList.add('active');
                
                // Show/hide forms based on selection
                // In a real implementation, you would handle different payment methods
            });
        });
        
        // Resend OTP
        document.getElementById('resend-otp')?.addEventListener('click', function(e) {
            e.preventDefault();
            alert('A new OTP has been sent to your email.');
            // In a real implementation, you would resend the OTP
        });
        
        // Format OTP input
        document.querySelector('.otp-input')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>