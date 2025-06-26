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
  <!-- <link rel="stylesheet" href="/BookHeaven2.0/css/header.css"> -->
  <!-- Add jQuery for AJAX -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    :root {
      --bg-color: #ffffff;
      --text-color: #333333;
      --accent-color: #4361ee;
      --search-bg: #f0f2f5;
      --border-color: #dddddd;
      --icon-color: #5f6368;
      --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .dark-mode {
      --bg-color: #1a1a2e;
      --text-color: #f0f0f0;
      --accent-color: #4cc9f0;
      --search-bg: #2d3748;
      --border-color: #4a5568;
      --icon-color: #a0aec0;
      --shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      transition: background 0.3s, color 0.3s;
    }

    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
    }

    header {
      background-color: var(--bg-color);
      box-shadow: var(--shadow);
      position: sticky;
      top: 0;
      z-index: 100;
      padding: 15px 5%;
    }

    .header-container {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }

    .logo {
      font-size: 1.8rem;
      font-weight: bold;
      color: var(--accent-color);
      display: flex;
      align-items: center;
      text-decoration: none;
    }

    .logo i {
      margin-right: 8px;
    }

    .search-container {
      flex: 1;
      min-width: 200px;
      max-width: 600px;
      position: relative;
      margin: 0 auto;
    }

    .search-bar {
      width: 100%;
      padding: 15px 20px 15px 45px;
      height: 48px;
      border-radius: 30px;
      border: 1px solid var(--border-color);
      background-color: var(--search-bg);
      color: var(--text-color);
      font-size: 1rem;
    }

    .search-bar:focus {
      outline: 2px solid var(--accent-color);
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--icon-color);
    }

    nav {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin: 0 auto;
    }

    .nav-links {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      list-style: none;
      margin: 0 auto;
      padding: 0;
    }

    .nav-links a {
      text-decoration: none;
      color: var(--text-color);
      font-weight: 500;
      padding: 8px 15px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.3s;
      height: 100%;
    }

    .nav-links a:hover {
      background-color: var(--search-bg);
      color: var(--accent-color);
    }

    .nav-buttons {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-left:5px;
    }

    .btn {
      padding: 8px 12px;
      border-radius: 30px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.9rem;
    }

    .btn-primary {
      background-color: var(--accent-color);
      color: white;
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--accent-color);
      color: var(--accent-color);
    }

    .theme-toggle {
      background: none;
      border: none;
      color: var(--icon-color);
      cursor: pointer;
      font-size: 1.3rem;
      padding: 8px;
      border-radius: 50%;
      display: flex;
    }

    .theme-toggle:hover {
      background-color: var(--search-bg);
    }

    .menu-toggle {
      display: none;
      background: none;
      border: none;
      color: var(--icon-color);
      font-size: 1.5rem;
      cursor: pointer;
    }

    /* Responsive Styles */
    @media (max-width: 992px) {
      .nav-links a span {
        display: none;
      }

      .nav-links a i {
        font-size: 1.2rem;
      }

      .btn span {
        display: none;
      }
    }

    @media (max-width: 768px) {
      .header-container {
        justify-content: space-between;
      }

      .search-container {
        order: 3;
        flex: 100%;
        margin-top: 10px;
      }

      nav {
        order: 2;
      }

      .menu-toggle {
        display: block;
      }

      .nav-links {
        position: absolute;
        top: 80px;
        left: 0;
        background: var(--bg-color);
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        box-shadow: var(--shadow);
        display: none;
        z-index: 99;
      }

      .nav-links.active {
        display: flex;
      }

      .nav-links li {
        width: 100%;
      }

      .nav-links a {
        justify-content: flex-start;
        border-radius: 0;
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color);
      }

      .nav-links a span {
        display: inline-block;
      }
    }

    @media (max-width: 480px) {
      .logo span {
        display: none;
      }

      .header-container {
        gap: 10px;
      }

      .search-bar {
        height: 42px;
        padding: 12px 15px 12px 40px;
      }

      .btn {
        padding: 6px 10px;
        font-size: 0.8rem;
      }
    }

    .search-container {
      position: relative;
      width: 40%;
    }

    .search-suggestions {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border: 1px solid #ddd;
      border-radius: 4px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      display: none;
      max-height: 300px;
      overflow-y: auto;
    }

    .suggestion-item {
      padding: 10px 15px;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .suggestion-item:hover {
      background-color: #f5f5f5;
    }

    .suggestion-type {
      font-size: 0.8em;
      color: #666;
      background-color: #eee;
      padding: 2px 6px;
      border-radius: 3px;
    }

    .no-results {
      padding: 10px 15px;
      color: #666;
    }

    /* Dark mode styles */
    .dark-mode .search-suggestions {
      background: #333;
      border-color: #444;
    }

    .dark-mode .suggestion-item {
      color: #eee;
    }

    .dark-mode .suggestion-item:hover {
      background-color: #444;
    }

    .dark-mode .suggestion-type {
      color: #ccc;
      background-color: #555;
    }

    .dark-mode .no-results {
      color: #aaa;
    }
  </style>
</head>

<body>
  <header>
    <div class="header-container">
      <a href="/BookHeaven2.0/index.php" class="logo">
        <i class="fas fa-book-open"></i>
        <span>Book Haven</span>
      </a>

      <div class="search-container">
        <form action="/BookHeaven2.0/php/search.php" method="GET" id="searchForm">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="search-bar" name="query" id="searchInput" placeholder="Search books, authors..."
            autocomplete="off">
          <div class="search-suggestions" id="searchSuggestions"></div>
        </form>
      </div>

      <nav>
        <button class="menu-toggle" aria-label="Toggle menu">
          <i class="fas fa-bars"></i>
        </button>

        <ul class="nav-links">
          <li><a href="/BookHeaven2.0/php/music_player.php"><i class="fas fa-music"></i>
              <span>Audio</span></a></li>
          <li><a href="/BookHeaven2.0/php/cart.php"><i class="fas fa-shopping-cart"></i> <span
                class="cart-count"><?php echo $cart_count; ?></span></a></li>
          <li><a href="/BookHeaven2.0/php/profile.php"><i class="fas fa-user"></i> <span>Profile</span></a>
          </li>
          <li><a href="/BookHeaven2.0/php/partner_dashboard.php"><i class="fas fa-handshake"></i>
              <span>Partner</span></a></li>
          <li><a href="/BookHeaven2.0/php/community/dashboard.php"><i class="fas fa-users"></i> <span>Community</span></a></li>

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

    // Search functionality
    $(document).ready(function () {
      const searchInput = $('#searchInput');
      const searchSuggestions = $('#searchSuggestions');

      // Handle search input
      searchInput.on('input', function () {
        const query = $(this).val().trim();

        if (query.length > 0) {
          $.ajax({
            url: '/BookHeaven2.0/php/search_suggestions.php',
            method: 'GET',
            data: { query: query },
            success: function (data) {
              if (data.length > 0) {
                let suggestionsHtml = '';
                data.forEach(item => {
                  suggestionsHtml += `
                                        <div class="suggestion-item" data-type="${item.type}" data-id="${item.id}">
                                            ${item.name}
                                            <span class="suggestion-type">${item.type}</span>
                                        </div>
                                    `;
                });
                searchSuggestions.html(suggestionsHtml).show();
              } else {
                searchSuggestions.html('<div class="no-results">No results found</div>').show();
              }
            }
          });
        } else {
          searchSuggestions.hide();
        }
      });

      // Handle suggestion click
      $(document).on('click', '.suggestion-item', function () {
        const type = $(this).data('type');
        const id = $(this).data('id');
        const query = searchInput.val();

        // Redirect based on type
        if (type === 'book') {
          window.location.href = `/BookHeaven2.0/php/book_details.php?book_id=${id}`;
        } else if (type === 'author') {
          window.location.href = `/BookHeaven2.0/php/writer_books.php?writer_id=${id}`;
        } else if (type === 'genre') {
          window.location.href = `/BookHeaven2.0/php/genre_books.php?genre_id=${id}`;
        }
      });

      // Handle form submission (when user presses enter)
      $('#searchForm').on('submit', function (e) {
        e.preventDefault();
        const query = searchInput.val().trim();
        if (query) {
          window.location.href = `/BookHeaven2.0/php/search.php?query=${encodeURIComponent(query)}`;
        }
      });

      // Hide suggestions when clicking outside
      $(document).on('click', function (e) {
        if (!$(e.target).closest('.search-container').length) {
          searchSuggestions.hide();
        }
      });
    });
  </script>
</body>

</html>