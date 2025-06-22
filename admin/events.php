<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Initialize variables
$events = [];
$upcoming_events = [];
$finished_events = [];
$canceled_events = [];
$error_message = '';
$success_message = '';

// Handle event deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $event_id = $_POST['event_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $_SESSION['success_message'] = "Event deleted successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting event: " . $e->getMessage();
    }
    header("Location: events.php");
    exit();
}

// Fetch event statistics
try {
    // Total events count
    $total_events = (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();

    // Upcoming events count (events with date in future)
    $upcoming_count = (int) $pdo->query(
        "SELECT COUNT(*) FROM events 
         WHERE event_date > NOW() 
         AND status != 'cancelled'"
    )->fetchColumn();

    // Finished events count (events with date in past)
    $finished_count = (int) $pdo->query(
        "SELECT COUNT(*) FROM events 
         WHERE event_date <= NOW() 
         AND status != 'cancelled'"
    )->fetchColumn();

    // Canceled events count
    $canceled_count = (int) $pdo->query(
        "SELECT COUNT(*) FROM events 
         WHERE status = 'cancelled'"
    )->fetchColumn();

    // Fetch all events with categorization
    $stmt = $pdo->query(
        "SELECT * FROM events 
         ORDER BY 
           CASE 
             WHEN status = 'cancelled' THEN 3
             WHEN event_date <= NOW() THEN 2
             ELSE 1
           END,
           event_date DESC"
    );
    $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Categorize events
    foreach ($all_events as $event) {
        if ($event['status'] == 'cancelled') {
            $canceled_events[] = $event;
        } elseif ($event['event_date'] <= date('Y-m-d H:i:s')) {
            $finished_events[] = $event;
        } else {
            $upcoming_events[] = $event;
        }
    }

} catch (PDOException $e) {
    $error_message = "Error fetching events: " . $e->getMessage();
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
    <title>Admin Dashboard - Events</title>
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
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

        /* Events Sections */
        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        .admin-dark-mode .section-title {
            color: #f0f0f0;
            border-color: #2980b9;
        }

        .add-event-btn {
            padding: 0.75rem 1.5rem;
            background: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 1rem;
            transition: background 0.3s;
        }

        .add-event-btn:hover {
            background: #27ae60;
        }

        .event-section {
            margin-bottom: 2.5rem;
        }

        .event-section-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
        }

        .admin-dark-mode .event-section-title {
            color: #f0f0f0;
        }

        .event-section-title i {
            margin-right: 0.5rem;
        }

        /* Events Table Styles */
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

        .event-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
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
            margin-right: .5rem;
            text-decoration: none;
            display: inline-block;
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

        .status-upcoming {
            color: #3498db;
            font-weight: bold;
        }

        .status-completed {
            color: #2ecc71;
            font-weight: bold;
        }

        .status-cancelled {
            color: #e74c3c;
            font-weight: bold;
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

            .events-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
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
            <div class="events-header">
                <h2 class="section-title">Events Management</h2>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <!-- Event Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Events</h3>
                    <div class="stat-value"><?= $total_events ?></div>
                </div>
                <div class="stat-card">
                    <h3>Upcoming</h3>
                    <div class="stat-value"><?= $upcoming_count ?></div>
                </div>
                <div class="stat-card">
                    <h3>Completed</h3>
                    <div class="stat-value"><?= $finished_count ?></div>
                </div>
                <div class="stat-card">
                    <h3>Canceled</h3>
                    <div class="stat-value"><?= $canceled_count ?></div>
                </div>
            </div>

            <!-- Upcoming Events Section -->
            <div class="event-section">
                <h3 class="event-section-title">
                    <i class="fas fa-calendar-check"></i> Upcoming Events
                </h3>
                <?php if (!empty($upcoming_events)): ?>
                    <table class="admin_table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_events as $event): ?>
                                <tr>
                                    <td>
                                        <img src="/BookHeaven2.0/<?= htmlspecialchars($event['banner_url'] ?? 'images/default-event.jpg') ?>"
                                            alt="<?= htmlspecialchars($event['name']) ?>" class="event-image">
                                    </td>
                                    <td><?= htmlspecialchars($event['name']) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($event['event_date'])) ?></td>
                                    <td><?= htmlspecialchars($event['venue']) ?></td>
                                    <td>
                                        <span class="status-upcoming">Upcoming</span>
                                    </td>
                                    <td>
                                        <a href="edit_event.php?id=<?= $event['event_id'] ?>" class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Are you sure you want to delete this event?');">
                                            <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                            <button type="submit" name="delete_event" class="action-btn delete-btn">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No upcoming events found.</p>
                <?php endif; ?>
            </div>

            <!-- Completed Events Section -->
            <div class="event-section">
                <h3 class="event-section-title">
                    <i class="fas fa-check-circle"></i> Completed Events
                </h3>
                <?php if (!empty($finished_events)): ?>
                    <table class="admin_table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($finished_events as $event): ?>
                                <tr>
                                    <td>
                                        <img src="/BookHeaven2.0/<?= htmlspecialchars($event['banner_url'] ?? 'images/default-event.jpg') ?>"
                                            alt="<?= htmlspecialchars($event['name']) ?>" class="event-image">
                                    </td>
                                    <td><?= htmlspecialchars($event['name']) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($event['event_date'])) ?></td>
                                    <td><?= htmlspecialchars($event['venue']) ?></td>
                                    <td>
                                        <span class="status-completed">Completed</span>
                                    </td>
                                    <td>
                                        <a href="edit_event.php?id=<?= $event['event_id'] ?>" class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Are you sure you want to delete this event?');">
                                            <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                            <button type="submit" name="delete_event" class="action-btn delete-btn">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No completed events found.</p>
                <?php endif; ?>
            </div>

            <!-- Canceled Events Section -->
            <div class="event-section">
                <h3 class="event-section-title">
                    <i class="fas fa-times-circle"></i> Canceled Events
                </h3>
                <?php if (!empty($canceled_events)): ?>
                    <table class="admin_table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($canceled_events as $event): ?>
                                <tr>
                                    <td>
                                        <img src="/BookHeaven2.0/<?= htmlspecialchars($event['banner_url'] ?? 'images/default-event.jpg') ?>"
                                            alt="<?= htmlspecialchars($event['name']) ?>" class="event-image">
                                    </td>
                                    <td><?= htmlspecialchars($event['name']) ?></td>
                                    <td><?= date('M j, Y H:i', strtotime($event['event_date'])) ?></td>
                                    <td><?= htmlspecialchars($event['venue']) ?></td>
                                    <td>
                                        <span class="status-cancelled">Canceled</span>
                                    </td>
                                    <td>
                                        <a href="edit_event.php?id=<?= $event['event_id'] ?>" class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Are you sure you want to delete this event?');">
                                            <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                            <button type="submit" name="delete_event" class="action-btn delete-btn">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No canceled events found.</p>
                <?php endif; ?>
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