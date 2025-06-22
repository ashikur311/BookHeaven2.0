<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
  header("Location: authentication.php");
  exit();
}

// Handle book deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
  $book_id = $_POST['book_id'];
  try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete related records first
    $pdo->prepare("DELETE FROM book_categories WHERE book_id = ?")->execute([$book_id]);
    $pdo->prepare("DELETE FROM book_genres WHERE book_id = ?")->execute([$book_id]);
    $pdo->prepare("DELETE FROM book_languages WHERE book_id = ?")->execute([$book_id]);
    $pdo->prepare("DELETE FROM book_writers WHERE book_id = ?")->execute([$book_id]);
    $pdo->prepare("DELETE FROM cart WHERE book_id = ?")->execute([$book_id]);
    $pdo->prepare("DELETE FROM order_items WHERE book_id = ?")->execute([$book_id]);
    $pdo->prepare("DELETE FROM questions WHERE book_id = ?")->execute([$book_id]);
    $pdo->prepare("DELETE FROM reviews WHERE book_id = ?")->execute([$book_id]);
    $pdo->prepare("DELETE FROM wishlist WHERE book_id = ?")->execute([$book_id]);
    
    // Finally delete the book
    $pdo->prepare("DELETE FROM books WHERE book_id = ?")->execute([$book_id]);
    
    $pdo->commit();
    $_SESSION['success_message'] = "Book #{$book_id} deleted successfully!";
  } catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Error deleting book: " . $e->getMessage();
  }
  header("Location: books.php");
  exit();
}

// Fetch stats and books
$stats = ['total_books' => 0, 'stock_out' => 0, 'new_this_month' => 0];
$books = [];
try {
  $stats['total_books'] = (int) $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
  $stats['stock_out'] = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE quantity = 0")->fetchColumn();
  $stats['new_this_month'] = (int) $pdo->query(
    "SELECT COUNT(*) FROM books
         WHERE created_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
           AND created_at <  DATE_FORMAT(CURRENT_DATE() + INTERVAL 1 MONTH, '%Y-%m-01')"
  )->fetchColumn();
  $stmt = $pdo->query(
    "SELECT b.book_id, b.title, w.name AS author, b.quantity, b.price,
                b.created_at, b.cover_image_url AS cover_image_url
         FROM books b
         LEFT JOIN book_writers bw ON bw.book_id = b.book_id
         LEFT JOIN writers w ON w.writer_id = bw.writer_id
         ORDER BY b.created_at DESC"
  );
  $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $_SESSION['error_message'] = "Error fetching books: " . $e->getMessage();
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
  <title>Admin Dashboard - Books</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/BookHeaven2.0/css/partners.css">
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
      color: #fff
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

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem
    }

    .stat-card {
      background: #fff;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
      text-align: center
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

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover
    }

    .action-btn {
      padding: .5rem 1rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: .9rem;
      transition: all .3s
    }

    .view-btn {
      background: #3498db;
      color: #fff;
      margin-right: .5rem
    }

    .view-btn:hover {
      background: #2980b9
    }

    .delete-btn {
      background: #e74c3c;
      color: #fff
    }

    .delete-btn:hover {
      background: #c0392b
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

    .action-form {
      display: inline;
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
                   <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
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
      <h2>Book Statistics</h2>
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Books</h3>
          <div class="stat-value"><?= $stats['total_books'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Stock Out</h3>
          <div class="stat-value"><?= $stats['stock_out'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Added This Month</h3>
          <div class="stat-value"><?= $stats['new_this_month'] ?></div>
        </div>
      </div>
      <?php if ($error_message): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
      <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
      <h2>Books Inventory</h2>
      <table class="admin_table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Cover</th>
            <th>Title</th>
            <th>Author</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($books as $b): ?>
            <tr>
              <td>#<?= $b['book_id'] ?></td>
              <td>
                <img src="/BookHeaven2.0/<?php echo $b['cover_image_url'] ? $b['cover_image_url'] : 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'; ?>"
                  alt="" class="user-avatar">
              </td>
              <td><?= htmlspecialchars($b['title']) ?></td>
              <td><?= htmlspecialchars($b['author'] ?: 'Unknown') ?></td>
              <td><?= $b['quantity'] ?><?php if ($b['quantity'] == 0): ?> <span style="color:red">(Stock
                    Out)</span><?php endif; ?></td>
              <td>$<?= number_format($b['price'], 2) ?></td>
              <td>
                <a href="bookedit.php?id=<?= $b['book_id'] ?>" class="action-btn view-btn">Edit</a>
                <form class="action-form" method="POST" onsubmit="return confirm('Are you sure you want to delete this book?');">
                  <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                  <button type="submit" name="delete_book" class="action-btn delete-btn">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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