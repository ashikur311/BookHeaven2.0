<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Initialize variables
$subscription = [];
$error_message = '';
$success_message = '';

// Fetch subscription plan data if ID is provided
if (isset($_GET['id'])) {
    $subscription_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE plan_id = ?");
        $stmt->execute([$subscription_id]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subscription) {
            $_SESSION['error_message'] = "Subscription plan not found!";
            header("Location: subscription.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error fetching subscription: " . $e->getMessage();
        header("Location: subscription.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subscription'])) {
    // Validate and sanitize input
    $plan_id = $_POST['plan_id'];
    $plan_name = trim($_POST['plan_name']);
    $price = $_POST['price'];
    $validity_days = $_POST['validity_days'];
    $book_quantity = $_POST['book_quantity'];
    $audiobook_quantity = $_POST['audiobook_quantity'];
    $description = trim($_POST['plan_description']);
    $status = $_POST['status'];

    try {
        // Update subscription plan
        $stmt = $pdo->prepare("UPDATE subscription_plans SET 
            plan_name = ?, 
            price = ?, 
            validity_days = ?, 
            book_quantity = ?, 
            audiobook_quantity = ?, 
            description = ?, 
            status = ? 
            WHERE plan_id = ?");

        $stmt->execute([
            $plan_name,
            $price,
            $validity_days,
            $book_quantity,
            $audiobook_quantity,
            $description,
            $status,
            $plan_id
        ]);

        $_SESSION['success_message'] = "Subscription plan updated successfully!";
        header("Location: subscription.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error updating subscription plan: " . $e->getMessage();
    }
}

// Get messages from session
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $error_message ?: ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Edit Subscription</title>
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
            color: #fff;
            transition: width 0.3s;
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

        /* Form Styles */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .admin-dark-mode .form-container {
            background: #3d3d3d;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .form-title {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }

        .admin-dark-mode .form-title {
            color: #f0f0f0;
            border-color: #2980b9;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .admin-dark-mode .form-group label {
            color: #f0f0f0;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .admin-dark-mode .form-control {
            background: #4d4d4d;
            border-color: #555;
            color: #f0f0f0;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
        }

        .subscription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .admin-dark-mode .subscription-header {
            border-color: #444;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
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

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
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

        /* Responsive */
        @media (max-width: 768px) {
            .admin_main {
                flex-direction: column;
            }

            .admin_sidebar {
                width: 100%;
            }

            .form-row {
                flex-direction: column;
                gap: 1rem;
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
                    <li><a href="admin_dashboard.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li><a href="add.php"><i class="fas fa-plus-circle"></i> Add</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                    <li><a href="writers.php"><i class="fas fa-pen-fancy"></i> Writers</a></li>
                    <li><a href="books.php"><i class="fas fa-book"></i> Books</a></li>
                    <li><a href="audiobooks.php"><i class="fas fa-headphones"></i> Audio Books</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="subscription.php" class="active"><i class="fas fa-star"></i> Subscription</a></li>
                    <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                    <li><a href="community.php"><i class="fas fa-users"></i> Community</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
        <div class="admin_main_content">
            <div class="form-container">
               
                <div class="subscription-header">
 <h1 class="form-title">Edit Subscription Plan</h1>
 <a href="subscription.php" class="btn btn-secondary">Back to Subscription</a>
                </div>
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <form method="POST" action="edit_subscription.php?id=<?= $subscription['plan_id'] ?>">
                    <input type="hidden" name="plan_id" value="<?= $subscription['plan_id'] ?>">

                    <div class="form-group">
                        <label for="plan_name">Plan Name</label>
                        <input type="text" id="plan_name" name="plan_name" class="form-control"
                            value="<?= htmlspecialchars($subscription['plan_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($)</label>
                            <input type="number" id="price" name="price" class="form-control"
                                value="<?= htmlspecialchars($subscription['price'] ?? '') ?>" step="0.01" min="0"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="validity_days">Validity (Days)</label>
                            <input type="number" id="validity_days" name="validity_days" class="form-control"
                                value="<?= htmlspecialchars($subscription['validity_days'] ?? '') ?>" min="1" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="book_quantity">Book Quantity</label>
                            <input type="number" id="book_quantity" name="book_quantity" class="form-control"
                                value="<?= htmlspecialchars($subscription['book_quantity'] ?? '') ?>" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="audiobook_quantity">Audiobook Quantity</label>
                            <input type="number" id="audiobook_quantity" name="audiobook_quantity" class="form-control"
                                value="<?= htmlspecialchars($subscription['audiobook_quantity'] ?? '') ?>" min="0"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="plan_description">Description</label>
                        <textarea id="plan_description" name="plan_description" class="form-control"
                            rows="4"><?= htmlspecialchars($subscription['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?= ($subscription['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                                Active</option>
                            <option value="inactive" <?= ($subscription['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="subscription.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="update_subscription" class="btn btn-primary">Update Plan</button>
                    </div>
                </form>
            </div>
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