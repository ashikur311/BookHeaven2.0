<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Handle partner approval/cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_partner'])) {
        $partner_id = $_POST['partner_id'];
        try {
            $stmt = $pdo->prepare("UPDATE partners SET status = 'approved' WHERE partner_id = ?");
            $stmt->execute([$partner_id]);
            $_SESSION['success_message'] = "Partner request approved successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error approving partner: " . $e->getMessage();
        }
        header("Location: admin_dashboard.php");
        exit();
    } elseif (isset($_POST['cancel_partner'])) {
        $partner_id = $_POST['partner_id'];
        try {
            $stmt = $pdo->prepare("UPDATE partners SET status = 'suspended' WHERE partner_id = ?");
            $stmt->execute([$partner_id]);
            $_SESSION['success_message'] = "Partner request cancelled successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error cancelling partner: " . $e->getMessage();
        }
        header("Location: admin_dashboard.php");
        exit();
    }
}

// Fetch stats from database
$stats = [
    'total_users' => 0,
    'total_books' => 0,
    'total_partners' => 0,
    'total_audiobooks' => 0,
    'total_writers' => 0,
    'total_sales_month' => 0,
    'total_orders' => 0
];

try {
    // Get total users
    $stats['total_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Get total books
    $stats['total_books'] = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
    
    // Get total partners
    $stats['total_partners'] = (int)$pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn();
    
    // Get total audiobooks
    $stats['total_audiobooks'] = (int)$pdo->query("SELECT COUNT(*) FROM audiobooks")->fetchColumn();
    
    // Get total writers
    $stats['total_writers'] = (int)$pdo->query("SELECT COUNT(*) FROM writers")->fetchColumn();
    
    // Get total sales this month
    $stats['total_sales_month'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM orders 
         WHERE order_date >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
           AND order_date < DATE_FORMAT(CURRENT_DATE() + INTERVAL 1 MONTH, '%Y-%m-01')"
    )->fetchColumn();
    
    // Get total orders
    $stats['total_orders'] = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    // Get monthly sales data for chart
    $monthly_sales_data = $pdo->query(
        "SELECT 
            DATE_FORMAT(order_date, '%b') AS month,
            COUNT(*) AS count
         FROM orders
         WHERE order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
         GROUP BY DATE_FORMAT(order_date, '%Y-%m'), DATE_FORMAT(order_date, '%b')
         ORDER BY DATE_FORMAT(order_date, '%Y-%m')"
    )->fetchAll(PDO::FETCH_ASSOC);
    
    // Format monthly sales for chart
    $monthly_sales = ['labels' => [], 'data' => []];
    foreach ($monthly_sales_data as $sale) {
        $monthly_sales['labels'][] = $sale['month'];
        $monthly_sales['data'][] = $sale['count'];
    }
    
    // Get subscription sales data
    $subscription_sales_data = $pdo->query(
        "SELECT 
            sp.plan_name,
            COUNT(so.id) AS count
         FROM subscription_plans sp
         LEFT JOIN subscription_orders so ON sp.plan_id = so.plan_id
         GROUP BY sp.plan_id"
    )->fetchAll(PDO::FETCH_ASSOC);
    
    // Format subscription sales for chart
    $subscription_sales = ['labels' => [], 'data' => []];
    foreach ($subscription_sales_data as $sale) {
        $subscription_sales['labels'][] = $sale['plan_name'];
        $subscription_sales['data'][] = $sale['count'];
    }
    
    // Get user growth data
    $user_growth_data = $pdo->query(
        "SELECT 
            DATE_FORMAT(create_time, '%b') AS month,
            COUNT(*) AS count
         FROM users
         WHERE create_time >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
         GROUP BY DATE_FORMAT(create_time, '%Y-%m'), DATE_FORMAT(create_time, '%b')
         ORDER BY DATE_FORMAT(create_time, '%Y-%m')"
    )->fetchAll(PDO::FETCH_ASSOC);
    
    // Format user growth for chart
    $user_growth = ['labels' => [], 'data' => []];
    foreach ($user_growth_data as $growth) {
        $user_growth['labels'][] = $growth['month'];
        $user_growth['data'][] = $growth['count'];
    }
    
    // Get pending orders
    $pending_orders = $pdo->query(
        "SELECT 
            o.order_id AS id,
            u.username AS user_name,
            DATE_FORMAT(o.order_date, '%Y-%m-%d') AS date,
            o.total_amount AS amount
         FROM orders o
         JOIN users u ON o.user_id = u.user_id
         WHERE o.status = 'pending'
         ORDER BY o.order_date DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending partners
    $pending_partners = $pdo->query(
        "SELECT 
            p.partner_id AS id,
            u.username AS name,
            DATE_FORMAT(p.joined_at, '%Y-%m-%d') AS joined_date,
            p.status
         FROM partners p
         JOIN users u ON p.user_id = u.user_id
         WHERE p.status = 'pending'
         ORDER BY p.joined_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching data: " . $e->getMessage();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="/BookHeaven2.0/css/admin_dashboard.css">
</head>

<body>
  <header class="admin_header">
    <div class="logo"><img src="images/logo.png" alt="Logo"></div>
    <div class="admin_header_right">
      <h1>Admin Dashboard</h1>
      <p>Welcome, Admin</p>
      <button class="admin_theme_toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
    </div>
  </header>
  <main class="admin_main">
    <aside class="admin_sidebar">
      <nav class="admin_sidebar_nav">
        <ul>
          <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li><a href="add.php"><i class="fas fa-plus-circle"></i> Add</a></li>
          <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
          <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
          <li><a href="writers.php"><i class="fas fa-pen-fancy"></i> Writers</a></li>
          <li><a href="books.php"><i class="fas fa-book"></i> Books</a></li>
          <li><a href="audiobooks.php"><i class="fas fa-headphones"></i> Audio Books</a></li>
          <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
          <li><a href="subscription.php"><i class="fas fa-star"></i> Subscription</a></li>
          <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
          <li><a href="community.php"><i class="fas fa-users"></i> Community</a></li>
          <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
          <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </nav>
    </aside>
    <div class="admin_main_content">
      <?php if ($error_message): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>
      <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>
      
      <h1>Dashboard Overview</h1>
      
      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Users</h3>
          <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Total Books</h3>
          <div class="stat-value"><?= number_format($stats['total_books']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Total Partners</h3>
          <div class="stat-value"><?= number_format($stats['total_partners']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Audio Books</h3>
          <div class="stat-value"><?= number_format($stats['total_audiobooks']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Total Writers</h3>
          <div class="stat-value"><?= number_format($stats['total_writers']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Sales This Month</h3>
          <div class="stat-value"><?= number_format($stats['total_sales_month']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Total Orders</h3>
          <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
        </div>
      </div>
      
      <!-- Charts Section -->
      <h2 class="section-title">Performance Metrics</h2>
      <div class="charts-section">
        <div class="chart-container">
          <h3>Monthly Sales</h3>
          <canvas id="salesChart"></canvas>
        </div>
        <div class="chart-container">
          <h3>Subscription Sales</h3>
          <canvas id="subscriptionChart"></canvas>
        </div>
        <div class="chart-container">
          <h3>User Growth</h3>
          <canvas id="usersChart"></canvas>
        </div>
      </div>
      
      <!-- Pending Orders Section -->
      <h2 class="section-title">Pending Orders</h2>
      <table class="admin_table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>User Name</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending_orders as $order): ?>
            <tr>
              <td>#<?= $order['id'] ?></td>
              <td><?= htmlspecialchars($order['user_name']) ?></td>
              <td><?= $order['date'] ?></td>
              <td>$<?= number_format($order['amount'], 2) ?></td>
              <td>
                <a href="orderedit.php?id=<?= $order['id'] ?>" class="action-btn view-btn">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <!-- Pending Partners Section -->
      <h2 class="section-title">Pending Partner Requests</h2>
      <table class="admin_table">
        <thead>
          <tr>
            <th>Partner ID</th>
            <th>Name</th>
            <th>Joined Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending_partners as $partner): ?>
            <tr>
              <td>#<?= $partner['id'] ?></td>
              <td><?= htmlspecialchars($partner['name']) ?></td>
              <td><?= $partner['joined_date'] ?></td>
              <td><?= $partner['status'] ?></td>
              <td>
                <form method="POST" class="action-form" onsubmit="return confirm('Are you sure you want to approve this partner?');">
                  <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                  <button type="submit" name="approve_partner" class="action-btn approve-btn">Approve</button>
                </form>
                <form method="POST" class="action-form" onsubmit="return confirm('Are you sure you want to cancel this partner request?');">
                  <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                  <button type="submit" name="cancel_partner" class="action-btn cancel-btn">Cancel</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
  
  <script>
    // Theme toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle.querySelector('i');

    // Check for saved theme preference or use system preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (localStorage.getItem('admin-theme') === 'dark' || (!localStorage.getItem('admin-theme') && prefersDark)) {
      document.body.classList.add('admin-dark-mode');
      icon.classList.replace('fa-moon', 'fa-sun');
    }

    // Toggle theme on button click
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('admin-dark-mode');
      if (document.body.classList.contains('admin-dark-mode')) {
        localStorage.setItem('admin-theme', 'dark');
        icon.classList.replace('fa-moon', 'fa-sun');
        updateChartsForDarkMode();
      } else {
        localStorage.setItem('admin-theme', 'light');
        icon.classList.replace('fa-sun', 'fa-moon');
        updateChartsForLightMode();
      }
    });

    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
      // Sales Chart
      const salesCtx = document.getElementById('salesChart').getContext('2d');
      const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
          labels: <?= json_encode($monthly_sales['labels']) ?>,
          datasets: [{
            label: 'Monthly Sales',
            data: <?= json_encode($monthly_sales['data']) ?>,
            backgroundColor: 'rgba(52, 152, 219, 0.2)',
            borderColor: 'rgba(52, 152, 219, 1)',
            borderWidth: 2,
            tension: 0.1,
            fill: true
          }]
        },
        options: getChartOptions('Sales')
      });

      // Subscription Chart
      const subscriptionCtx = document.getElementById('subscriptionChart').getContext('2d');
      const subscriptionChart = new Chart(subscriptionCtx, {
        type: 'doughnut',
        data: {
          labels: <?= json_encode($subscription_sales['labels']) ?>,
          datasets: [{
            data: <?= json_encode($subscription_sales['data']) ?>,
            backgroundColor: [
              'rgba(52, 152, 219, 0.7)',
              'rgba(46, 204, 113, 0.7)',
              'rgba(241, 196, 15, 0.7)'
            ],
            borderWidth: 1
          }]
        },
        options: getChartOptions('Subscriptions')
      });

      // Users Chart
      const usersCtx = document.getElementById('usersChart').getContext('2d');
      const usersChart = new Chart(usersCtx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($user_growth['labels']) ?>,
          datasets: [{
            label: 'User Growth',
            data: <?= json_encode($user_growth['data']) ?>,
            backgroundColor: 'rgba(155, 89, 182, 0.7)',
            borderColor: 'rgba(155, 89, 182, 1)',
            borderWidth: 1
          }]
        },
        options: getChartOptions('Users')
      });

      // Update charts if in dark mode initially
      if (document.body.classList.contains('admin-dark-mode')) {
        updateChartsForDarkMode();
      }
    });

    function getChartOptions(title) {
      const isDarkMode = document.body.classList.contains('admin-dark-mode');
      const textColor = isDarkMode ? '#f0f0f0' : '#666';
      const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
      
      return {
        responsive: true,
        plugins: {
          legend: {
            labels: {
              color: textColor
            }
          },
          title: {
            display: true,
            text: title,
            color: textColor
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: gridColor
            },
            ticks: {
              color: textColor
            }
          },
          x: {
            grid: {
              color: gridColor
            },
            ticks: {
              color: textColor
            }
          }
        }
      };
    }

    function updateChartsForDarkMode() {
      // This function would be called when switching to dark mode
      // In a real implementation, you would update all chart instances
      console.log("Updating charts for dark mode");
    }

    function updateChartsForLightMode() {
      // This function would be called when switching to light mode
      console.log("Updating charts for light mode");
    }
  </script>
</body>

</html>