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

// Fetch user's current subscriptions (both active and expired)
$currentPlans = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $currentPlanQuery = "SELECT sp.*, us.*, 
                         DATEDIFF(us.end_date, NOW()) AS days_left,
                         us.available_audio AS audio_left,
                         us.available_rent_book AS books_left,
                         CASE 
                             WHEN us.end_date > NOW() THEN 'active'
                             ELSE 'expired'
                         END AS subscription_status
                         FROM user_subscriptions us
                         JOIN subscription_plans sp ON us.subscription_plan_id = sp.plan_id
                         WHERE us.user_id = ? AND (us.status = 'active' OR us.status = 'expired')
                         ORDER BY us.end_date DESC";
    $stmt = $conn->prepare($currentPlanQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $currentPlans[] = $row;
    }
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
   <link rel="stylesheet" href="/BookHeaven2.0/css/subscriptions_plan.css">
</head>
<body>
    <?php include_once("../header.php") ?>
    <main>
        <section class="current-plan">
            <h2><i class="fas fa-id-card"></i> Your Current Plans</h2>
            <?php if (!empty($currentPlans)): ?>
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
                        <?php foreach ($currentPlans as $plan): ?>
                            <tr>
                                <td><?= htmlspecialchars($plan['plan_name']) ?></td>
                                <td>$<?= number_format($plan['price'], 2) ?></td>
                                <td><?= date('M d, Y', strtotime($plan['end_date'])) ?></td>
                                <td><?= $plan['book_quantity'] ?></td>
                                <td><?= $plan['audiobook_quantity'] ?></td>
                                <td><?= $plan['books_left'] ?? 0 ?></td>
                                <td><?= $plan['audio_left'] ?? 0 ?></td>
                                <td class="<?= $plan['subscription_status'] === 'active' ? 'status-active' : 'status-expired' ?>">
                                    <?= ucfirst($plan['subscription_status']) ?>
                                </td>
                                <td>
                                    <?php if ($plan['subscription_status'] === 'active'): ?>
                                        <button class="action-btn"
                                            onclick="window.location.href='book_add_to_subscription.php?sub_id=<?php echo htmlspecialchars($plan['plan_id'] ?? ''); ?>'">
                                            <i class="fas fa-plus"></i> Add Book
                                        </button>
                                    <?php else: ?>
                                        <button class="action-btn warning"
                                            onclick="openModal(
                                                '<?= addslashes($plan['plan_name']) ?>', 
                                                '<?= $plan['price'] ?>', 
                                                '<?= $plan['book_quantity'] ?>', 
                                                '<?= $plan['audiobook_quantity'] ?>', 
                                                '<?= $plan['validity_days'] ?>',
                                                '<?= $plan['plan_id'] ?>'
                                            )">
                                            <i class="fas fa-sync-alt"></i> Renew
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-plan">You don't have any active subscription plans. Please subscribe to access books and audio
                    books.</p>
            <?php endif; ?>
        </section>

        <section class="available-plans">
            <h2><i class="fas fa-crown"></i> Available Plans</h2>
            <div class="plans-grid">
                <?php foreach ($plans as $plan): 
                    $hasActive = false;
                    $hasExpired = false;
                    
                    foreach ($currentPlans as $current) {
                        if ($current['plan_id'] == $plan['plan_id']) {
                            if ($current['subscription_status'] === 'active') {
                                $hasActive = true;
                            } else {
                                $hasExpired = true;
                            }
                        }
                    }
                ?>
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
                        
                        <?php if ($hasActive): ?>
                            <button class="subscribe-btn success" disabled>
                                <i class="fas fa-check"></i> Subscribed
                            </button>
                        <?php elseif ($hasExpired): ?>
                            <button class="subscribe-btn warning" 
                                onclick="openModal(
                                    '<?= addslashes($plan['plan_name']) ?>', 
                                    '<?= $plan['price'] ?>', 
                                    '<?= $plan['book_quantity'] ?>', 
                                    '<?= $plan['audiobook_quantity'] ?>', 
                                    '<?= $plan['validity_days'] ?>',
                                    '<?= $plan['plan_id'] ?>'
                                )">
                                <i class="fas fa-sync-alt"></i> Renew
                            </button>
                        <?php else: ?>
                            <button class="subscribe-btn" 
                                onclick="openModal(
                                    '<?= addslashes($plan['plan_name']) ?>', 
                                    '<?= $plan['price'] ?>', 
                                    '<?= $plan['book_quantity'] ?>', 
                                    '<?= $plan['audiobook_quantity'] ?>', 
                                    '<?= $plan['validity_days'] ?>',
                                    '<?= $plan['plan_id'] ?>'
                                )">
                                <i class="fas fa-crown"></i> Subscribe
                            </button>
                        <?php endif; ?>
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

            <!-- Payment Method Section -->
            <div class="payment-method">
                <h4 style="margin-bottom: 1rem; color: var(--text-color);">Select Payment Method</h4>
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <button class="payment-option" data-method="bkash">
                        <i class="fas fa-mobile-alt" style="color: #e2136e;"></i> bKash
                    </button>
                    <button class="payment-option" data-method="card">
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

        // Check if user already has this subscription
        function checkExistingSubscription(planId) {
            const currentPlans = <?php echo json_encode($currentPlans); ?>;
            const existingPlan = currentPlans.find(plan => plan.plan_id == planId);
            
            if (existingPlan) {
                if (existingPlan.subscription_status === 'active') {
                    return { exists: true, status: 'active' };
                } else {
                    return { exists: true, status: 'expired' };
                }
            }
            return { exists: false };
        }

        // Modal functions
        function openModal(planName, price, books, audioBooks, validity, planId) {
            const subscriptionCheck = checkExistingSubscription(planId);
            
            if (subscriptionCheck.exists && subscriptionCheck.status === 'active') {
                alert('You already have an active subscription for this plan.');
                return;
            }

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

            // Update button text based on existing subscription
            const confirmBtn = document.getElementById('confirmSubscription');
            if (subscriptionCheck.exists && subscriptionCheck.status === 'expired') {
                confirmBtn.textContent = 'Renew Subscription';
                confirmBtn.classList.add('warning');
            } else {
                confirmBtn.textContent = 'Confirm Subscription';
                confirmBtn.classList.remove('warning');
            }

            // Reset payment option styles
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
        }

        function closeModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
            selectedPlanId = null;
            selectedPaymentMethod = null;
        }

        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function () {
                // Reset all options
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });

                // Highlight selected option
                this.classList.add('selected');

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
        document.getElementById('confirmSubscription').addEventListener('click', function () {
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
        window.onclick = function (event) {
            const modal = document.getElementById('subscriptionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>