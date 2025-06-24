<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authentication.php");
  exit();
}
require_once '../db_connection.php';

// Fetch user's current subscription
$currentPlan = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $currentPlanQuery = "SELECT sp.*, us.*, 
                         DATEDIFF(us.end_date, NOW()) AS days_left,
                         us.available_audio AS audio_left,
                         us.available_rent_book AS books_left
                         FROM user_subscriptions us
                         JOIN subscription_plans sp ON us.subscription_plan_id = sp.plan_id
                         WHERE us.user_id = ? AND us.status = 'active'
                         ORDER BY us.end_date DESC LIMIT 2";
    $stmt = $conn->prepare($currentPlanQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $currentPlan = $stmt->get_result()->fetch_assoc();
}

// Fetch all active subscription plans
$plansQuery = "SELECT * FROM subscription_plans WHERE status = 'active'";
$plansResult = $conn->query($plansQuery);
$plans = [];
while ($row = $plansResult->fetch_assoc()) {
    $plans[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #57abd2;
            --primary-dark: #3d8eb4;
            --secondary-color: #f8f5fc;
            --accent-color: rgb(223, 219, 227);
            --text-color: #333;
            --text-light: #666;
            --light-purple: #e6d9f2;
            --dark-text: #212529;
            --light-text: #f8f9fa;
            --card-bg: #ffffff;
            --aside-bg: #f0f2f5;
            --nav-hover: #e0e0e0;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --border-color: #e0e0e0;
            --hover-bg: #f5f5f5;
            --even-row-bg: #f9f9f9;
            --header-bg: #f0f0f0;
            --header-text: #333;
            --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .dark-mode {
            --primary-color: #57abd2;
            --primary-dark: #4a9bc1;
            --secondary-color: #2d3748;
            --accent-color: #4a5568;
            --text-color: #f8f9fa;
            --text-light: #a0aec0;
            --light-purple: #4a5568;
            --dark-text: #f8f9fa;
            --light-text: #212529;
            --card-bg: #1a202c;
            --aside-bg: #1a202c;
            --nav-hover: #4a5568;
            --border-color: #4a5568;
            --hover-bg: #2d3748;
            --even-row-bg: #2d3748;
            --header-bg: #1a202c;
            --header-text: #f8f9fa;
            --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            background-color: var(--aside-bg);
            color: var(--text-color);
            line-height: 1.6;
        }

        main {
            padding: 2rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        section {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Current Plan Section */
        .current-plan {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--header-bg);
            color: var(--header-text);
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: var(--even-row-bg);
        }

        tr:hover {
            background-color: var(--hover-bg);
        }

        .status-active {
            color: var(--success-color);
            font-weight: bold;
        }

        .status-expired {
            color: var(--danger-color);
            font-weight: bold;
        }

        .action-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            background-color: #218838;
        }

        .action-btn i {
            font-size: 0.8rem;
        }

        /* Plans Grid */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .plan-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            background-color: var(--card-bg);
            position: relative;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .plan-card.popular {
            border: 2px solid var(--primary-color);
        }

        .popular-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .plan-name {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .plan-price {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .plan-price span {
            font-size: 1rem;
            color: var(--text-light);
            font-weight: normal;
        }

        .plan-features {
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .plan-feature {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            color: var(--text-color);
        }

        .plan-feature i {
            margin-right: 0.75rem;
            color: var(--primary-color);
            min-width: 20px;
        }

        .subscribe-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-top: auto;
        }

        .subscribe-btn:hover {
            background-color: var(--primary-dark);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .modal-plan-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .modal-features {
            margin-bottom: 1.5rem;
        }

        .modal-feature {
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            color: var(--text-color);
        }

        .modal-feature-label {
            font-weight: bold;
            color: var(--text-color);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .modal-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .modal-btn-close {
            background-color: var(--danger-color);
            border: 1px solid var(--danger-color);
            color: white;
        }

        .modal-btn-close:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .modal-btn-confirm {
            background-color: var(--success-color);
            border: 1px solid var(--success-color);
            color: white;
        }

        .modal-btn-confirm:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .no-plan {
            color: var(--text-color);
            font-size: 1.1rem;
            padding: 1rem;
            background-color: var(--even-row-bg);
            border-radius: 6px;
            border-left: 4px solid var(--warning-color);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .plans-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 8px 10px;
            }

            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }

            .modal-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .modal-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include_once("../header.php") ?>
    <main>
        <section class="current-plan">
            <h2><i class="fas fa-id-card"></i> Your Current Plan</h2>
            <?php if ($currentPlan): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Plan Name</th>
                            <th>Price</th>
                            <th>Valid Until</th>
                            <th>Books Available</th>
                            <th>Audio Books</th>
                            <th>Books Left</th>
                            <th>Audio Books Left</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="current-plan-tbody">
                        <tr>
                            <td><?= htmlspecialchars($currentPlan['plan_name']) ?></td>
                            <td>$<?= number_format($currentPlan['price'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($currentPlan['end_date'])) ?></td>
                            <td><?= $currentPlan['book_quantity'] ?></td>
                            <td><?= $currentPlan['audiobook_quantity'] ?></td>
                            <td><?= $currentPlan['books_left'] ?? 0 ?></td>
                            <td><?= $currentPlan['audio_left'] ?? 0 ?></td>
                            <td class="status-active">Active</td>
                            <td>
                                <button class="action-btn" onclick="window.location.href='add_book_to_subscription.php'">
                                    <i class="fas fa-plus"></i> Add Book
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-plan">You don't have an active subscription plan. Please subscribe to access books and audio books.</p>
            <?php endif; ?>
        </section>
        
        <section class="available-plans">
            <h2><i class="fas fa-crown"></i> Available Plans</h2>
            <div class="plans-grid">
                <?php foreach ($plans as $plan): ?>
                    <div class="plan-card <?= $plan['plan_id'] == 2 ? 'popular' : '' ?>">
                        <?php if ($plan['plan_id'] == 2): ?>
                            <div class="popular-badge">Popular</div>
                        <?php endif; ?>
                        <h3 class="plan-name"><?= htmlspecialchars($plan['plan_name']) ?></h3>
                        <p class="plan-price">$<?= number_format($plan['price'], 2) ?>
                            <span>/<?= $plan['validity_days'] > 30 ? 'year' : 'month' ?></span>
                        </p>
                        <div class="plan-features">
                            <div class="plan-feature">
                                <i class="fas fa-check-circle"></i> 
                                <?= $plan['book_quantity'] ?> Books
                            </div>
                            <div class="plan-feature">
                                <i class="fas fa-check-circle"></i> 
                                <?= $plan['audiobook_quantity'] ?> Audio Books
                            </div>
                            <div class="plan-feature">
                                <i class="fas fa-check-circle"></i> 
                                <?= $plan['validity_days'] ?> Days Validity
                            </div>
                            <div class="plan-feature">
                                <i class="fas fa-check-circle"></i> 
                                <?= $plan['plan_id'] == 1 ? 'Standard' : 'Priority' ?> Support
                            </div>
                        </div>
                        <button class="subscribe-btn" 
                            onclick="openModal(
                                '<?= addslashes($plan['plan_name']) ?>', 
                                '<?= $plan['price'] ?>', 
                                '<?= $plan['book_quantity'] ?>', 
                                '<?= $plan['audiobook_quantity'] ?>', 
                                '<?= $plan['validity_days'] ?>',
                                '<?= $plan['plan_id'] ?>'
                            )">
                            Subscribe
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
   <!-- Subscription Modal -->
    <div class="modal" id="subscriptionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalPlanName">Plan Name</h3>
                <div class="modal-plan-price">$<span id="modalPlanPrice">0.00</span></div>
            </div>
            <div class="modal-features">
                <div class="modal-feature">
                    <span class="modal-feature-label">Books Included:</span>
                    <span id="modalBookQuantity">0</span>
                </div>
                <div class="modal-feature">
                    <span class="modal-feature-label">Audio Books Included:</span>
                    <span id="modalAudioBookQuantity">0</span>
                </div>
                <div class="modal-feature">
                    <span class="modal-feature-label">Validity:</span>
                    <span id="modalValidityDays">0</span> Days
                </div>
            </div>
            
            <!-- New Payment Method Section -->
            <div class="payment-method" style="margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 1rem; color: var(--text-color);">Select Payment Method</h4>
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <button class="payment-option" data-method="bkash" 
                            style="flex: 1; padding: 0.75rem; border: 2px solid var(--border-color); 
                                   border-radius: 6px; background: var(--card-bg); cursor: pointer;
                                   display: flex; align-items: center; justify-content: center;
                                   gap: 0.5rem; font-weight: bold; color: var(--text-color);">
                        <i class="fas fa-mobile-alt" style="color: #e2136e;"></i> bKash
                    </button>
                    <button class="payment-option" data-method="card" 
                            style="flex: 1; padding: 0.75rem; border: 2px solid var(--border-color); 
                                   border-radius: 6px; background: var(--card-bg); cursor: pointer;
                                   display: flex; align-items: center; justify-content: center;
                                   gap: 0.5rem; font-weight: bold; color: var(--text-color);">
                        <i class="far fa-credit-card" style="color: #0061a8;"></i> Credit/Debit Card
                    </button>
                </div>
                <div id="paymentDetails" style="display: none;">
                    <!-- Dynamic content based on payment method will appear here -->
                </div>
            </div>
            
            <div class="modal-actions">
                <button class="modal-btn modal-btn-close" onclick="closeModal()">Close</button>
                <button class="modal-btn modal-btn-confirm" id="confirmSubscription" disabled>Confirm Subscription</button>
            </div>
        </div>
    </div>
    
    <?php include_once("../footer.php") ?>
    
    <script>
        let selectedPlanId = null;
        let selectedPaymentMethod = null;
        
        // Modal functions
        function openModal(planName, price, books, audioBooks, validity, planId) {
            document.getElementById('modalPlanName').textContent = planName;
            document.getElementById('modalPlanPrice').textContent = parseFloat(price).toFixed(2);
            document.getElementById('modalBookQuantity').textContent = books;
            document.getElementById('modalAudioBookQuantity').textContent = audioBooks;
            document.getElementById('modalValidityDays').textContent = validity;
            
            selectedPlanId = planId;
            selectedPaymentMethod = null;
            document.getElementById('subscriptionModal').style.display = 'flex';
            document.getElementById('confirmSubscription').disabled = true;
            document.getElementById('paymentDetails').style.display = 'none';
            
            // Reset payment option styles
            document.querySelectorAll('.payment-option').forEach(option => {
                option.style.borderColor = 'var(--border-color)';
                option.style.backgroundColor = 'var(--card-bg)';
            });
        }
        
        function closeModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
            selectedPlanId = null;
            selectedPaymentMethod = null;
        }
        
        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Reset all options
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.style.borderColor = 'var(--border-color)';
                    opt.style.backgroundColor = 'var(--card-bg)';
                });
                
                // Highlight selected option
                this.style.borderColor = 'var(--primary-color)';
                this.style.backgroundColor = 'var(--light-purple)';
                
                selectedPaymentMethod = this.getAttribute('data-method');
                document.getElementById('confirmSubscription').disabled = false;
                
                // Show payment details
                const paymentDetails = document.getElementById('paymentDetails');
                if (selectedPaymentMethod === 'bkash') {
                    paymentDetails.innerHTML = `
                        <div style="background: var(--even-row-bg); padding: 1rem; border-radius: 6px;">
                            <p style="margin-bottom: 0.5rem; color: var(--text-color);">
                                <i class="fas fa-info-circle" style="color: var(--primary-color);"></i> 
                                Please complete payment via bKash to activate your subscription.
                            </p>
                            <p style="color: var(--text-color);">
                                bKash Merchant: 01777895XXX<br>
                                Amount: $${document.getElementById('modalPlanPrice').textContent}
                            </p>
                        </div>
                    `;
                } else if (selectedPaymentMethod === 'card') {
                    paymentDetails.innerHTML = `
                        <div style="background: var(--even-row-bg); padding: 1rem; border-radius: 6px;">
                            <p style="margin-bottom: 0.5rem; color: var(--text-color);">
                                <i class="fas fa-info-circle" style="color: var(--primary-color);"></i> 
                                You will be redirected to our secure payment gateway.
                            </p>
                            <p style="color: var(--text-color);">
                                We accept Visa, MasterCard, and American Express.
                            </p>
                        </div>
                    `;
                }
                paymentDetails.style.display = 'block';
            });
        });
        
        // Confirm subscription
document.getElementById('confirmSubscription').addEventListener('click', function() {
    if (selectedPlanId && selectedPaymentMethod) {
        if (selectedPaymentMethod === 'bkash') {
            // Redirect to bkash payment processing with plan ID
            window.location.href = `process_bkash_payment.php?type=subscription&id=${selectedPlanId}`;
        } else if (selectedPaymentMethod === 'card') {
            // Redirect to card payment processing with plan ID
            window.location.href = `process_card_payment.php?type=subscription&id=${selectedPlanId}`;
        }
    }
});

        // Close modal when clicking outside the content
        window.onclick = function(event) {
            const modal = document.getElementById('subscriptionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>