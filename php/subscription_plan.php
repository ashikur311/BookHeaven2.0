<?php
session_start();
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
                         ORDER BY us.end_date DESC LIMIT 1";
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
    <link rel="stylesheet" href="/BookHeaven2.0/css/subscriptions_plan.css">
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
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($currentPlan['plan_name']) ?></td>
                            <td>$<?= number_format($currentPlan['price'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($currentPlan['end_date'])) ?></td>
                            <td><?= $currentPlan['book_quantity'] ?></td>
                            <td><?= $currentPlan['audiobook_quantity'] ?></td>
                            <td><?= $currentPlan['books_left'] ?? 0 ?></td>
                            <td><?= $currentPlan['audio_left'] ?? 0 ?></td>
                            <td class="status-active">Active</td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-plan">You don't have an active subscription plan.</p>
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
                                '<?= $plan['validity_days'] ?>'
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
            <div class="modal-actions">
                <button class="modal-btn modal-btn-close" onclick="closeModal()">Close</button>
                <button class="modal-btn modal-btn-confirm">Confirm Subscription</button>
            </div>
        </div>
    </div>
    
    <?php include_once("../footer.php") ?>
    
    <script>
        // Modal functions
        function openModal(planName, price, books, audioBooks, validity) {
            document.getElementById('modalPlanName').textContent = planName;
            document.getElementById('modalPlanPrice').textContent = parseFloat(price).toFixed(2);
            document.getElementById('modalBookQuantity').textContent = books;
            document.getElementById('modalAudioBookQuantity').textContent = audioBooks;
            document.getElementById('modalValidityDays').textContent = validity;
            
            document.getElementById('subscriptionModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
        }
        
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