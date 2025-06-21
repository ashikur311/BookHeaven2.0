<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-primary-color: #4e73df;
            --admin-secondary-color: #f8f9fc;
            --admin-text-color: #5a5c69;
            --admin-bg-color: #fff;
            --admin-card-bg: #fff;
            --admin-sidebar-bg: #4e73df;
            --admin-sidebar-text: rgba(255, 255, 255, 0.8);
            --admin-sidebar-hover: rgba(255, 255, 255, 0.1);
            --admin-header-bg: #fff;
            --admin-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --admin-border-color: #e3e6f0;
        }

        .admin-dark-mode {
            --admin-primary-color: #4e73df;
            --admin-secondary-color: #2a2f45;
            --admin-text-color: #d1d5db;
            --admin-bg-color: #1a1f36;
            --admin-card-bg: #2a2f45;
            --admin-sidebar-bg: #1a1f36;
            --admin-sidebar-text: rgba(255, 255, 255, 0.8);
            --admin-sidebar-hover: rgba(255, 255, 255, 0.1);
            --admin-header-bg: #2a2f45;
            --admin-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.3);
            --admin-border-color: #3a3f58;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--admin-bg-color);
            color: var(--admin-text-color);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1.2rem;
            left: 1.2rem;
            z-index: 1000;
            background-color: var(--admin-primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border: none;
        }

        .admin_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1rem 1rem 4.5rem;
            background-color: var(--admin-header-bg);
            box-shadow: var(--admin-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
        }

        .admin_header .logo img {
            height: 40px;
            max-width: 100%;
        }

        .admin_header_right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .admin_header_right h1 {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            color: var(--admin-text-color);
            margin: 0.5rem 0;
        }

        .admin_header_right p {
            color: var(--admin-text-color);
            opacity: 0.8;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .admin_theme_toggle {
            background: none;
            border: none;
            color: var(--admin-text-color);
            font-size: 1.2rem;
            cursor: pointer;
            margin-left: 0.5rem;
        }

        .admin_main {
            display: flex;
            min-height: calc(100vh - 72px);
            flex-direction: column;
        }

        .admin_sidebar {
            width: 250px;
            background-color: var(--admin-sidebar-bg);
            color: var(--admin-sidebar-text);
            padding: 1rem 0;
            transition: transform 0.3s ease;
            z-index: 90;
            position: fixed;
            top: 72px;
            left: 0;
            bottom: 0;
            transform: translateX(-100%);
            overflow-y: auto;
        }

        .admin_sidebar_nav ul {
            list-style: none;
        }

        .admin_sidebar_nav li {
            margin-bottom: 0.5rem;
        }

        .admin_sidebar_nav a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--admin-sidebar-text);
            text-decoration: none;
            transition: all 0.3s;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }

        .admin_sidebar_nav a:hover {
            background-color: var(--admin-sidebar-hover);
            color: white;
        }

        .admin_sidebar_nav a.active {
            background-color: var(--admin-sidebar-hover);
            font-weight: bold;
        }

        .admin_sidebar_nav i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .admin_main_content {
            flex: 1;
            padding: 1.5rem;
            background-color: var(--admin-secondary-color);
            transition: margin-left 0.3s ease;
            margin-left: 0;
            width: 100%;
        }

        .admin_stats_grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .admin_stat_card {
            background-color: var(--admin-card-bg);
            border-radius: 0.35rem;
            padding: 1rem;
            box-shadow: var(--admin-shadow);
            border-left: 0.25rem solid var(--admin-primary-color);
        }

        .admin_stat_card h3 {
            font-size: clamp(0.8rem, 2vw, 1rem);
            color: var(--admin-text-color);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            opacity: 0.8;
        }

        .admin_stat_card p {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            font-weight: bold;
            color: var(--admin-text-color);
        }

        .admin_graphs_section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .admin_graph_card {
            background-color: var(--admin-card-bg);
            border-radius: 0.35rem;
            padding: 1rem;
            box-shadow: var(--admin-shadow);
        }

        .admin_graph_card h3 {
            font-size: clamp(0.9rem, 2vw, 1rem);
            color: var(--admin-text-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--admin-border-color);
            padding-bottom: 0.5rem;
        }

        .admin_graph_placeholder {
            height: 200px;
            background-color: var(--admin-secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--admin-text-color);
            opacity: 0.7;
            border-radius: 0.25rem;
        }

        .admin_tables_section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .admin_table_card {
            background-color: var(--admin-card-bg);
            border-radius: 0.35rem;
            padding: 1rem;
            box-shadow: var(--admin-shadow);
            overflow-x: auto;
        }

        .admin_table_card h3 {
            font-size: clamp(0.9rem, 2vw, 1rem);
            color: var(--admin-text-color);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--admin-border-color);
            padding-bottom: 0.5rem;
            flex-wrap: wrap;
        }

        .admin_table_card .view_all {
            font-size: 0.8rem;
            color: var(--admin-primary-color);
            text-decoration: none;
        }

        .admin_table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .admin_table th {
            text-align: left;
            padding: 0.5rem;
            font-weight: bold;
            color: var(--admin-text-color);
            border-bottom: 1px solid var(--admin-border-color);
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        .admin_table td {
            padding: 0.5rem;
            border-bottom: 1px solid var(--admin-border-color);
            color: var(--admin-text-color);
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        .admin_table tr:last-child td {
            border-bottom: none;
        }

        .admin_status {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }

        .admin_status_pending {
            background-color: #f8e3a3;
            color: #8a6d3b;
        }

        .admin_status_approved {
            background-color: #c8e6c9;
            color: #388e3c;
        }

        .admin_action_link {
            color: var(--admin-primary-color);
            text-decoration: none;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        /* Mobile Sidebar State */
        .sidebar-expanded .admin_sidebar {
            transform: translateX(0);
        }

        .sidebar-expanded .admin_main_content {
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        /* Responsive styles */
        @media (min-width: 576px) {
            .admin_header {
                padding: 1rem 2rem 1rem 4.5rem;
                flex-wrap: nowrap;
            }
            
            .admin_header_right {
                justify-content: flex-end;
                flex-wrap: nowrap;
            }
            
            .admin_stats_grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 1.5rem;
            }
            
            .admin_stat_card {
                padding: 1.5rem;
            }
        }

        @media (min-width: 768px) {
            .mobile-menu-toggle {
                display: none;
            }
            
            .admin_header {
                padding-left: 2rem;
            }
            
            .admin_sidebar {
                transform: translateX(0);
            }
            
            .admin_main_content {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
            
            .admin_graphs_section {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
            
            .admin_tables_section {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }

        @media (min-width: 992px) {
            .admin_stats_grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .admin_graph_card {
                padding: 1.5rem;
            }
            
            .admin_table_card {
                padding: 1.5rem;
            }
        }

        @media (min-width: 1200px) {
            .admin_stats_grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .admin_graph_placeholder {
                height: 250px;
            }
        }

        /* Very small devices (phones, 360px and down) */
        @media (max-width: 360px) {
            .mobile-menu-toggle {
                top: 0.8rem;
                left: 0.8rem;
                width: 35px;
                height: 35px;
            }
            
            .admin_header {
                padding-top: 3.5rem;
                padding-left: 4rem;
            }
            
            .admin_stats_grid {
                grid-template-columns: 1fr;
            }
            
            .admin_stat_card h3 {
                font-size: 0.8rem;
            }
            
            .admin_stat_card p {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
    <i class="fas fa-bars"></i>
</button>

<header>
    <nav class="admin_header">
        <div class="logo">
            <img src="images/logo.png" alt="Logo">
        </div>
        <div class="admin_header_right">
            <h1>Admin Dashboard</h1>
            <p>Welcome, Admin</p>
            <button class="admin_theme_toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>
</header>
<main class="admin_main">
    <aside class="admin_sidebar">
        <nav class="admin_sidebar_nav">
            <ul>
                <li><a href="index.php" class="active"><i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="add.php"><i class="fas fa-fw fa-plus-circle"></i> <span>Add</span></a></li>
                <li><a href="Users.php"><i class="fas fa-fw fa-users"></i> <span>Users</span></a></li>
                <li><a href="partners.php"><i class="fas fa-fw fa-handshake"></i> <span>Partners</span></a></li>
                <li><a href="books.php"><i class="fas fa-fw fa-book"></i> <span>Books</span></a></li>
                <li><a href="rentbooks.php"><i class="fas fa-fw fa-book-open"></i> <span>Rent Books</span></a></li>
                <li><a href="audiobook.php"><i class="fas fa-fw fa-headphones"></i> <span>Audio Books</span></a></li>
                <li><a href="orders.php"><i class="fas fa-fw fa-shopping-cart"></i> <span>Orders</span></a></li>
                <li><a href="subscription.php"><i class="fas fa-fw fa-star"></i> <span>Subscription</span></a></li>
                <li><a href="events.php"><i class="fas fa-fw fa-calendar-alt"></i> <span>Events</span></a></li>
                <li><a href="reports.php"><i class="fas fa-fw fa-chart-bar"></i> <span>Reports</span></a></li>
                <li><a href="logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </nav>
    </aside>
    <div class="admin_main_content">
        <div class="admin_stats_grid">
            <div class="admin_stat_card">
                <h3>Total Users</h3>
                <p>24</p>
            </div>
            <div class="admin_stat_card">
                <h3>Total Books</h3>
                <p>26</p>
            </div>
            <div class="admin_stat_card">
                <h3>Total Partners</h3>
                <p>3</p>
            </div>
            <div class="admin_stat_card">
                <h3>Rent Books</h3>
                <p>2</p>
            </div>
            <div class="admin_stat_card">
                <h3>Audio Books</h3>
                <p>2</p>
            </div>
            <div class="admin_stat_card">
                <h3>Total Orders</h3>
                <p>67</p>
            </div>
            <div class="admin_stat_card">
                <h3>Pending Orders</h3>
                <p>52</p>
            </div>
            <div class="admin_stat_card">
                <h3>Shiped Orders</h3>
                <p>15</p>
            </div>
            <div class="admin_stat_card">
                <h3>Delivered Orders</h3>
                <p>1</p>
            </div>
        </div>
        
        <section class="admin_graphs_section">
            <div class="admin_graph_card">
                <h3>Monthly Sales Graph</h3>
                <div class="admin_graph_placeholder">
                    <canvas id="salesChart" width="100%" height="250"></canvas>
                </div>
            </div>
            <div class="admin_graph_card">
                <h3>Order Graph</h3>
                <div class="admin_graph_placeholder">
                    <canvas id="ordersChart" width="100%" height="250"></canvas>
                </div>
            </div>
            <div class="admin_graph_card">
                <h3>Subscription Graph</h3>
                <div class="admin_graph_placeholder">
                    <canvas id="subscriptionChart" width="100%" height="250"></canvas>
                </div>
            </div>
            <div class="admin_graph_card">
                <h3>User Growth Chart</h3>
                <div class="admin_graph_placeholder">
                    <canvas id="usersChart" width="100%" height="250"></canvas>
                </div>
            </div>
        </section>
        
        <section class="admin_tables_section">
            <div class="admin_table_card">
                <h3>Recent Pending Orders <a href="orders.php" class="view_all">View All</a></h3>
                <table class="admin_table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#ORD-1001</td>
                            <td>John Doe</td>
                            <td>2023-05-15</td>
                            <td>$24.99</td>
                            <td><span class="admin_status admin_status_pending">Pending</span></td>
                            <td><a href="#" class="admin_action_link">View</a></td>
                        </tr>
                        <tr>
                            <td>#ORD-1002</td>
                            <td>Jane Smith</td>
                            <td>2023-05-14</td>
                            <td>$19.99</td>
                            <td><span class="admin_status admin_status_pending">Pending</span></td>
                            <td><a href="#" class="admin_action_link">View</a></td>
                        </tr>
                        <tr>
                            <td>#ORD-1003</td>
                            <td>Robert Johnson</td>
                            <td>2023-05-13</td>
                            <td>$32.50</td>
                            <td><span class="admin_status admin_status_pending">Pending</span></td>
                            <td><a href="#" class="admin_action_link">View</a></td>
                        </tr>
                        <tr>
                            <td>#ORD-1004</td>
                            <td>Emily Davis</td>
                            <td>2023-05-12</td>
                            <td>$27.99</td>
                            <td><span class="admin_status admin_status_pending">Pending</span></td>
                            <td><a href="#" class="admin_action_link">View</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section  class="admin_tables_section" >
            <div class="admin_table_card">
                <h3>Pending Partner Approvals <a href="partners.php" class="view_all">View All</a></h3>
                <table class="admin_table">
                    <thead>
                        <tr>
                            <th>Partner ID</th>
                            <th>User</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#PTR-2001</td>
                            <td>Book Haven</td>
                            <td>2023-05-10</td>
                            <td><span class="admin_status admin_status_pending">Pending</span></td>
                            <td><a href="#" class="admin_action_link">Review</a></td>
                        </tr>
                        <tr>
                            <td>#PTR-2002</td>
                            <td>Literary Corner</td>
                            <td>2023-05-09</td>
                            <td><span class="admin_status admin_status_pending">Pending</span></td>
                            <td><a href="#" class="admin_action_link">Review</a></td>
                        </tr>
                        <tr>
                            <td>#PTR-2003</td>
                            <td>Page Turner</td>
                            <td>2023-05-08</td>
                            <td><span class="admin_status admin_status_pending">Pending</span></td>
                            <td><a href="#" class="admin_action_link">Review</a></td>
                        </tr>
                        <tr>
                            <td>#PTR-2004</td>
                            <td>Novel Ideas</td>
                            <td>2023-05-07</td>
                            <td><span class="admin_status admin_status_pending">Pending</span></td>
                            <td><a href="#" class="admin_action_link">Review</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    
    themeToggle.addEventListener('click', function() {
        document.body.classList.toggle('admin-dark-mode');
        
        if (document.body.classList.contains('admin-dark-mode')) {
            localStorage.setItem('admin-theme', 'dark');
            icon.classList.replace('fa-moon', 'fa-sun');
        } else {
            localStorage.setItem('admin-theme', 'light');
            icon.classList.replace('fa-sun', 'fa-moon');
        }
        
        // Update charts when theme changes
        updateChartThemes();
    });
    
    // Mobile sidebar toggle functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.admin_sidebar');
    const body = document.body;
    
    mobileMenuToggle.addEventListener('click', function() {
        body.classList.toggle('sidebar-expanded');
        
        // Change icon based on state
        const icon = this.querySelector('i');
        if (body.classList.contains('sidebar-expanded')) {
            icon.classList.replace('fa-bars', 'fa-times');
        } else {
            icon.classList.replace('fa-times', 'fa-bars');
        }
    });
    
    // Close sidebar when clicking on a link (for mobile)
    document.querySelectorAll('.admin_sidebar_nav a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                body.classList.remove('sidebar-expanded');
                mobileMenuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
            }
        });
    });
    
    // Close sidebar when clicking outside (for mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth >= 768) return;
        
        const isClickInsideSidebar = sidebar.contains(e.target);
        const isClickOnToggle = mobileMenuToggle.contains(e.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && body.classList.contains('sidebar-expanded')) {
            body.classList.remove('sidebar-expanded');
            mobileMenuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
        }
    });
    
    // Handle window resize
    function handleResize() {
        if (window.innerWidth >= 768) {
            body.classList.remove('sidebar-expanded');
            mobileMenuToggle.style.display = 'none';
            mobileMenuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
        } else {
            mobileMenuToggle.style.display = 'flex';
        }
    }
    
    window.addEventListener('resize', handleResize);
    handleResize(); // Run on initial load
    
    // Chart.js implementation
    function getChartColors() {
        return document.body.classList.contains('admin-dark-mode') ? {
            bgColor: 'rgba(78, 115, 223, 0.2)',
            borderColor: 'rgba(78, 115, 223, 1)',
            gridColor: 'rgba(255, 255, 255, 0.1)',
            textColor: 'rgba(255, 255, 255, 0.7)'
        } : {
            bgColor: 'rgba(78, 115, 223, 0.2)',
            borderColor: 'rgba(78, 115, 223, 1)',
            gridColor: 'rgba(0, 0, 0, 0.1)',
            textColor: 'rgba(0, 0, 0, 0.7)'
        };
    }
    
    function updateChartThemes() {
        const colors = getChartColors();
        
        // Update all charts with new colors
        charts.forEach(chart => {
            chart.options.scales.x.grid.color = colors.gridColor;
            chart.options.scales.y.grid.color = colors.gridColor;
            chart.options.scales.x.ticks.color = colors.textColor;
            chart.options.scales.y.ticks.color = colors.textColor;
            chart.update();
        });
    }
    
    const colors = getChartColors();
    const charts = [];
    
    // Initialize charts only when they are in view
    function initCharts() {
        if (charts.length === 0) {
            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sales',
                        data: [1200, 1900, 1500, 2200, 1800, 2500],
                        backgroundColor: colors.bgColor,
                        borderColor: colors.borderColor,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
                            }
                        },
                        y: {
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
                            }
                        }
                    }
                }
            });
            charts.push(salesChart);
            
            // Orders Chart
            const ordersCtx = document.getElementById('ordersChart').getContext('2d');
            const ordersChart = new Chart(ordersCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Orders',
                        data: [45, 70, 60, 85, 72, 95],
                        backgroundColor: colors.bgColor,
                        borderColor: colors.borderColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
                            }
                        },
                        y: {
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
                            }
                        }
                    }
                }
            });
            charts.push(ordersChart);
            
            // Subscription Chart
            const subscriptionCtx = document.getElementById('subscriptionChart').getContext('2d');
            const subscriptionChart = new Chart(subscriptionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Basic', 'Standard', 'Premium'],
                    datasets: [{
                        data: [300, 450, 200],
                        backgroundColor: [
                            'rgba(78, 115, 223, 0.7)',
                            'rgba(54, 185, 204, 0.7)',
                            'rgba(155, 89, 182, 0.7)'
                        ],
                        borderColor: [
                            'rgba(78, 115, 223, 1)',
                            'rgba(54, 185, 204, 1)',
                            'rgba(155, 89, 182, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: colors.textColor
                            }
                        }
                    }
                }
            });
            charts.push(subscriptionChart);
            
            // Users Chart
            const usersCtx = document.getElementById('usersChart').getContext('2d');
            const usersChart = new Chart(usersCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'New Users',
                        data: [120, 190, 150, 220, 180, 250],
                        backgroundColor: colors.bgColor,
                        borderColor: colors.borderColor,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
                            }
                        },
                        y: {
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
                            }
                        }
                    }
                }
            });
            charts.push(usersChart);
        }
    }
    
    // Initialize charts when they come into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                initCharts();
                observer.disconnect();
            }
        });
    }, { threshold: 0.1 });
    
    observer.observe(document.querySelector('.admin_graph_placeholder'));
</script>
</body>
</html>