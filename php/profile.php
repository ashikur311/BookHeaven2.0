<?php
// Start session and include database connection
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authentication.php");
  exit();
}
require_once('../db_connection.php');



$user_id = $_SESSION['user_id'];

// Fetch user data
$user_query = "SELECT u.*, ui.* FROM users u 
               LEFT JOIN user_info ui ON u.user_id = ui.user_id 
               WHERE u.user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Fetch total books purchased
$books_query = "SELECT SUM(oi.quantity) as total_books 
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.user_id = ? AND o.status IN ('shipped', 'delivered')";
$stmt = $conn->prepare($books_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$books_result = $stmt->get_result();
$books_data = $books_result->fetch_assoc();
$total_books = $books_data['total_books'] ?? 0;

// Fetch active subscriptions
$subs_query = "SELECT COUNT(*) as active_subs 
               FROM user_subscriptions 
               WHERE user_id = ? AND status = 'active' AND end_date > NOW()";
$stmt = $conn->prepare($subs_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subs_result = $stmt->get_result();
$subs_data = $subs_result->fetch_assoc();
$active_subs = $subs_data['active_subs'] ?? 0;

// Fetch partner status
$partner_query = "SELECT status FROM partners WHERE user_id = ?";
$stmt = $conn->prepare($partner_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$partner_result = $stmt->get_result();
$partner_status = $partner_result->num_rows > 0 ? ucfirst($partner_result->fetch_assoc()['status']) : "Not a partner";

// Fetch total spent
$spent_query = "SELECT SUM(total_amount) as total_spent 
                FROM orders 
                WHERE user_id = ? AND status IN ('shipped', 'delivered')";
$stmt = $conn->prepare($spent_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$spent_result = $stmt->get_result();
$spent_data = $spent_result->fetch_assoc();
$total_spent = $spent_data['total_spent'] ?? 0;

// Fetch monthly purchase data for chart
$chart_query = "SELECT 
                    MONTH(order_date) as month, 
                    SUM(oi.quantity) as books_purchased
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.user_id = ? AND o.status IN ('shipped', 'delivered')
                GROUP BY MONTH(order_date)
                ORDER BY MONTH(order_date)";
$stmt = $conn->prepare($chart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$chart_result = $stmt->get_result();

// Initialize monthly data array with zeros
$monthly_data = array_fill(1, 12, 0);

while ($row = $chart_result->fetch_assoc()) {
    $monthly_data[$row['month']] = $row['books_purchased'];
}

// Format date of birth if available
$dob = isset($user_data['birthday']) ? date('F j, Y', strtotime($user_data['birthday'])) : 'Not specified';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/BookHeaven2.0/css/user_profile.css">
</head>
<body>
    <?php include_once("../header.php"); ?>
    <main>
        <aside>
            <section class="user-info">
               <img src="/BookHeaven2.0/<?php echo htmlspecialchars($user_data['user_profile'] ?? 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                            alt="<?php echo htmlspecialchars($user_data['username']); ?>" class="user-avatar">
                 <!-- <img src="/BookHeaven2.0/assets/user_image/akash.jpg" alt="" class="user-avatar"> -->
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user_data['username']); ?></div>
                    <small>Member since: <?php echo date('M Y', strtotime($user_data['create_time'])); ?></small>
                </div>
            </section>
            <section>
                <nav>
                    <ul>
                        <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wish List</a></li>
                        <li><a href="user_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                        <li><a href="user_subscription.php"><i class="fas fa-calendar-check"></i> My Subscription</a></li>
                        <li><a href="user_setting.php"><i class="fas fa-cog"></i> Setting</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </section>
        </aside>
        <div class="user_profile_content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Books Purchased</h3>
                    <p><?php echo $total_books; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active Subscriptions</h3>
                    <p><?php echo $active_subs; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Partner Status</h3>
                    <p><?php echo $partner_status; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Spent</h3>
                    <p>$<?php echo number_format($total_spent, 2); ?></p>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="purchaseChart"></canvas>
            </div>

            <div class="profile-info">
                <h2>Profile Information</h2>
                <div class="info-grid">
                    <div>
                        <div class="info-item">
                            <label>Username</label>
                            <p><?php echo htmlspecialchars($user_data['username']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Phone</label>
                            <p><?php echo htmlspecialchars($user_data['phone'] ?? 'Not specified'); ?></p>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <label>Date of Birth</label>
                            <p><?php echo $dob; ?></p>
                        </div>
                        <div class="info-item">
                            <label>Address</label>
                            <p><?php echo htmlspecialchars($user_data['address'] ?? 'Not specified'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Member Since</label>
                            <p><?php echo date('F Y', strtotime($user_data['create_time'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include_once("../footer.php");?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize chart with PHP data
            const ctx = document.getElementById('purchaseChart').getContext('2d');
            const purchaseChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Books Purchased',
                        data: [
                            <?php echo $monthly_data[1]; ?>, 
                            <?php echo $monthly_data[2]; ?>, 
                            <?php echo $monthly_data[3]; ?>, 
                            <?php echo $monthly_data[4]; ?>, 
                            <?php echo $monthly_data[5]; ?>, 
                            <?php echo $monthly_data[6]; ?>, 
                            <?php echo $monthly_data[7]; ?>, 
                            <?php echo $monthly_data[8]; ?>, 
                            <?php echo $monthly_data[9]; ?>, 
                            <?php echo $monthly_data[10]; ?>, 
                            <?php echo $monthly_data[11]; ?>, 
                            <?php echo $monthly_data[12]; ?>
                        ],
                        backgroundColor: 'rgba(87, 171, 210, 0.2)',
                        borderColor: 'rgba(87, 171, 210, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
                            },
                            grid: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--accent-color')
                            }
                        },
                        x: {
                            ticks: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
                            },
                            grid: {
                                color: getComputedStyle(document.documentElement).getPropertyValue('--accent-color')
                            }
                        }
                    }
                }
            });

            // Update chart colors when theme changes
            function updateChartColors() {
                const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-color');
                const accentColor = getComputedStyle(document.documentElement).getPropertyValue('--accent-color');
                
                purchaseChart.options.plugins.legend.labels.color = textColor;
                purchaseChart.options.scales.x.ticks.color = textColor;
                purchaseChart.options.scales.y.ticks.color = textColor;
                purchaseChart.options.scales.x.grid.color = accentColor;
                purchaseChart.options.scales.y.grid.color = accentColor;
                purchaseChart.update();
            }

            // Listen for theme changes from header
            document.body.addEventListener('themeChanged', function() {
                updateChartColors();
            });
        });
    </script>
</body>
</html>