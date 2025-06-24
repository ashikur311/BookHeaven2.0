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
           u.user_profile, ui.phone, ui.address
    FROM users u
    LEFT JOIN user_info ui ON u.user_id = ui.user_id
    ORDER BY u.user_id ASC
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
    <link rel="stylesheet" href="/BookHeaven2.0/css/admin_css/users_data.css">
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
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li><a href="add.php"><i class="fas fa-plus-circle"></i> Add</a></li>
                    <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
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
                                <img src="/BookHeaven2.0/<?php echo htmlspecialchars($user['user_profile'] ?? 'default-avatar.jpg'); ?>"
                                    alt="<?php echo htmlspecialchars($user['username']); ?>" class="user-avatar">
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
                image: row.cells[1].querySelector('img').src,
                phone: row.dataset.phone || 'N/A',  // Get phone from dataset or default to 'N/A'
                address: row.dataset.address || 'N/A'  // Get address from dataset or default to 'N/A'
            };
        }
    });

    if (userData) {
        document.getElementById('modalUserId').textContent = '#' + userData.id;
        document.getElementById('modalUsername').textContent = userData.username;
        document.getElementById('modalEmail').textContent = userData.email;
        document.getElementById('modalJoinedDate').textContent = userData.joinedDate;
        document.getElementById('modalUserImage').src = userData.image;

        // Set phone and address in the modal
        document.getElementById('modalPhone').textContent = userData.phone;
        document.getElementById('modalAddress').textContent = userData.address;

        // Show the modal
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