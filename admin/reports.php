<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Fetch all report data
try {
    // General Statistics
    $stats = [
        'total_users' => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_books' => (int) $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
        'total_audiobooks' => (int) $pdo->query("SELECT COUNT(*) FROM audiobooks")->fetchColumn(),
        'total_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'total_revenue' => (float) $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'delivered'")->fetchColumn(),
        'active_subscriptions' => (int) $pdo->query("SELECT COUNT(*) FROM user_subscriptions WHERE status = 'active'")->fetchColumn(),
        'total_writers' => (int) $pdo->query("SELECT COUNT(*) FROM writers")->fetchColumn(),
        'total_events' => (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn()
    ];

    // Sales Data (last 6 months)
    $sales_data = $pdo->query(
        "SELECT 
            DATE_FORMAT(order_date, '%Y-%m') AS month,
            COUNT(*) AS order_count,
            SUM(total_amount) AS revenue
         FROM orders
         WHERE order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
         GROUP BY DATE_FORMAT(order_date, '%Y-%m')
         ORDER BY month"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Format sales data for chart
    $sales_chart = ['labels' => [], 'orders' => [], 'revenue' => []];
    foreach ($sales_data as $sale) {
        $sales_chart['labels'][] = date("M Y", strtotime($sale['month'] . '-01'));
        $sales_chart['orders'][] = $sale['order_count'];
        $sales_chart['revenue'][] = $sale['revenue'];
    }
    //     // Subscription Plan Sales
// $subscription_data = $pdo->query(
//   "SELECT 
//       sp.plan_name,
//       sp.price,
//       sp.validity_days,
//       sp.book_quantity,
//       sp.audiobook_quantity,
//       COUNT(so.id) AS sales_count
//    FROM subscription_plans sp
//    LEFT JOIN subscription_orders so ON sp.plan_id = so.plan_id
//    GROUP BY sp.plan_id
//    ORDER BY sales_count DESC"
// )->fetchAll(PDO::FETCH_ASSOC);

// Monthly Subscription Data
$subscription_monthly_data = $pdo->query(
  "SELECT 
      DATE_FORMAT(so.issue_date, '%Y-%m') AS month,
      COUNT(CASE WHEN so.status = 'active' THEN 1 END) AS new_subs,
      COUNT(CASE WHEN so.status = 'renewed' THEN 1 END) AS renewals,
      SUM(so.amount) AS revenue
   FROM subscription_orders so
   WHERE so.issue_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
   GROUP BY DATE_FORMAT(so.issue_date, '%Y-%m')
   ORDER BY month"
)->fetchAll(PDO::FETCH_ASSOC);
    // Book Categories Distribution
    $categories_data = $pdo->query(
        "SELECT 
            c.name AS category,
            COUNT(bc.book_id) AS book_count
         FROM book_categories bc
         JOIN categories c ON bc.category_id = c.id
         GROUP BY bc.category_id
         ORDER BY book_count DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Book Genres Distribution
    $genres_data = $pdo->query(
        "SELECT 
            g.name AS genre,
            COUNT(bg.book_id) AS book_count
         FROM book_genres bg
         JOIN genres g ON bg.genre_id = g.genre_id
         GROUP BY bg.genre_id
         ORDER BY book_count DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Top Selling Books
    $top_books = $pdo->query(
        "SELECT 
            b.title,
            b.price,
            COUNT(oi.id) AS sales_count,
            SUM(oi.quantity) AS total_quantity,
            AVG(r.rating) AS avg_rating
         FROM books b
         LEFT JOIN order_items oi ON b.book_id = oi.book_id
         LEFT JOIN reviews r ON b.book_id = r.book_id
         GROUP BY b.book_id
         ORDER BY sales_count DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    // User Activity
    $user_activity = $pdo->query(
        "SELECT 
            u.username,
            COUNT(DISTINCT o.order_id) AS order_count,
            COUNT(DISTINCT r.review_id) AS review_count,
            COUNT(DISTINCT w.id) AS wishlist_count,
            MAX(ua.login_timestamp) AS last_login
         FROM users u
         LEFT JOIN orders o ON u.user_id = o.user_id
         LEFT JOIN reviews r ON u.user_id = r.user_id
         LEFT JOIN wishlist w ON u.user_id = w.user_id
         LEFT JOIN user_activities ua ON u.user_id = ua.user_id
         GROUP BY u.user_id
         ORDER BY order_count DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Event Participation
    $event_participation = $pdo->query(
        "SELECT 
            e.name AS event_name,
            e.event_date,
            COUNT(ep.id) AS participant_count
         FROM events e
         LEFT JOIN event_participants ep ON e.event_id = ep.event_id
         GROUP BY e.event_id
         ORDER BY e.event_date DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error generating reports: " . $e->getMessage();
}

$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports | BKH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/BookHeaven2.0/css/admin_dashboard.css">
    <style>
        .report-section {
            margin-bottom: 2rem;
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .report-title {
            font-size: 1.5rem;
            color: var(--text-color);
            margin: 0;
        }

        .chart-container {
            height: 400px;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            margin-top: 0;
            color: var(--text-light);
            font-size: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background-color: var(--table-header-bg);
            color: var(--text-color);
        }

        .data-table tr:hover {
            background-color: var(--table-hover-bg);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="admin_header">
        <div class="logo"><img src="images/logo.png" alt="Logo"></div>
        <div class="admin_header_right">
            <h1>Admin Reports</h1>
            <p>Comprehensive website analytics</p>
            <button class="admin_theme_toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
        </div>
    </header>

    <main class="admin_main">
        <aside class="admin_sidebar">
            <nav class="admin_sidebar_nav">
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
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
                    <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="admin_main_content">
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <!-- Key Statistics -->
            <div class="report-section">
                <div class="report-header">
                    <h2 class="report-title">Key Statistics</h2>
                    <span class="text-muted">Last updated: <?= date('Y-m-d H:i:s') ?></span>
                </div>

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
                        <h3>Audio Books</h3>
                        <div class="stat-value"><?= number_format($stats['total_audiobooks']) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Revenue</h3>
                        <div class="stat-value">$<?= number_format($stats['total_revenue'], 2) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Active Subscriptions</h3>
                        <div class="stat-value"><?= number_format($stats['active_subscriptions']) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Writers</h3>
                        <div class="stat-value"><?= number_format($stats['total_writers']) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Upcoming Events</h3>
                        <div class="stat-value"><?= number_format($stats['total_events']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Sales Performance -->
            <div class="report-section">
                <div class="report-header">
                    <h2 class="report-title">Sales Performance</h2>
                    <span class="text-muted">Last 6 months</span>
                </div>

                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Add this section after the Sales Performance section -->
            <!-- Subscription Plan Performance -->
            <div class="report-section">
                <div class="report-header">
                    <h2 class="report-title">Subscription Plan Performance</h2>
                    <span class="text-muted">Last 6 months</span>
                </div>

                <div class="chart-container">
                    <canvas id="subscriptionChart"></canvas>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>New Subscriptions</th>
                            <th>Renewals</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch monthly subscription data
                        $subscription_monthly_data = $pdo->query(
                            "SELECT 
            DATE_FORMAT(so.issue_date, '%Y-%m') AS month,
            COUNT(CASE WHEN so.status = 'active' THEN 1 END) AS new_subs,
            COUNT(CASE WHEN so.status = 'renewed' THEN 1 END) AS renewals,
            SUM(so.amount) AS revenue
         FROM subscription_orders so
         WHERE so.issue_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
         GROUP BY DATE_FORMAT(so.issue_date, '%Y-%m')
         ORDER BY month"
                        )->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($subscription_monthly_data as $month): ?>
                            <tr>
                                <td><?= date("M Y", strtotime($month['month'] . '-01')) ?></td>
                                <td><?= $month['new_subs'] ?></td>
                                <td><?= $month['renewals'] ?></td>
                                <td>$<?= number_format($month['revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="two-columns">
                <!-- Book Categories -->
                <div class="report-section">
                    <div class="report-header">
                        <h2 class="report-title">Book Categories</h2>
                        <span class="text-muted">Top 10 categories</span>
                    </div>

                    <div class="chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Book Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories_data as $category): ?>
                                <tr>
                                    <td><?= htmlspecialchars($category['category']) ?></td>
                                    <td><?= $category['book_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Book Genres -->
                <div class="report-section">
                    <div class="report-header">
                        <h2 class="report-title">Book Genres</h2>
                        <span class="text-muted">Top 10 genres</span>
                    </div>

                    <div class="chart-container">
                        <canvas id="genresChart"></canvas>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Genre</th>
                                <th>Book Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($genres_data as $genre): ?>
                                <tr>
                                    <td><?= htmlspecialchars($genre['genre']) ?></td>
                                    <td><?= $genre['book_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Selling Books -->
            <div class="report-section">
                <div class="report-header">
                    <h2 class="report-title">Top Selling Books</h2>
                    <span class="text-muted">By number of sales</span>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Sales Count</th>
                            <th>Total Quantity</th>
                            <th>Avg Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_books as $book): ?>
                            <tr>
                                <td><?= htmlspecialchars($book['title']) ?></td>
                                <td>$<?= number_format($book['price'], 2) ?></td>
                                <td><?= $book['sales_count'] ?></td>
                                <td><?= $book['total_quantity'] ?></td>
                                <td>
                                    <?php if ($book['avg_rating']): ?>
                                        <?= number_format($book['avg_rating'], 1) ?> <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- User Activity -->
            <div class="report-section">
                <div class="report-header">
                    <h2 class="report-title">Top Active Users</h2>
                    <span class="text-muted">By order count</span>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Orders</th>
                            <th>Reviews</th>
                            <th>Wishlist</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_activity as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= $user['order_count'] ?></td>
                                <td><?= $user['review_count'] ?></td>
                                <td><?= $user['wishlist_count'] ?></td>
                                <td><?= $user['last_login'] ? date('Y-m-d', strtotime($user['last_login'])) : 'Never' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Event Participation -->
            <div class="report-section">
                <div class="report-header">
                    <h2 class="report-title">Event Participation</h2>
                    <span class="text-muted">Recent events</span>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Participants</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($event_participation as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['event_name']) ?></td>
                                <td><?= date('Y-m-d', strtotime($event['event_date'])) ?></td>
                                <td><?= $event['participant_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                updateChartsForDarkMode();
            } else {
                localStorage.setItem('admin-theme', 'light');
                icon.classList.replace('fa-sun', 'fa-moon');
                updateChartsForLightMode();
            }
        });

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function () {
            const isDarkMode = document.body.classList.contains('admin-dark-mode');
            const textColor = isDarkMode ? '#f0f0f0' : '#666';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($sales_chart['labels']) ?>,
                    datasets: [
                        {
                            label: 'Number of Orders',
                            data: <?= json_encode($sales_chart['orders']) ?>,
                            backgroundColor: 'rgba(52, 152, 219, 0.7)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Revenue ($)',
                            data: <?= json_encode($sales_chart['revenue']) ?>,
                            backgroundColor: 'rgba(46, 204, 113, 0.7)',
                            borderColor: 'rgba(46, 204, 113, 1)',
                            borderWidth: 1,
                            type: 'line',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: textColor
                            }
                        },
                        title: {
                            display: true,
                            text: 'Monthly Sales Performance',
                            color: textColor
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Number of Orders',
                                color: textColor
                            },
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Revenue ($)',
                                color: textColor
                            },
                            grid: {
                                drawOnChartArea: false,
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
                }
            });

            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            const categoriesChart = new Chart(categoriesCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_column($categories_data, 'category')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($categories_data, 'book_count')) ?>,
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(155, 89, 182, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(241, 196, 15, 0.7)',
                            'rgba(231, 76, 60, 0.7)',
                            'rgba(26, 188, 156, 0.7)',
                            'rgba(230, 126, 34, 0.7)',
                            'rgba(142, 68, 173, 0.7)',
                            'rgba(41, 128, 185, 0.7)',
                            'rgba(39, 174, 96, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor
                            }
                        },
                        title: {
                            display: true,
                            text: 'Book Categories Distribution',
                            color: textColor
                        }
                    }
                }
            });

            // Genres Chart
            const genresCtx = document.getElementById('genresChart').getContext('2d');
            const genresChart = new Chart(genresCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($genres_data, 'genre')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($genres_data, 'book_count')) ?>,
                        backgroundColor: [
                            'rgba(231, 76, 60, 0.7)',
                            'rgba(26, 188, 156, 0.7)',
                            'rgba(230, 126, 34, 0.7)',
                            'rgba(142, 68, 173, 0.7)',
                            'rgba(41, 128, 185, 0.7)',
                            'rgba(39, 174, 96, 0.7)',
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(155, 89, 182, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(241, 196, 15, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor
                            }
                        },
                        title: {
                            display: true,
                            text: 'Book Genres Distribution',
                            color: textColor
                        }
                    }
                }
            });

            // Subscription Plan Sales Chart
            // Subscription Performance Chart
            const subscriptionCtx = document.getElementById('subscriptionChart').getContext('2d');
            const subscriptionChart = new Chart(subscriptionCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_map(function ($m) {
                        return date("M Y", strtotime($m['month'] . '-01')); }, $subscription_monthly_data)) ?>,
                    datasets: [
                        {
                            label: 'New Subscriptions',
                            data: <?= json_encode(array_column($subscription_monthly_data, 'new_subs')) ?>,
                            backgroundColor: 'rgba(52, 152, 219, 0.7)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Renewals',
                            data: <?= json_encode(array_column($subscription_monthly_data, 'renewals')) ?>,
                            backgroundColor: 'rgba(155, 89, 182, 0.7)',
                            borderColor: 'rgba(155, 89, 182, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Revenue ($)',
                            data: <?= json_encode(array_column($subscription_monthly_data, 'revenue')) ?>,
                            backgroundColor: 'rgba(46, 204, 113, 0.2)',
                            borderColor: 'rgba(46, 204, 113, 1)',
                            borderWidth: 2,
                            type: 'line',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            labels: {
                                color: textColor
                            }
                        },
                        title: {
                            display: true,
                            text: 'Monthly Subscription Performance',
                            color: textColor
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Number of Subscriptions',
                                color: textColor
                            },
                            grid: {
                                color: gridColor
                            },
                            ticks: {
                                color: textColor
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Revenue ($)',
                                color: textColor
                            },
                            grid: {
                                drawOnChartArea: false,
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
                }
            });

            // Update charts if in dark mode initially
            if (isDarkMode) {
                updateChartsForDarkMode();
            }
        });
    </script>
</body>

</html>