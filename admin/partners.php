<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Handle partner approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_partner'])) {
    $partner_id = $_POST['partner_id'];
    try {
        $stmt = $pdo->prepare("UPDATE partners SET status = 'approved' WHERE partner_id = ?");
        $stmt->execute([$partner_id]);
        $_SESSION['success_message'] = "Partner #{$partner_id} approved!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error approving partner: " . $e->getMessage();
    }
    header("Location: partners.php");
    exit();
}

// Handle partner deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_partner'])) {
    $partner_id = $_POST['partner_id'];
    try {
        // Remove related books
        $delBooks = $pdo->prepare("DELETE FROM partner_books WHERE partner_id = ?");
        $delBooks->execute([$partner_id]);
        // Delete partner record
        $delPartner = $pdo->prepare("DELETE FROM partners WHERE partner_id = ?");
        $delPartner->execute([$partner_id]);
        $_SESSION['success_message'] = "Partner #{$partner_id} deleted.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting partner: " . $e->getMessage();
    }
    header("Location: partners.php");
    exit();
}

// Initialize stats
$stats = [
    'total_partners' => 0,
    'new_this_month' => 0,
    'pending_partners' => 0,
    'approved_partners' => 0
];

try {
    // Stats queries
    $stats['total_partners'] = (int) $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn();
    $stats['new_this_month'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM partners
         WHERE joined_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
           AND joined_at <  DATE_FORMAT(CURRENT_DATE() + INTERVAL 1 MONTH, '%Y-%m-01')"
    )->fetchColumn();
    $stats['pending_partners'] = (int) $pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'pending'")->fetchColumn();
    $stats['approved_partners'] = (int) $pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'approved'")->fetchColumn();

    // Fetch pending partners with details
    $stmt = $pdo->query(
        "SELECT p.partner_id, p.joined_at,
                u.username, u.email, ui.userimageurl,
                ui.phone, ui.address,
                (SELECT COUNT(*) FROM partner_books pb WHERE pb.partner_id = p.partner_id) AS book_count
         FROM partners p
         JOIN users u ON p.user_id = u.user_id
         LEFT JOIN user_info ui ON p.user_id = ui.user_id
         WHERE p.status = 'pending'
         ORDER BY p.joined_at DESC"
    );
    $pendingPartners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch approved partners with details
    $stmt = $pdo->query(
        "SELECT p.partner_id, p.joined_at,
                u.username, u.email, ui.userimageurl,
                ui.phone, ui.address,
                (SELECT COUNT(*) FROM partner_books pb WHERE pb.partner_id = p.partner_id) AS book_count
         FROM partners p
         JOIN users u ON p.user_id = u.user_id
         LEFT JOIN user_info ui ON p.user_id = ui.user_id
         WHERE p.status = 'approved'
         ORDER BY p.joined_at DESC"
    );
    $approvedPartners = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching partners: " . $e->getMessage();
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
    <title>Partners - Admin Dashboard</title>
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

        .admin-dark-mode .admin_theme_toggle {
            color: #f0f0f0;
        }

        /* Layout */
        .admin_main {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        .admin_sidebar {
            width: 250px;
            background-color: #34495e;
            color: white;
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
            padding: .8rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: background .3s;
        }

        .admin_sidebar_nav li a:hover,
        .admin_sidebar_nav li a.active {
            background: rgba(255, 255, 255, 0.1);
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

        /* Stats Grid */
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .admin-dark-mode .stat-card {
            background: #3d3d3d;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
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

        /* Table Styles */
        .admin_table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            margin-bottom: 2rem;
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

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .action-btn {
            padding: .5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: .9rem;
            transition: all .3s;
        }

        .view-btn {
            background: #3498db;
            color: #fff;
            margin-right: .5rem;
        }

        .admin-dark-mode .view-btn {
            background: #5dade2;
        }

        .view-btn:hover {
            background: #2980b9;
        }

        .admin-dark-mode .view-btn:hover {
            background: #4aa3df;
        }

        .confirm-btn {
            background: #2ecc71;
            color: #fff;
        }

        .confirm-btn:hover {
            background: #27ae60;
        }

        .delete-btn {
            background: #e74c3c;
            color: #fff;
            margin-left: .5rem;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        /* Modal Styles */
        .admin_modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .admin_modal_content {
            background: #fff;
            padding: 2rem;
            border-radius: 5px;
            max-width: 600px;
            width: 90%;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .admin-dark-mode .admin_modal_content {
            background: #2d2d2d;
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
            padding: .8rem 0;
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
    </style>
</head>

<body>
    <header>
        <nav class="admin_header">
            <div class="logo"><img src="images/logo.png" alt="Logo"></div>
            <div class="admin_header_right">
                <h1>Admin Dashboard</h1>
                <p>Welcome, Admin</p>
                <button class="admin_theme_toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
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
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="partners.php"  class="active"><i class="fas fa-handshake"></i> Partners</a></li>
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
            <h2>Partner Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Partners</h3>
                    <div class="stat-value"><?= $stats['total_partners'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>New This Month</h3>
                    <div class="stat-value"><?= $stats['new_this_month'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending Partners</h3>
                    <div class="stat-value"><?= $stats['pending_partners'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Approved Partners</h3>
                    <div class="stat-value"><?= $stats['approved_partners'] ?></div>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>

            <h2>Pending Partners</h2>
            <table class="admin_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Books</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingPartners as $p): ?>
                        <tr data-phone="<?= htmlspecialchars($p['phone']) ?>"
                            data-address="<?= htmlspecialchars($p['address']) ?>">
                            <td>#<?= $p['partner_id'] ?></td>
                            <td><img src="<?php echo $p['userimageurl'] ? $p['userimageurl'] : 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'; ?>"
                                    alt="user profile" class="user-avatar"></td>
                            <td><?= htmlspecialchars($p['username']) ?></td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td><?= $p['book_count'] ?></td>
                            <td><?= date('Y-m-d', strtotime($p['joined_at'])) ?></td>
                            <td><button class="action-btn view-btn"
                                    onclick="showPartnerDetails(<?= $p['partner_id'] ?>,'pending')">View</button>
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Delete this partner?');"><input type="hidden"
                                        name="partner_id" value="<?= $p['partner_id'] ?>"><input type="hidden"
                                        name="delete_partner" value="1"><button type="submit"
                                        class="action-btn delete-btn">Delete</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Approved Partners</h2>
            <table class="admin_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Books</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvedPartners as $p): ?>
                        <tr data-phone="<?= htmlspecialchars($p['phone']) ?>"
                            data-address="<?= htmlspecialchars($p['address']) ?>">
                            <td>#<?= $p['partner_id'] ?></td>
                            <td><img src="<?php echo $p['userimageurl'] ? $p['userimageurl'] : 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'; ?>"
                                    alt="user profile" class="user-avatar"></td>
                            <td><?= htmlspecialchars($p['username']) ?></td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td><?= $p['book_count'] ?></td>
                            <td><?= date('Y-m-d', strtotime($p['joined_at'])) ?></td>
                            <td><button class="action-btn view-btn"
                                    onclick="showPartnerDetails(<?= $p['partner_id'] ?>,'approved')">View</button>
                                <form method="POST" style="display:inline;"
                                    onsubmit="return confirm('Delete this partner?');"><input type="hidden"
                                        name="partner_id" value="<?= $p['partner_id'] ?>"><input type="hidden"
                                        name="delete_partner" value="1"><button type="submit"
                                        class="action-btn delete-btn">Delete</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Partner Details Modal -->
            <div id="partnerDetailsModal" class="admin_modal">
                <div class="admin_modal_content">
                    <span class="admin_modal_close" onclick="closeModal()">&times;</span>
                    <h2>Partner Details</h2>
                    <div class="user-details">
                        <img id="modalUserImage" class="user-details-avatar" src="">
                        <div class="user-details-info">
                            <div class="user-details-row"><span class="user-details-label">ID:</span><span
                                    id="modalPartnerId"></span></div>
                            <div class="user-details-row"><span class="user-details-label">Username:</span><span
                                    id="modalUsername"></span></div>
                            <div class="user-details-row"><span class="user-details-label">Email:</span><span
                                    id="modalEmail"></span></div>
                            <div class="user-details-row"><span class="user-details-label">Phone:</span><span
                                    id="modalPhone"></span></div>
                            <div class="user-details-row"><span class="user-details-label">Address:</span><span
                                    id="modalAddress"></span></div>
                            <div class="user-details-row"><span class="user-details-label">Books:</span><span
                                    id="modalBookCount"></span></div>
                            <div class="user-details-row"><span class="user-details-label">Status:</span><span
                                    id="modalStatus"></span></div>
                            <div class="user-details-row"><span class="user-details-label">Joined At:</span><span
                                    id="modalJoinedDate"></span></div>
                        </div>
                        <form id="approveForm" method="POST" style="margin-top:1rem; display:none;">
                            <input type="hidden" name="partner_id" id="approvePartnerId">
                            <input type="hidden" name="approve_partner" value="1">
                            <button type="submit" class="action-btn confirm-btn">Approve Partner</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle.querySelector('i');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (localStorage.getItem('admin-theme') === 'dark' || (!localStorage.getItem('admin-theme') && prefersDark)) {
            document.body.classList.add('admin-dark-mode'); icon.classList.replace('fa-moon', 'fa-sun');
        }
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('admin-dark-mode');
            if (document.body.classList.contains('admin-dark-mode')) {
                localStorage.setItem('admin-theme', 'dark'); icon.classList.replace('fa-moon', 'fa-sun');
            } else {
                localStorage.setItem('admin-theme', 'light'); icon.classList.replace('fa-sun', 'fa-moon');
            }
        });
        // Modal
        function showPartnerDetails(id, status) {
            const row = Array.from(document.querySelectorAll('.admin_table tbody tr'))
                .find(r => r.cells[0].textContent === `#${id}`);
            if (!row) return; const cells = row.cells;
            document.getElementById('modalPartnerId').textContent = cells[0].textContent;
            document.getElementById('modalUsername').textContent = cells[2].textContent;
            document.getElementById('modalEmail').textContent = cells[3].textContent;
            document.getElementById('modalBookCount').textContent = row.cells[4].textContent;
            document.getElementById('modalJoinedDate').textContent = cells[cells.length - 2].textContent;
            document.getElementById('modalUserImage').src = cells[1].querySelector('img').src;
            document.getElementById('modalPhone').textContent = row.dataset.phone || 'N/A';
            document.getElementById('modalAddress').textContent = row.dataset.address || 'N/A';
            document.getElementById('modalStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
            const form = document.getElementById('approveForm');
            if (status === 'pending') { document.getElementById('approvePartnerId').value = id; form.style.display = 'block'; } else form.style.display = 'none';
            document.getElementById('partnerDetailsModal').style.display = 'flex';
        }
        function closeModal() { document.getElementById('partnerDetailsModal').style.display = 'none'; }
        window.onclick = e => { if (e.target.classList.contains('admin_modal')) closeModal(); };
    </script>
</body>

</html>