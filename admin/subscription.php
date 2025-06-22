<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: authentication.php");
  exit();
}

// Fetch subscription statistics
$stats = [
  'total_plans' => 0,
  'total_sales' => 0,
  'premium_sales' => 0,
  'gold_sales' => 0,
  'basic_sales' => 0
];

// Fetch subscription plans
$subscription_plans = [];

try {
  // Get total number of subscription plans
  $stats['total_plans'] = (int) $pdo->query("SELECT COUNT(*) FROM subscription_plans")->fetchColumn();

  // Get total subscription sales
  $stats['total_sales'] = (int) $pdo->query("SELECT COUNT(*) FROM subscription_orders")->fetchColumn();

  // Get sales by plan type
  $sales_by_plan = $pdo->query(
    "SELECT 
            sp.plan_name,
            COUNT(so.id) AS count
         FROM subscription_plans sp
         LEFT JOIN subscription_orders so ON sp.plan_id = so.plan_id
         GROUP BY sp.plan_id"
  )->fetchAll(PDO::FETCH_ASSOC);

  foreach ($sales_by_plan as $sale) {
    if ($sale['plan_name'] === 'Premium') {
      $stats['premium_sales'] = $sale['count'];
    } elseif ($sale['plan_name'] === 'Gold') {
      $stats['gold_sales'] = $sale['count'];
    } elseif ($sale['plan_name'] === 'Basic') {
      $stats['basic_sales'] = $sale['count'];
    }
  }

  // Get all subscription plans
  $subscription_plans = $pdo->query(
    "SELECT 
            plan_id,
            plan_name,
            price,
            validity_days,
            book_quantity,
            audiobook_quantity,
            description,
            status
         FROM subscription_plans
         ORDER BY price DESC"
  )->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  $_SESSION['error_message'] = "Error fetching subscription data: " . $e->getMessage();
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
  <title>Admin Dashboard - Subscription</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Base Styles */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      color: #333;
      line-height: 1.6
    }

    body.admin-dark-mode {
      background: #1a1a1a;
      color: #f0f0f0
    }

    .admin_header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      background: #2c3e50;
      color: #fff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1)
    }

    .admin-dark-mode .admin_header {
      background: #1a1a1a;
      border-bottom: 1px solid #333
    }

    .logo img {
      height: 40px
    }

    .admin_header_right {
      display: flex;
      align-items: center;
      gap: 1rem
    }

    .admin_theme_toggle {
      background: none;
      border: none;
      color: #fff;
      font-size: 1.2rem;
      cursor: pointer
    }

    .admin-dark-mode .admin_theme_toggle {
      color: #f0f0f0
    }

    .admin_main {
      display: flex;
      min-height: calc(100vh - 70px)
    }

    .admin_sidebar {
      width: 250px;
      background: #34495e;
      color: #fff
    }

    .admin-dark-mode .admin_sidebar {
      background: #252525
    }

    .admin_sidebar_nav ul {
      list-style: none;
      padding: 1rem 0
    }

    .admin_sidebar_nav li a {
      display: flex;
      align-items: center;
      padding: .8rem 1.5rem;
      color: #fff;
      text-decoration: none;
      transition: background .3s
    }

    .admin_sidebar_nav li a:hover,
    .admin_sidebar_nav li a.active {
      background: rgba(255, 255, 255, .1)
    }

    .admin_sidebar_nav li a i {
      margin-right: 10px;
      width: 20px;
      text-align: center
    }

    .admin_main_content {
      flex: 1;
      padding: 1.5rem;
      background: #fff;
      overflow-x: auto
    }

    .admin-dark-mode .admin_main_content {
      background: #2d2d2d;
      color: #f0f0f0
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem
    }

    .stat-card {
      background: #fff;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
      text-align: center
    }

    .admin-dark-mode .stat-card {
      background: #3d3d3d;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .3)
    }

    .stat-card h3 {
      font-size: 1rem;
      margin-bottom: .5rem;
      color: #666
    }

    .admin-dark-mode .stat-card h3 {
      color: #aaa
    }

    .stat-card .stat-value {
      font-size: 2rem;
      font-weight: bold;
      color: #2c3e50
    }

    .admin-dark-mode .stat-card .stat-value {
      color: #f0f0f0
    }

    /* Tables Section */
    .section-title {
      margin: 2rem 0 1rem;
      color: #2c3e50;
      font-size: 1.5rem;
    }

    .admin-dark-mode .section-title {
      color: #f0f0f0;
    }

    .admin_table {
      width: 100%;
      border-collapse: collapse;
      margin: 1.5rem 0
    }

    .admin_table th,
    .admin_table td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #ddd
    }

    .admin-dark-mode .admin_table th,
    .admin-dark-mode .admin_table td {
      border-color: #444
    }

    .admin_table th {
      background: #f5f5f5;
      font-weight: bold
    }

    .admin-dark-mode .admin_table th {
      background: #3d3d3d
    }

    .admin_table tr:hover {
      background: #f9f9f9
    }

    .admin-dark-mode .admin_table tr:hover {
      background: #3a3a3a
    }

    .action-btn {
      padding: .5rem 1rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: .9rem;
      transition: all .3s
    }

    .edit-btn {
      background: #3498db;
      color: #fff;
      margin-right: .5rem
    }

    .edit-btn:hover {
      background: #2980b9
    }

    .delete-btn {
      background: #e74c3c;
      color: #fff
    }

    .delete-btn:hover {
      background: #c0392b
    }

    .alert {
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 4px;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .admin-dark-mode .alert-error {
      background-color: #3a1a1d;
      color: #ffb8c6;
      border-color: #4d2227;
    }

    .admin-dark-mode .alert-success {
      background-color: #1a3a24;
      color: #a3ffc2;
      border-color: #224d2e;
    }

    .action-form {
      display: inline;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .admin_main {
        flex-direction: column;
      }

      .admin_sidebar {
        width: 100%;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .admin_table {
        display: block;
        overflow-x: auto;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .admin_header_right {
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
      }

      .action-btn {
        padding: .3rem .6rem;
        font-size: .8rem;
      }
    }
  </style>
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

      <h1>Subscription Management</h1>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Plans</h3>
          <div class="stat-value"><?= number_format($stats['total_plans']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Total Sales</h3>
          <div class="stat-value"><?= number_format($stats['total_sales']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Premium Sales</h3>
          <div class="stat-value"><?= number_format($stats['premium_sales']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Gold Sales</h3>
          <div class="stat-value"><?= number_format($stats['gold_sales']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Basic Sales</h3>
          <div class="stat-value"><?= number_format($stats['basic_sales']) ?></div>
        </div>
      </div>

      <!-- Subscription Plans Table -->
      <h2 class="section-title">Subscription Plans</h2>
      <table class="admin_table">
        <thead>
          <tr>
            <th>Plan ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Validity (Days)</th>
            <th>Books</th>
            <th>Audiobooks</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subscription_plans as $plan): ?>
            <tr>
              <td>#<?= $plan['plan_id'] ?></td>
              <td><?= htmlspecialchars($plan['plan_name']) ?></td>
              <td>$<?= number_format($plan['price'], 2) ?></td>
              <td><?= $plan['validity_days'] ?></td>
              <td><?= $plan['book_quantity'] ?></td>
              <td><?= $plan['audiobook_quantity'] ?></td>
              <td><?= ucfirst($plan['status']) ?></td>
              <td>
                <a href="edit_subscription.php?id=<?= $plan['plan_id'] ?>" class="action-btn edit-btn">Edit</a>
                <form class="action-form" method="POST" action="delete_subscription.php"
                  onsubmit="return confirm('Are you sure you want to delete this subscription plan?');">
                  <input type="hidden" name="plan_id" value="<?= $plan['plan_id'] ?>">
                  <button type="submit" class="action-btn delete-btn">Delete</button>
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
      } else {
        localStorage.setItem('admin-theme', 'light');
        icon.classList.replace('fa-sun', 'fa-moon');
      }
    });
  </script>
</body>

</html>