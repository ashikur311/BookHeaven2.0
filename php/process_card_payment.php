<?php
session_start();
require_once '../db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: authentication.php");
    exit;
}

// Initialize variables
$error = '';
$success = '';
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$user_id = $_SESSION['user_id'];

// Get payment details from URL
$payment_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
$payment_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Validate payment type
if (!in_array($payment_type, ['subscription', 'book_order']) || !$payment_id) {
    die("Invalid payment request");
}

// Get user details
$userEmail = "";
$query = "SELECT email, username FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($userEmail, $username);
$stmt->fetch();
$stmt->close();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get user's saved payment methods
$saved_cards = [];
$query = "SELECT * FROM user_payment_methods WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $saved_cards[] = $row;
}
$stmt->close();

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
    
    // Check for existing subscription order
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
    // For book orders (if needed)
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    // Check if using saved card or new card
    $using_saved_card = isset($_POST['saved_card_id']) && $_POST['saved_card_id'] !== 'new';
    
    if ($using_saved_card) {
        // Validate saved card selection
        $saved_card_id = intval($_POST['saved_card_id']);
        $card_found = false;
        
        // Verify the card belongs to the user
        foreach ($saved_cards as $card) {
            if ($card['id'] == $saved_card_id) {
                $card_found = true;
                $card_number = $card['card_number'];
                $card_expiry = $card['expiry_date'];
                $card_cvv = $_POST['saved_card_cvv']; // CVV is not stored, must be entered
                $card_name = $card['card_name'];
                $stored_cvv = $card['cvv']; // Get stored CVV for validation
                break;
            }
        }
        
        if (!$card_found) {
            $error = "Invalid card selection";
        } elseif (empty($card_cvv)) {
            $error = "Please enter the CVV for your card";
        } elseif (!preg_match('/^\d{3,4}$/', $card_cvv)) {
            $error = "Invalid CVV (must be 3 or 4 digits)";
        } elseif ($card_cvv !== $stored_cvv) {
            $error = "CVV does not match the saved card";
        }
    } else {
        // Validate new card details
        $card_number = str_replace(' ', '', $_POST['card_number']);
        $card_expiry = $_POST['card_expiry'];
        $card_cvv = $_POST['card_cvv'];
        $card_name = trim($_POST['card_name']);
        $save_card = isset($_POST['save_card']) && $_POST['save_card'] == '1';
        
        // Basic validation
        if (empty($card_number) || empty($card_expiry) || empty($card_cvv) || empty($card_name)) {
            $error = "Please fill in all card details";
        } elseif (!preg_match('/^\d{16}$/', $card_number)) {
            $error = "Invalid card number (must be 16 digits)";
        } elseif (!preg_match('/^\d{3,4}$/', $card_cvv)) {
            $error = "Invalid CVV (must be 3 or 4 digits)";
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $card_expiry)) {
            $error = "Invalid expiry date (MM/YY format)";
        }
    }
    
    if (empty($error)) {
        // Process payment (in a real app, this would connect to Stripe/PayPal/etc.)
        $transaction_id = "CARD" . time() . rand(100, 999);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            if ($payment_type == 'subscription') {
                // Check for existing subscription
                $stmt = $conn->prepare("SELECT * FROM user_subscriptions 
                                      WHERE user_id = ? AND subscription_plan_id = ?
                                      ORDER BY end_date DESC LIMIT 1");
                $stmt->bind_param("ii", $user_id, $payment_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $existing_sub = $result->fetch_assoc();
                $stmt->close();
                
                // Create or update subscription
                if ($existing_sub && strtotime($existing_sub['end_date']) > time()) {
                    // Extend existing subscription
                    $new_end_date = date('Y-m-d H:i:s', strtotime($existing_sub['end_date'] . " + {$payment_details['validity_days']} days"));
                    $stmt = $conn->prepare("UPDATE user_subscriptions 
                                          SET end_date = ?,
                                              status = 'active',
                                              available_audio = available_audio + ?,
                                              available_rent_book = available_rent_book + ?
                                          WHERE user_subscription_id = ?");
                    $stmt->bind_param("siii", $new_end_date, $payment_details['audiobook_quantity'], 
                                     $payment_details['book_quantity'], $existing_sub['user_subscription_id']);
                } else {
                    // Create new subscription
                    $new_end_date = date('Y-m-d H:i:s', strtotime("+{$payment_details['validity_days']} days"));
                    $stmt = $conn->prepare("INSERT INTO user_subscriptions 
                                          (user_id, subscription_plan_id, start_date, end_date, 
                                          status, available_audio, available_rent_book)
                                          VALUES (?, ?, NOW(), ?, 'active', ?, ?)");
                    $stmt->bind_param("iisii", $user_id, $payment_id, $new_end_date, 
                                     $payment_details['audiobook_quantity'], $payment_details['book_quantity']);
                }
                $stmt->execute();
                $subscription_id = $existing_sub['user_subscription_id'] ?? $conn->insert_id;
                $stmt->close();
                
                // Record the transaction
                $stmt = $conn->prepare("INSERT INTO subscription_transactions 
                                      (user_subscription_id, amount, payment_method, 
                                      payment_status, transaction_code, payment_provider, transaction_date)
                                      VALUES (?, ?, 'card', 'paid', ?, 'Stripe', NOW())");
                $stmt->bind_param("ids", $subscription_id, $amount, $transaction_id);
                $stmt->execute();
                $stmt->close();
                
                // Update subscription order status
                $stmt = $conn->prepare("UPDATE subscription_orders 
                                      SET payment_status = 'paid', 
                                          payment_method = 'card',
                                          status = 'active',
                                          issue_date = NOW(),
                                          expire_date = ?,
                                          user_subscription_id = ?
                                      WHERE id = ?");
                $stmt->bind_param("sii", $new_end_date, $subscription_id, $id);
                $stmt->execute();
                $stmt->close();
                
                $success = "Subscription payment successful! Your subscription is now active.";
                $redirect = "user_subscription.php";
            } else {
                // For book orders (if needed)
                $stmt = $conn->prepare("INSERT INTO transactions 
                                      (order_id, payment_method, payment_status, 
                                      transaction_date, payment_reference)
                                      VALUES (?, 'card', 'paid', NOW(), ?)");
                $stmt->bind_param("is", $payment_id, $transaction_id);
                $stmt->execute();
                $stmt->close();
                
                // Update order status
                $stmt = $conn->prepare("UPDATE orders 
                                      SET status = 'confirmed', 
                                          payment_method = 'card',
                                          payment_status = 'paid'
                                      WHERE order_id = ?");
                $stmt->bind_param("i", $payment_id);
                $stmt->execute();
                $stmt->close();
                
                $success = "Book purchase successful! Your order has been confirmed.";
                $redirect = "user_orders.php";
            }
            
            // Save new card if requested and not using saved card
            if (!$using_saved_card && $save_card) {
                // Extract expiry month and year
                $expiry_parts = explode('/', $card_expiry);
                $expiry_month = $expiry_parts[0];
                $expiry_year = '20' . $expiry_parts[1]; // Assuming 21st century
                $formatted_expiry = $expiry_month . '/' . substr($expiry_year, 2);
                
                // Detect card type based on number (simplified for example)
                $card_type = 'visa';
                if (preg_match('/^5[1-5]/', $card_number)) {
                    $card_type = 'mastercard';
                } elseif (preg_match('/^3[47]/', $card_number)) {
                    $card_type = 'amex';
                } elseif (preg_match('/^6(?:011|5)/', $card_number)) {
                    $card_type = 'discover';
                }
                
                $stmt = $conn->prepare("INSERT INTO user_payment_methods 
                                      (user_id, card_type, card_number, card_name, expiry_date, cvv, is_default)
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $is_default = (count($saved_cards) === 0) ? 1 : 0; // Set as default if first card
                $stmt->bind_param("isssssi", $user_id, $card_type, $card_number, $card_name, $formatted_expiry, $card_cvv, $is_default);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            
            // Clear CSRF token
            unset($_SESSION['csrf_token']);
            
            // Store success message in session
            $_SESSION['payment_success'] = $success;
            header("Location: $redirect");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Payment processing failed. Please try again or contact support.";
            error_log("Payment failed - User: $user_id, Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHeaven - Secure Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
        }
        
        body {
            background-color: #f5f7ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-color);
        }

        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: none;
        }

        .payment-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .payment-header h3 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-header h3 i {
            margin-right: 10px;
        }

        .payment-body {
            padding: 2rem;
        }

        .payment-summary {
            background-color: var(--light-color);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--accent-color);
        }

        .payment-summary h5 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .payment-summary p {
            margin-bottom: 0.5rem;
            display: flex;
        }

        .payment-summary p strong {
            width: 120px;
            display: inline-block;
            color: var(--dark-color);
        }

        .alert {
            border-radius: 8px;
        }

        .saved-card-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background-color: var(--light-color);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #e0e0e0;
        }

        .saved-card-option:hover {
            border-color: var(--accent-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .saved-card-option input[type="radio"] {
            margin-right: 1rem;
            accent-color: var(--primary-color);
        }

        .saved-card-details {
            flex-grow: 1;
        }

        .card-type {
            display: inline-flex;
            align-items: center;
            margin-right: 1rem;
        }

        .card-type i {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .card-number {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .card-expiry {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .card-cvv-input {
            max-width: 100px;
            display: inline-block;
            margin-left: 1rem;
        }

        .new-card-form {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background-color: var(--light-color);
            border-radius: 10px;
            border: 1px dashed var(--accent-color);
        }

        .card-icons {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .card-icons img {
            height: 25px;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .card-icons img:hover {
            opacity: 1;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .btn-pay {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            margin-top: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-pay:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-pay:active {
            transform: translateY(0);
        }

        .security-note {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .security-note i {
            color: var(--success-color);
            margin-right: 5px;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            margin-top: 1rem;
        }

        .progress-bar {
            background-color: var(--success-color);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            cursor: pointer;
        }

        /* Animation for payment processing */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .processing {
            animation: pulse 1.5s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <h3><i class="far fa-credit-card"></i> Secure Payment</h3>
            </div>
            
            <div class="payment-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading">Payment Successful!</h4>
                        <p><?php echo $success; ?></p>
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
                        <h5><i class="fas fa-receipt"></i> Order Summary</h5>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($description); ?></p>
                        <p><strong>Amount:</strong> $<?php echo number_format($amount, 2); ?></p>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($username); ?></p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="process_card_payment.php?type=<?php echo $payment_type; ?>&id=<?php echo $payment_id; ?>&step=1">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <h5 class="mb-3"><i class="fas fa-credit-card"></i> Payment Method</h5>
                        
                        <?php if (!empty($saved_cards)): ?>
                            <div class="mb-4">
                                <?php foreach ($saved_cards as $card): 
                                    $card_last4 = substr($card['card_number'], -4);
                                    $card_type = strtolower($card['card_type']);
                                ?>
                                    <div class="saved-card-option">
                                        <input type="radio" name="saved_card_id" id="card_<?php echo $card['id']; ?>" 
                                               value="<?php echo $card['id']; ?>" 
                                               <?php echo (count($saved_cards) == 1) ? 'checked' : ''; ?>>
                                        <div class="saved-card-details">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="card-type">
                                                    <i class="fab fa-cc-<?php echo $card_type; ?>"></i>
                                                    <?php echo ucfirst($card_type); ?>
                                                </span>
                                                <span class="card-number">•••• •••• •••• <?php echo $card_last4; ?></span>
                                                <span class="card-expiry ms-auto">Exp: <?php echo $card['expiry_date']; ?></span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted"><?php echo htmlspecialchars($card['card_name']); ?></small>
                                                <div class="ms-auto">
                                                    <small>CVV:</small>
                                                    <input type="password" name="saved_card_cvv" 
                                                           class="form-control form-control-sm card-cvv-input" 
                                                           placeholder="•••" maxlength="4" 
                                                           <?php echo (count($saved_cards) > 1) ? 'disabled' : ''; ?>
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="saved-card-option">
                                    <input type="radio" name="saved_card_id" id="card_new" value="new">
                                    <label for="card_new" class="ms-2 fw-bold"><i class="fas fa-plus-circle me-2"></i>Use a new card</label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div id="newCardForm" class="<?php echo empty($saved_cards) ? 'show' : 'new-card-form'; ?>">
                            <div class="card-icons">
                                <img src="/BookHeaven2.0/assets/images/visa.png" alt="Visa" title="Visa">
                                <img src="/BookHeaven2.0/assets/images/mastercard.png" alt="Mastercard" title="Mastercard">
                                <img src="/BookHeaven2.0/assets/images/amex.png" alt="American Express" title="American Express">
                                <img src="/BookHeaven2.0/assets/images/discover.png" alt="Discover" title="Discover">
                            </div>
                            
                            <div class="mb-3">
                                <label for="card_name" class="form-label">Cardholder Name</label>
                                <input type="text" class="form-control" id="card_name" name="card_name" 
                                       placeholder="Name on card" <?php echo !empty($saved_cards) ? 'disabled' : ''; ?> required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="card_number" class="form-label">Card Number</label>
                                <input type="text" class="form-control" id="card_number" name="card_number" 
                                       placeholder="1234 5678 9012 3456" <?php echo !empty($saved_cards) ? 'disabled' : ''; ?> required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="card_expiry" class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" id="card_expiry" name="card_expiry" 
                                           placeholder="MM/YY" <?php echo !empty($saved_cards) ? 'disabled' : ''; ?> required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="card_cvv" class="form-label">Security Code (CVV)</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="card_cvv" name="card_cvv" 
                                               placeholder="•••" <?php echo !empty($saved_cards) ? 'disabled' : ''; ?> required>
                                        <span class="input-group-text" id="cvvHelp">
                                            <i class="fas fa-question-circle" title="3 or 4 digit security code on back of card"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (empty($saved_cards)): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="save_card" id="save_card" value="1" checked>
                                    <label class="form-check-label" for="save_card">
                                        <i class="fas fa-save me-1"></i> Save this card for future payments
                                    </label>
                                </div>
                            <?php else: ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="save_card" id="save_card" value="1">
                                    <label class="form-check-label" for="save_card">
                                        <i class="fas fa-save me-1"></i> Save this card for future payments
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-pay">
                            <i class="fas fa-lock me-2"></i> Pay $<?php echo number_format($amount, 2); ?>
                        </button>
                        
                        <div class="security-note">
                            <i class="fas fa-shield-alt"></i> Your payment is secured with 256-bit SSL encryption
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format card number input
        document.getElementById('card_number')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '')
                .replace(/(\d{4})/g, '$1 ')
                .trim()
                .substring(0, 19);
        });
        
        // Format expiry date input
        document.getElementById('card_expiry')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '')
                .replace(/(\d{2})/, '$1/')
                .substring(0, 5);
        });
        
        // Format CVV input
        document.getElementById('card_cvv')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
        
        // Toggle between saved cards and new card form
        document.querySelectorAll('input[name="saved_card_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const newCardForm = document.getElementById('newCardForm');
                const isNewCard = this.value === 'new';
                
                // Toggle new card form visibility
                if (isNewCard) {
                    newCardForm.classList.add('show');
                } else {
                    newCardForm.classList.remove('show');
                }
                
                // Enable/disable new card form fields
                const newCardFields = newCardForm.querySelectorAll('input:not([type="radio"]):not([type="checkbox"])');
                newCardFields.forEach(field => {
                    field.disabled = !isNewCard;
                    if (isNewCard) {
                        field.required = true;
                    } else {
                        field.required = false;
                        field.value = '';
                    }
                });
                
                // Enable/disable CVV field for saved card
                if (!isNewCard) {
                    const cardId = this.value;
                    const cvvInput = document.querySelector(`input[value="${cardId}"]`).closest('.saved-card-option').querySelector('input[name="saved_card_cvv"]');
                    if (cvvInput) {
                        cvvInput.disabled = false;
                        cvvInput.required = true;
                    }
                    
                    // Disable other CVV inputs
                    document.querySelectorAll('input[name="saved_card_cvv"]').forEach(input => {
                        if (input !== cvvInput) {
                            input.disabled = true;
                            input.required = false;
                            input.value = '';
                        }
                    });
                } else {
                    // Disable all saved card CVV inputs
                    document.querySelectorAll('input[name="saved_card_cvv"]').forEach(input => {
                        input.disabled = true;
                        input.required = false;
                        input.value = '';
                    });
                }
                
                // Enable/disable save card checkbox
                const saveCardCheckbox = document.getElementById('save_card');
                if (isNewCard) {
                    saveCardCheckbox.disabled = false;
                } else {
                    saveCardCheckbox.disabled = true;
                    saveCardCheckbox.checked = false;
                }
            });
        });
        
        // Initialize form based on saved cards
        document.addEventListener('DOMContentLoaded', function() {
            const savedCards = <?php echo json_encode($saved_cards); ?>;
            
            if (savedCards.length > 0) {
                // Enable CVV for the first saved card if it's selected
                const firstCardRadio = document.querySelector('input[name="saved_card_id"]:checked');
                if (firstCardRadio) {
                    const cvvInput = firstCardRadio.closest('.saved-card-option').querySelector('input[name="saved_card_cvv"]');
                    if (cvvInput) {
                        cvvInput.disabled = false;
                        cvvInput.required = true;
                    }
                }
            }
        });
        
        // Add pulse animation to pay button on submit
        document.querySelector('form')?.addEventListener('submit', function() {
            const payButton = this.querySelector('button[type="submit"]');
            if (payButton) {
                payButton.classList.add('processing');
                payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
                payButton.disabled = true;
            }
        });
        
        // Auto-focus first field
        document.querySelector('input[name="saved_card_id"]:checked')?.focus() || 
        document.getElementById('card_name')?.focus();
        
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>