<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

$order_id = $_GET['id'] ?? 0;

// Fetch order details
$order = [];
$order_items = [];

try {
    // Get order information
    $stmt = $pdo->prepare(
        "SELECT o.*, u.username, u.email 
         FROM orders o
         JOIN users u ON o.user_id = u.user_id
         WHERE o.order_id = ?"
    );
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['error_message'] = "Order not found";
        header("Location: orders.php");
        exit();
    }

    // Get order items (modified to match your database structure)
    $stmt = $pdo->prepare(
        "SELECT oi.*, b.title 
         FROM order_items oi
         JOIN books b ON oi.book_id = b.book_id
         WHERE oi.order_id = ?
         ORDER BY oi.id"
    );
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching order details: " . $e->getMessage();
    header("Location: orders.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $order['status'];
    $shipping_address = $_POST['shipping_address'] ?? $order['shipping_address'];
    $payment_method = $_POST['payment_method'] ?? $order['payment_method'];
    $notes = $_POST['notes'] ?? $order['notes'];

    try {
        $stmt = $pdo->prepare(
            "UPDATE orders 
             SET status = ?, shipping_address = ?, payment_method = ?, notes = ?
             WHERE order_id = ?"
        );
        $stmt->execute([$status, $shipping_address, $payment_method, $notes, $order_id]);

        $_SESSION['success_message'] = "Order #$order_id updated successfully!";
        header("Location: orders.php");
        exit();

    } catch (PDOException $e) {
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? $error_message ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Edit Order #<?= $order_id ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reuse the same styles from orders.php */
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

        .order-edit-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .admin-dark-mode .order-header {
            border-color: #444;
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .order-section {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
            margin-bottom: 1.5rem;
        }

        .admin-dark-mode .order-section {
            background: #3d3d3d;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .3);
        }

        .order-section h3 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #ddd;
        }

        .admin-dark-mode .order-section h3 {
            border-color: #444;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .admin-dark-mode .form-control {
            background: #4d4d4d;
            border-color: #555;
            color: #f0f0f0;
        }

        select.form-control {
            height: 42px;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all .3s;
        }

        .btn-primary {
            background: #3498db;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #95a5a6;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }

        .items-table th,
        .items-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .admin-dark-mode .items-table th,
        .admin-dark-mode .items-table td {
            border-color: #444;
        }

        .items-table th {
            background: #f5f5f5;
            font-weight: bold;
        }

        .admin-dark-mode .items-table th {
            background: #3d3d3d;
        }

        .items-table tr:hover {
            background: #f9f9f9;
        }

        .admin-dark-mode .items-table tr:hover {
            background: #3a3a3a;
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

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
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
            <div class="order-edit-container">
                <div class="order-header">
                    <h2>Edit Order #<?= $order_id ?></h2>
                    <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="order-details-grid">
                        <div class="order-section">
                            <h3>Order Information</h3>

                            <div class="form-group">
                                <label for="status">Order Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending
                                    </option>
                                    <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>
                                        Confirmed</option>
                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped
                                    </option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>
                                        Delivered</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>
                                        Cancelled</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-control">
                                    <option value="cod" <?= $order['payment_method'] === 'cod' ? 'selected' : '' ?>>Cash on
                                        Delivery</option>
                                    <option value="online" <?= $order['payment_method'] === 'online' ? 'selected' : '' ?>>
                                        Online Payment</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="shipping_address">Shipping Address</label>
                                <textarea id="shipping_address" name="shipping_address" class="form-control"
                                    rows="4"><?= htmlspecialchars($order['shipping_address']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="notes">Admin Notes</label>
                                <textarea id="notes" name="notes" class="form-control"
                                    rows="4"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="order-section">
                            <h3>Customer Information</h3>

                            <div class="form-group">
                                <label>Customer Name</label>
                                <p><?= htmlspecialchars($order['username']) ?></p>
                            </div>

                            <div class="form-group">
                                <label>Customer Email</label>
                                <p><?= htmlspecialchars($order['email']) ?></p>
                            </div>

                            <div class="form-group">
                                <label>Order Date</label>
                                <p><?= date('M j, Y H:i', strtotime($order['order_date'])) ?></p>
                            </div>

                            <div class="form-group">
                                <label>Total Amount</label>
                                <p>$<?= number_format($order['total_amount'], 2) ?></p>
                            </div>

                            <div class="form-group">
                                <label>Current Status</label>
                                <p class="status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="order-section">
                        <h3>Order Items</h3>

                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['title']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right text-bold">Total:</td>
                                    <td class="text-bold">$<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Update Order</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script>
        // Theme toggle functionality (same as orders.php)
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