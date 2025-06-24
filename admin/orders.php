<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];

        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$new_status, $order_id]);

            $_SESSION['success_message'] = "Order #{$order_id} status updated to {$new_status} successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating order status: " . $e->getMessage();
        }
        header("Location: orders.php");
        exit();
    }

    if (isset($_POST['delete_order'])) {
        $order_id = $_POST['order_id'];

        try {
            // Start transaction
            $pdo->beginTransaction();

            // First delete order items
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);

            // Then delete the order
            $pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$order_id]);

            $pdo->commit();
            $_SESSION['success_message'] = "Order #{$order_id} deleted successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error deleting order: " . $e->getMessage();
        }
        header("Location: orders.php");
        exit();
    }
}

// Fetch order statistics
$stats = [
    'total_orders' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

// Fetch orders by status
$orders_by_status = [
    'pending' => [],
    'confirmed' => [],
    'shipped' => [],
    'delivered' => [],
    'cancelled' => []
];

try {
    // Get statistics
    $stats['total_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['pending'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $stats['confirmed'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'")->fetchColumn();
    $stats['shipped'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
    $stats['delivered'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
    $stats['cancelled'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();

    // Fetch orders for each status
    foreach ($orders_by_status as $status => $orders) {
        $orders_by_status[$status] = $pdo->query(
            "SELECT o.order_id, o.user_id, u.username, o.total_amount, o.order_date, o.status, 
              o.payment_method, o.shipping_address
       FROM orders o
       JOIN users u ON o.user_id = u.user_id
       WHERE o.status = '$status'
       ORDER BY o.order_date DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching orders: " . $e->getMessage();
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
    <title>Admin Dashboard - Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        body.admin-dark-mode {
            background: #1a1a1a;
            color: #f0f0f0;
        }

        .admin_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #2c3e50;
            color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .admin-dark-mode .admin_header {
            background: #1a1a1a;
            border-bottom: 1px solid #333;
        }

        .logo img {
            height: 40px;
        }

        .admin_header_right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin_theme_toggle {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .admin-dark-mode .admin_theme_toggle {
            color: #f0f0f0;
        }

        .admin_main {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        .admin_sidebar {
            width: 250px;
            background: #34495e;
            color: #fff;
        }

        .admin-dark-mode .admin_sidebar {
            background: #252525;
        }

        .admin_sidebar_nav ul {
            list-style: none;
            padding: 1rem 0;
        }

        .admin_sidebar_nav li a {
            display: flex;
            align-items: center;
            padding: .8rem 1.5rem;
            color: #fff;
            text-decoration: none;
            transition: background .3s;
        }

        .admin_sidebar_nav li a:hover,
        .admin_sidebar_nav li a.active {
            background: rgba(255, 255, 255, .1);
        }

        .admin_sidebar_nav li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .admin_main_content {
            flex: 1;
            padding: 1.5rem;
            background: #fff;
            overflow-x: auto;
        }

        .admin-dark-mode .admin_main_content {
            background: #2d2d2d;
            color: #f0f0f0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
            text-align: center;
        }

        .admin-dark-mode .stat-card {
            background: #3d3d3d;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .3);
        }

        .stat-card h3 {
            font-size: 1rem;
            margin-bottom: .5rem;
            color: #666;
        }

        .admin-dark-mode .stat-card h3 {
            color: #aaa;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .admin-dark-mode .stat-card .stat-value {
            color: #f0f0f0;
        }

        .admin_table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }

        .admin_table th,
        .admin_table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .admin-dark-mode .admin_table th,
        .admin-dark-mode .admin_table td {
            border-color: #444;
        }

        .admin_table th {
            background: #f5f5f5;
            font-weight: bold;
        }

        .admin-dark-mode .admin_table th {
            background: #3d3d3d;
        }

        .admin_table tr:hover {
            background: #f9f9f9;
        }

        .admin-dark-mode .admin_table tr:hover {
            background: #3a3a3a;
        }

        .action-btn {
            padding: .5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: .9rem;
            transition: all .3s;
            margin-right: .5rem;
        }

        .confirm-btn {
            background: #2ecc71;
            color: #fff;
        }

        .confirm-btn:hover {
            background: #27ae60;
        }

        .ship-btn {
            background: #3498db;
            color: #fff;
        }

        .ship-btn:hover {
            background: #2980b9;
        }

        .deliver-btn {
            background: #9b59b6;
            color: #fff;
        }

        .deliver-btn:hover {
            background: #8e44ad;
        }

        .cancel-btn {
            background: #e74c3c;
            color: #fff;
        }

        .cancel-btn:hover {
            background: #c0392b;
        }

        .edit-btn {
            background: #f39c12;
            color: #fff;
        }

        .edit-btn:hover {
            background: #d35400;
        }

        .delete-btn {
            background: #e74c3c;
            color: #fff;
        }

        .delete-btn:hover {
            background: #c0392b;
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

        .section-title {
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ddd;
        }

        .admin-dark-mode .section-title {
            border-color: #444;
        }

        .status-pending {
            color: #f39c12;
            font-weight: bold;
        }

        .status-confirmed {
            color: #3498db;
            font-weight: bold;
        }

        .status-shipped {
            color: #9b59b6;
            font-weight: bold;
        }

        .status-delivered {
            color: #2ecc71;
            font-weight: bold;
        }

        .status-cancelled {
            color: #e74c3c;
            font-weight: bold;
        }

        .order-details-toggle {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
        }

        .admin-dark-mode .order-details-toggle {
            color: #5dade2;
        }

        .order-details {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f9f9f9;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .admin-dark-mode .order-details {
            background: #3a3a3a;
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
                    <li><a href="admin_dashboard.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li><a href="add.php"><i class="fas fa-plus-circle"></i> Add</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                    <li><a href="writers.php"><i class="fas fa-pen-fancy"></i> Writers</a></li>
                    <li><a href="books.php"><i class="fas fa-book"></i> Books</a></li>
                    <li><a href="audiobooks.php"><i class="fas fa-headphones"></i> Audio Books</a></li>
                    <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="subscription.php"><i class="fas fa-star"></i> Subscription</a></li>
                    <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                    <li><a href="community.php"><i class="fas fa-users"></i> Community</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
        <div class="admin_main_content">
            <h2>Order Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-value"><?= $stats['total_orders'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending</h3>
                    <div class="stat-value"><?= $stats['pending'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Confirmed</h3>
                    <div class="stat-value"><?= $stats['confirmed'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Shipped</h3>
                    <div class="stat-value"><?= $stats['shipped'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Delivered</h3>
                    <div class="stat-value"><?= $stats['delivered'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Cancelled</h3>
                    <div class="stat-value"><?= $stats['cancelled'] ?></div>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <!-- Pending Orders Section -->
            <h2 class="section-title">Pending Orders</h2>
            <?php if (!empty($orders_by_status['pending'])): ?>
                <table class="admin_table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_by_status['pending'] as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id'] ?></td>
                                <td><?= htmlspecialchars($order['username']) ?> (ID: <?= $order['user_id'] ?>)</td>
                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                <td class="status-pending"><?= ucfirst($order['status']) ?></td>
                                <td>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="new_status" value="confirmed">
                                        <button type="submit" name="update_status"
                                            class="action-btn confirm-btn">Confirm</button>
                                    </form>
                                    <a href="orderedit.php?id=<?= $order['order_id'] ?>" class="action-btn edit-btn">Edit</a>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="new_status" value="cancelled">
                                        <button type="submit" name="update_status" class="action-btn cancel-btn">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending orders found.</p>
            <?php endif; ?>

            <!-- Confirmed Orders Section -->
            <h2 class="section-title">Confirmed Orders</h2>
            <?php if (!empty($orders_by_status['confirmed'])): ?>
                <table class="admin_table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_by_status['confirmed'] as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id'] ?></td>
                                <td><?= htmlspecialchars($order['username']) ?> (ID: <?= $order['user_id'] ?>)</td>
                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                <td class="status-confirmed"><?= ucfirst($order['status']) ?></td>
                                <td>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="new_status" value="shipped">
                                        <button type="submit" name="update_status" class="action-btn ship-btn">Ship</button>
                                    </form>
                                    <a href="orderedit.php?id=<?= $order['order_id'] ?>" class="action-btn edit-btn">Edit</a>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="new_status" value="cancelled">
                                        <button type="submit" name="update_status" class="action-btn cancel-btn">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No confirmed orders found.</p>
            <?php endif; ?>

            <!-- Shipped Orders Section -->
            <h2 class="section-title">Shipped Orders</h2>
            <?php if (!empty($orders_by_status['shipped'])): ?>
                <table class="admin_table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_by_status['shipped'] as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id'] ?></td>
                                <td><?= htmlspecialchars($order['username']) ?> (ID: <?= $order['user_id'] ?>)</td>
                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                <td class="status-shipped"><?= ucfirst($order['status']) ?></td>
                                <td>
                                    <a href="orderedit.php?id=<?= $order['order_id'] ?>" class="action-btn edit-btn">Edit</a>
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <input type="hidden" name="new_status" value="delivered">
                                        <button type="submit" name="update_status"
                                            class="action-btn deliver-btn">Deliver</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No shipped orders found.</p>
            <?php endif; ?>

            <!-- Delivered Orders Section -->
            <h2 class="section-title">Delivered Orders</h2>
            <?php if (!empty($orders_by_status['delivered'])): ?>
                <table class="admin_table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_by_status['delivered'] as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id'] ?></td>
                                <td><?= htmlspecialchars($order['username']) ?> (ID: <?= $order['user_id'] ?>)</td>
                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                <td class="status-delivered"><?= ucfirst($order['status']) ?></td>
                                <td>
                                    <a href="orderedit.php?id=<?= $order['order_id'] ?>" class="action-btn edit-btn">Edit</a>
                                    <form class="action-form" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this order?');">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <button type="submit" name="delete_order" class="action-btn delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No delivered orders found.</p>
            <?php endif; ?>

            <!-- Cancelled Orders Section -->
            <h2 class="section-title">Cancelled Orders</h2>
            <?php if (!empty($orders_by_status['cancelled'])): ?>
                <table class="admin_table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_by_status['cancelled'] as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id'] ?></td>
                                <td><?= htmlspecialchars($order['username']) ?> (ID: <?= $order['user_id'] ?>)</td>
                                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                <td class="status-cancelled"><?= ucfirst($order['status']) ?></td>
                                <td>
                                    <a href="orderedit.php?id=<?= $order['order_id'] ?>" class="action-btn edit-btn">Edit</a>
                                    <form class="action-form" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this order?');">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <button type="submit" name="delete_order" class="action-btn delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No cancelled orders found.</p>
            <?php endif; ?>
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