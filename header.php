<?php
$page_title = isset($page_title) ? $page_title : "Default Page Title";
$cart_count = 0;
$is_logged_in = false;

if (isset($_SESSION['user_id'])) {
    $is_logged_in = true;
    require_once 'db_connection.php';
    $user_id = $_SESSION['user_id'];
    $count_query = "SELECT COUNT(DISTINCT book_id) as book_count FROM cart WHERE user_id = $user_id";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $cart_count = $count_row['book_count'] ? $count_row['book_count'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/header.css">
</head>
<body>
    <header>
        <div class="header-container">
            <a href="/BookHeaven2.0/index.php" class="logo">
                <i class="fas fa-book-open"></i>
                <span>Book Haven</span>
            </a>
            
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-bar" placeholder="Search books, authors...">
            </div>
            
            <nav>
                <button class="menu-toggle" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="nav-links">
                    <li><a href="#"><i class="fas fa-key"></i> <span>Rent</span></a></li>
                    <li><a href="#"><i class="fas fa-music"></i> <span>Audio</span></a></li>
                    <li><a href="#"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                    <li><a href="/BookHeaven2.0/php/cart.php"><i class="fas fa-shopping-cart"></i> <span class="cart-count"><?php echo $cart_count; ?></span></a></li>
                    <li><a href="#"><i class="fas fa-bell"></i> <span>Notification</span></a></li>
                </ul>
                <div class="nav-buttons">
                    <button class="btn btn-outline" id="authBtn">
                        <?php if ($is_logged_in): ?>
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        <?php else: ?>
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        <?php endif; ?>
                    </button>
                    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <script>
        // Toggle mobile menu
        const menuToggle = document.querySelector('.menu-toggle');
        const navLinks = document.querySelector('.nav-links');
        
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('nav') && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });

        // Theme toggle with localStorage
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle.querySelector('i');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        let darkMode = localStorage.getItem('darkMode');
        
        // Check localStorage or preferred color scheme
        if (darkMode === 'enabled' || (!darkMode && prefersDark)) {
            enableDarkMode();
        }
        
        themeToggle.addEventListener('click', () => {
            darkMode = localStorage.getItem('darkMode');
            if (darkMode !== 'enabled') {
                enableDarkMode();
            } else {
                disableDarkMode();
            }
        });
        
        function enableDarkMode() {
            document.body.classList.add('dark-mode');
            icon.classList.replace('fa-moon', 'fa-sun');
            localStorage.setItem('darkMode', 'enabled');
        }
        
        function disableDarkMode() {
            document.body.classList.remove('dark-mode');
            icon.classList.replace('fa-sun', 'fa-moon');
            localStorage.setItem('darkMode', 'disabled');
        }

        // Login/Logout toggle (PHP session handling)
        const authBtn = document.getElementById('authBtn');
        
        authBtn.addEventListener('click', () => {
            <?php if ($is_logged_in): ?>
                window.location.href = '/BookHeaven2.0/php/logout.php';
            <?php else: ?>
                window.location.href = '/BookHeaven2.0/php/authentication.php';
            <?php endif; ?>
        });
    </script>
</body>
</html>
