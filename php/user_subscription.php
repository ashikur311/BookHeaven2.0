<?php
// Start session and include database connection
require_once('../db_connection.php');

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authentication.php");
  exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Function to get subscription status
function getSubscriptionStatus($end_date) {
    $current_date = new DateTime();
    $end_date = new DateTime($end_date);
    return ($current_date < $end_date) ? 'active' : 'expired';
}

// Fetch user's active subscriptions
$active_subscriptions = [];
$expired_subscriptions = [];

$subscription_query = "SELECT us.user_subscription_id, us.subscription_plan_id, us.start_date, us.end_date, 
                      us.status, us.available_audio, us.available_rent_book,
                      sp.plan_name, sp.price, sp.validity_days, sp.book_quantity, sp.audiobook_quantity
                      FROM user_subscriptions us
                      JOIN subscription_plans sp ON us.subscription_plan_id = sp.plan_id
                      WHERE us.user_id = ?";
$stmt = $conn->prepare($subscription_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $status = getSubscriptionStatus($row['end_date']);
    
    if ($status === 'active') {
        $active_subscriptions[] = $row;
    } else {
        $expired_subscriptions[] = $row;
    }
}

// Calculate stats for the grid
$total_subscriptions = count($active_subscriptions) + count($expired_subscriptions);
$active_count = count($active_subscriptions);
$expired_count = count($expired_subscriptions);
$renew_needed = 0; // You can add logic to determine if renewal is needed

// Fetch user information for the sidebar
$user_query = "SELECT u.username, u.user_profile, u.create_time, ui.* 
              FROM users u 
              LEFT JOIN user_info ui ON u.user_id = ui.user_id 
              WHERE u.user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Close statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscriptions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/user_subscription.css">
</head>
<body>
    <?php include_once("../header.php"); ?>
    <main>
        <aside>
            <section class="user-info">
                 <img src="/BookHeaven2.0/<?php echo htmlspecialchars($user_data['user_profile'] ?? 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                    alt="<?php echo htmlspecialchars($user_data['username']); ?>" class="user-avatar">
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user_data['username']); ?></div>
                    <small>Member since: <?php echo date('M Y', strtotime($user_data['create_time'])); ?></small>
                </div>
            </section>
            <section>
                <nav>
                    <ul>
                        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wish List</a></li>
                        <li><a href="user_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                        <li><a href="user_subscription.php" class="active"><i class="fas fa-calendar-check"></i> My Subscription</a></li>
                        <li><a href="user_setting.php"><i class="fas fa-cog"></i> Setting</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </section>
        </aside>
        <!-- Grid View -->
        <div class="subscription_content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Subscriptions</h3>
                    <p><?php echo $total_subscriptions; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active</h3>
                    <p><?php echo $active_count; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Expired</h3>
                    <p><?php echo $expired_count; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Renew Needed</h3>
                    <p><?php echo $renew_needed; ?></p>
                </div>
            </div>
            
            <!-- Active Subscription Plan -->
            <div class="subscription-table">
                <h2>Active Subscription Plans</h2><br>
                <?php if (!empty($active_subscriptions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Plan ID</th>
                            <th>Name</th>
                            <th>Expire Date</th>
                            <th>Books Left</th>
                            <th>Audiobooks Left</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_subscriptions as $subscription): ?>
                        <tr>
                            <td>#<?php echo $subscription['user_subscription_id']; ?></td>
                            <td><?php echo htmlspecialchars($subscription['plan_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></td>
                            <td><?php echo $subscription['available_rent_book'] ?? $subscription['book_quantity']; ?></td>
                            <td><?php echo $subscription['available_audio'] ?? $subscription['audiobook_quantity']; ?></td>
                            <td>
                                <button class="btn btn-view" 
                                        onclick="openModal(
                                            '<?php echo $subscription['user_subscription_id']; ?>',
                                            '<?php echo htmlspecialchars($subscription['plan_name']); ?>',
                                            '<?php echo date('M d, Y', strtotime($subscription['start_date'])); ?>',
                                            '<?php echo date('M d, Y', strtotime($subscription['end_date'])); ?>',
                                            '<?php echo $subscription['book_quantity']; ?>',
                                            '<?php echo $subscription['available_rent_book'] ?? $subscription['book_quantity']; ?>',
                                            '<?php echo $subscription['audiobook_quantity']; ?>',
                                            '<?php echo $subscription['available_audio'] ?? $subscription['audiobook_quantity']; ?>',
                                            '<?php echo $subscription['price']; ?>',
                                            '<?php echo $subscription['validity_days']; ?>',
                                            'active'
                                        )">
                                    View Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No active subscriptions found.</p>
                <?php endif; ?>
            </div>
            
            <!-- Expired Subscription plan -->
            <div class="subscription-table">
                <h2>Expired Subscription Plans</h2><br>
                <?php if (!empty($expired_subscriptions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Plan ID</th>
                            <th>Name</th>
                            <th>Expire Date</th>
                            <th>Used Books</th>
                            <th>Used Audiobooks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expired_subscriptions as $subscription): 
                            $books_used = $subscription['book_quantity'] - ($subscription['available_rent_book'] ?? 0);
                            $audiobooks_used = $subscription['audiobook_quantity'] - ($subscription['available_audio'] ?? 0);
                        ?>
                        <tr>
                            <td>#<?php echo $subscription['user_subscription_id']; ?></td>
                            <td><?php echo htmlspecialchars($subscription['plan_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($subscription['end_date'])); ?></td>
                            <td><?php echo $books_used; ?></td>
                            <td><?php echo $audiobooks_used; ?></td>
                            <td>
                                <div class="button-container">
                                    <button class="btn btn-view" 
                                        onclick="openExpiredModal(
                                            '<?php echo $subscription['user_subscription_id']; ?>',
                                            '<?php echo htmlspecialchars($subscription['plan_name']); ?>',
                                            '<?php echo date('M d, Y', strtotime($subscription['start_date'])); ?>',
                                            '<?php echo date('M d, Y', strtotime($subscription['end_date'])); ?>',
                                            '<?php echo $subscription['book_quantity']; ?>',
                                            '<?php echo $books_used; ?>',
                                            '<?php echo $subscription['audiobook_quantity']; ?>',
                                            '<?php echo $audiobooks_used; ?>',
                                            '<?php echo $subscription['price']; ?>',
                                            '<?php echo $subscription['validity_days']; ?>'
                                        )">
                                    View Details
                                </button>
                                <button class="btn btn-renew" 
                                        onclick="renewSubscription(<?php echo $subscription['subscription_plan_id']; ?>)">
                                    Renew
                                </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No expired subscriptions found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include_once("../footer.php");?>

    <!-- Active Subscription Details Modal -->
    <div id="subscriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Subscription Details - <span id="modalPlanId"></span></h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="subscription-details">
                <div class="detail-item">
                    <label>Plan Name</label>
                    <p id="planName"></p>
                </div>
                <div class="detail-item">
                    <label>Plan ID</label>
                    <p id="planId"></p>
                </div>
                <div class="detail-item">
                    <label>Start Date</label>
                    <p id="startDate"></p>
                </div>
                <div class="detail-item">
                    <label>Expire Date</label>
                    <p id="expireDate"></p>
                </div>
                <div class="detail-item">
                    <label>Books Included</label>
                    <p id="booksIncluded"></p>
                </div>
                <div class="detail-item">
                    <label>Books Remaining</label>
                    <p id="booksUsed"></p>
                </div>
                <div class="detail-item">
                    <label>Audiobooks Included</label>
                    <p id="audiobooksIncluded"></p>
                </div>
                <div class="detail-item">
                    <label>Audiobooks Remaining</label>
                    <p id="audiobooksRemaining"></p>
                </div>
                <div class="detail-item">
                    <label>Price</label>
                    <p id="planPrice"></p>
                </div>
                <div class="detail-item">
                    <label>Validity</label>
                    <p id="planValidity"></p>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <p id="planStatus"></p>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-close" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Expired Subscription Details Modal -->
    <div id="expiredSubscriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Expired Subscription Details - <span id="expiredModalPlanId"></span></h3>
                <button class="modal-close" onclick="closeExpiredModal()">&times;</button>
            </div>
            <div class="subscription-details">
                <div class="detail-item">
                    <label>Plan Name</label>
                    <p id="expiredPlanName"></p>
                </div>
                <div class="detail-item">
                    <label>Plan ID</label>
                    <p id="expiredPlanId"></p>
                </div>
                <div class="detail-item">
                    <label>Start Date</label>
                    <p id="expiredStartDate"></p>
                </div>
                <div class="detail-item">
                    <label>Expire Date</label>
                    <p id="expiredExpireDate"></p>
                </div>
                <div class="detail-item">
                    <label>Books Included</label>
                    <p id="expiredBooksIncluded"></p>
                </div>
                <div class="detail-item">
                    <label>Books Used</label>
                    <p id="expiredBooksUsed"></p>
                </div>
                <div class="detail-item">
                    <label>Audiobooks Included</label>
                    <p id="expiredAudiobooksIncluded"></p>
                </div>
                <div class="detail-item">
                    <label>Audiobooks Used</label>
                    <p id="expiredAudiobooksUsed"></p>
                </div>
                <div class="detail-item">
                    <label>Price</label>
                    <p id="expiredPlanPrice"></p>
                </div>
                <div class="detail-item">
                    <label>Validity</label>
                    <p id="expiredPlanValidity"></p>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <p id="expiredPlanStatus">Expired</p>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-renew" onclick="renewSubscription()">Renew Plan</button>
                <button class="btn btn-close" onclick="closeExpiredModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Global variable to store current plan ID for renewal
        let currentPlanId = null;
        
        function openModal(planId, planName, startDate, expireDate, booksIncluded, booksUsed, 
                         audiobooksIncluded, audiobooksRemaining, price, validity, status) {
            const modal = document.getElementById('subscriptionModal');
            
            document.getElementById('modalPlanId').textContent = `#${planId}`;
            document.getElementById('planName').textContent = planName;
            document.getElementById('planId').textContent = planId;
            document.getElementById('startDate').textContent = startDate;
            document.getElementById('expireDate').textContent = expireDate;
            document.getElementById('booksIncluded').textContent = booksIncluded;
            document.getElementById('booksUsed').textContent = booksUsed;
            document.getElementById('audiobooksIncluded').textContent = audiobooksIncluded;
            document.getElementById('audiobooksRemaining').textContent = audiobooksRemaining;
            document.getElementById('planPrice').textContent = `$${price}`;
            document.getElementById('planValidity').textContent = `${validity} days`;
            document.getElementById('planStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
            
            modal.style.display = 'flex';
        }

        function openExpiredModal(planId, planName, startDate, expireDate, booksIncluded, booksUsed, 
                                audiobooksIncluded, audiobooksUsed, price, validity) {
            const modal = document.getElementById('expiredSubscriptionModal');
            
            document.getElementById('expiredModalPlanId').textContent = `#${planId}`;
            document.getElementById('expiredPlanName').textContent = planName;
            document.getElementById('expiredPlanId').textContent = planId;
            document.getElementById('expiredStartDate').textContent = startDate;
            document.getElementById('expiredExpireDate').textContent = expireDate;
            document.getElementById('expiredBooksIncluded').textContent = booksIncluded;
            document.getElementById('expiredBooksUsed').textContent = booksUsed;
            document.getElementById('expiredAudiobooksIncluded').textContent = audiobooksIncluded;
            document.getElementById('expiredAudiobooksUsed').textContent = audiobooksUsed;
            document.getElementById('expiredPlanPrice').textContent = `$${price}`;
            document.getElementById('expiredPlanValidity').textContent = `${validity} days`;
            
            // Store the plan ID for renewal
            currentPlanId = planId;
            
            modal.style.display = 'flex';
        }

        function renewSubscription(planId) {
            if (planId) {
                // Redirect to payment page with plan ID
                window.location.href = `payment.php?plan_id=${planId}`;
            } else if (currentPlanId) {
                // Redirect to payment page with stored plan ID
                window.location.href = `payment.php?plan_id=${currentPlanId}`;
            }
        }

        function closeModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
        }

        function closeExpiredModal() {
            document.getElementById('expiredSubscriptionModal').style.display = 'none';
            currentPlanId = null;
        }

        // Close modal when clicking outside the content
        window.onclick = function(event) {
            const modal = document.getElementById('subscriptionModal');
            const expiredModal = document.getElementById('expiredSubscriptionModal');
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === expiredModal) {
                closeExpiredModal();
            }
        }
    </script>
</body>
</html>