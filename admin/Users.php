<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Handle user deletion...
// (unchanged)

// Get all users
$users = [];
$stats = [
    'total_users' => 0,
    'new_this_month' => 0,
    'active_users' => 0,
    'inactive_users' => 0
];

try {
    // 1) Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = (int) $stmt->fetchColumn();

    // 2) New users this month (from 1st day of this month to before next month)
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM users
        WHERE create_time >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
          AND create_time <  DATE_FORMAT(CURRENT_DATE() + INTERVAL 1 MONTH, '%Y-%m-01')
    ");
    $stats['new_this_month'] = (int) $stmt->fetchColumn();

    // 3) Active users (distinct user_ids in user_activities with status='active')
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT user_id) 
        FROM user_activities 
        WHERE status = 'active'
    ");
    $stats['active_users'] = (int) $stmt->fetchColumn();

    // 4) Inactive users = total minus active
    $stats['inactive_users'] = $stats['total_users'] - $stats['active_users'];

    // Get all users with their info (unchanged)
    $stmt = $pdo->query("
        SELECT u.user_id, u.username, u.email, u.create_time, 
               ui.userimageurl, ui.phone, ui.address
        FROM users u
        LEFT JOIN user_info ui ON u.user_id = ui.user_id
        ORDER BY u.create_time DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching users: " . $e->getMessage();
}

// Display success/error messages (unchanged)
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>User Management - Admin Dashboard</title>
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
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        body.admin-dark-mode {
            background-color: #1a1a1a;
            color: #f0f0f0;
        }

        /* Header Styles */
        .admin_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background-color: #2c3e50;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .admin-dark-mode .admin_header {
            background-color: #1a1a1a;
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
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }

        /* Main Layout */
        .admin_main {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        /* Sidebar Styles */
        .admin_sidebar {
            width: 250px;
            background-color: #34495e;
            color: white;
            transition: transform 0.3s ease;
        }

        .admin-dark-mode .admin_sidebar {
            background-color: #252525;
        }

        .admin_sidebar_nav ul {
            list-style: none;
            padding: 1rem 0;
        }

        .admin_sidebar_nav li a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .admin_sidebar_nav li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .admin_sidebar_nav li a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .admin_sidebar_nav li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .admin_main_content {
            flex: 1;
            padding: 1.5rem;
            overflow-x: auto;
            background-color: #fff;
        }

        .admin-dark-mode .admin_main_content {
            background-color: #2d2d2d;
            color: #f0f0f0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .admin-dark-mode .stat-card {
            background-color: #3d3d3d;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .stat-card h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
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

        /* Table Styles */
        .admin_table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        .admin_table th,
        .admin_table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .admin-dark-mode .admin_table th,
        .admin-dark-mode .admin_table td {
            border-bottom-color: #444;
        }

        .admin_table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .admin-dark-mode .admin_table th {
            background-color: #3d3d3d;
        }

        .admin_table tr:hover {
            background-color: #f9f9f9;
        }

        .admin-dark-mode .admin_table tr:hover {
            background-color: #3a3a3a;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .view-btn {
            background-color: #3498db;
            color: white;
            margin-right: 0.5rem;
        }

        .admin-dark-mode .view-btn {
            background-color: #5dade2;
        }

        .view-btn:hover {
            background-color: #2980b9;
        }

        .admin-dark-mode .view-btn:hover {
            background-color: #4aa3df;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        /* Modal Styles */
        .admin_modal {
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

        .admin_modal_content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 5px;
            width: 90%;
            max-width: 600px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .admin-dark-mode .admin_modal_content {
            background-color: #2d2d2d;
        }

        .admin_modal_close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
        }

        .admin-dark-mode .admin_modal_close {
            color: #f0f0f0;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .user-details-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-details-info {
            width: 100%;
        }

        .user-details-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }

        .admin-dark-mode .user-details-row {
            border-bottom-color: #444;
        }

        .user-details-label {
            font-weight: bold;
            color: #666;
        }

        .admin-dark-mode .user-details-label {
            color: #aaa;
        }

        .user-details-value {
            text-align: right;
        }

        /* Confirmation Modal */
        .confirmation-modal {
            text-align: center;
        }

        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .confirm-btn {
            background-color: #e74c3c;
            color: white;
        }

        .cancel-btn {
            background-color: #3498db;
            color: white;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .admin-dark-mode .alert-success {
            background-color: #1e3a24;
            color: #d4edda;
            border-color: #2a4b2f;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .admin-dark-mode .alert-error {
            background-color: #3a1e22;
            color: #f8d7da;
            border-color: #4a2a2f;
        }

        /* Mobile Styles */
        @media (max-width: 992px) {
            .admin_main {
                flex-direction: column;
            }

            .admin_sidebar {
                width: 100%;
                position: static;
                transform: none;
            }

            .admin_main_content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Small Mobile Screens */
        @media (max-width: 576px) {
            .admin_header {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }

            .admin_header_right {
                flex-direction: column;
                gap: 0.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .admin_table {
                display: block;
                overflow-x: auto;
            }

            .action-btns {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .view-btn,
            .delete-btn {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>

<body>
    <header>
        <nav class="admin_header">
            <div class="logo">
                <img src="images/logo.png" alt="Logo">
            </div>
            <div class="admin_header_right">
                <h1>Admin Dashboard</h1>
                <p>Welcome, Admin</p>
                <button class="admin_theme_toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </nav>
    </header>
    <main class="admin_main">
        <aside class="admin_sidebar">
            <nav class="admin_sidebar_nav">
                <ul>
                    <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
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
            <h2>User Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-value"><?= $stats['total_users'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>New This Month</h3>
                    <div class="stat-value"><?= $stats['new_this_month'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Users</h3>
                    <div class="stat-value"><?= $stats['active_users'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Inactive Users</h3>
                    <div class="stat-value"><?= $stats['inactive_users'] ?></div>
                </div>
            </div>

            <!-- Display success/error messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <h2>User Management</h2>
            <table class="admin_table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>User Image</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= $user['user_id'] ?></td>
                            <td>
                                <img src="<?php echo $user['userimageurl'] ? $user['userimageurl'] : 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'; ?>"
                                    alt="user profile" class="user-avatar">
                            </td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= date('Y-m-d', strtotime($user['create_time'])) ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn view-btn" onclick="showUserDetails(<?= $user['user_id'] ?>)">
                                        View
                                    </button>
                                    <button class="action-btn delete-btn"
                                        onclick="confirmDelete(<?= $user['user_id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- User Details Modal -->
            <div id="userDetailsModal" class="admin_modal">
                <div class="admin_modal_content">
                    <span class="admin_modal_close" onclick="closeModal()">&times;</span>
                    <h2>User Details</h2>
                    <div class="user-details">
                        <img id="modalUserImage" src="" alt="User" class="user-details-avatar">
                        <div class="user-details-info">
                            <div class="user-details-row">
                                <span class="user-details-label">User ID:</span>
                                <span class="user-details-value" id="modalUserId"></span>
                            </div>
                            <div class="user-details-row">
                                <span class="user-details-label">Username:</span>
                                <span class="user-details-value" id="modalUsername"></span>
                            </div>
                            <div class="user-details-row">
                                <span class="user-details-label">Email:</span>
                                <span class="user-details-value" id="modalEmail"></span>
                            </div>
                            <div class="user-details-row">
                                <span class="user-details-label">Phone:</span>
                                <span class="user-details-value" id="modalPhone"></span>
                            </div>
                            <div class="user-details-row">
                                <span class="user-details-label">Joined Date:</span>
                                <span class="user-details-value" id="modalJoinedDate"></span>
                            </div>
                            <div class="user-details-row">
                                <span class="user-details-label">Address:</span>
                                <span class="user-details-value" id="modalAddress"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteConfirmationModal" class="admin_modal">
                <div class="admin_modal_content confirmation-modal">
                    <span class="admin_modal_close" onclick="closeModal()">&times;</span>
                    <h2>Confirm Deletion</h2>
                    <p id="confirmationMessage">Are you sure you want to delete this user?</p>
                    <form id="deleteForm" method="POST" style="display: none;">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <input type="hidden" name="delete_user" value="1">
                    </form>
                    <div class="confirmation-buttons">
                        <button class="action-btn confirm-btn" onclick="document.getElementById('deleteForm').submit()">
                            Confirm Delete
                        </button>
                        <button class="action-btn cancel-btn" onclick="closeModal()">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle.querySelector('i');

        // Check for saved theme preference or use preferred color scheme
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
        const currentTheme = localStorage.getItem('admin-theme');

        if (currentTheme === 'dark' || (!currentTheme && prefersDarkScheme.matches)) {
            document.body.classList.add('admin-dark-mode');
            icon.classList.replace('fa-moon', 'fa-sun');
        }

        themeToggle.addEventListener('click', function () {
            document.body.classList.toggle('admin-dark-mode');

            if (document.body.classList.contains('admin-dark-mode')) {
                localStorage.setItem('admin-theme', 'dark');
                icon.classList.replace('fa-moon', 'fa-sun');
            } else {
                localStorage.setItem('admin-theme', 'light');
                icon.classList.replace('fa-sun', 'fa-moon');
            }
        });

        // User details modal functions
        function showUserDetails(userId) {
            // In a real application, you would fetch this data from your backend via AJAX
            // For this example, we'll use the data from the table row

            // Find the user in the table
            const rows = document.querySelectorAll('.admin_table tbody tr');
            let userData = null;

            rows.forEach(row => {
                const idCell = row.cells[0];
                if (idCell.textContent === `#${userId}`) {
                    userData = {
                        id: userId,
                        username: row.cells[2].textContent,
                        email: row.cells[3].textContent,
                        joinedDate: row.cells[4].textContent,
                        image: row.cells[1].querySelector('img').src
                    };

                    // Check if we have additional info in the row's data attributes
                    if (row.dataset.phone) {
                        userData.phone = row.dataset.phone;
                    }
                    if (row.dataset.address) {
                        userData.address = row.dataset.address;
                    }
                }
            });

            if (userData) {
                document.getElementById('modalUserId').textContent = '#' + userData.id;
                document.getElementById('modalUsername').textContent = userData.username;
                document.getElementById('modalEmail').textContent = userData.email;
                document.getElementById('modalJoinedDate').textContent = userData.joinedDate;
                document.getElementById('modalUserImage').src = userData.image;

                // Set optional fields
                document.getElementById('modalPhone').textContent = userData.phone || 'N/A';
                document.getElementById('modalAddress').textContent = userData.address || 'N/A';

                document.getElementById('userDetailsModal').style.display = 'flex';
            }
        }

        // Delete confirmation function
        function confirmDelete(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('confirmationMessage').textContent =
                `Are you sure you want to delete user ${username} (ID: ${userId})? This action cannot be undone.`;

            document.getElementById('deleteConfirmationModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('userDetailsModal').style.display = 'none';
            document.getElementById('deleteConfirmationModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modals = document.querySelectorAll('.admin_modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>