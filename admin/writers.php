<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Initialize variables
$writers = [];
$stats = ['total_writers' => 0];
$error_message = '';
$success_message = '';
$edit_mode = false;
$writer_to_edit = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_writer'])) {
        // Enter edit mode for a specific writer
        $writer_id = $_POST['writer_id'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM writers WHERE writer_id = ?");
            $stmt->execute([$writer_id]);
            $writer_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
            $edit_mode = true;
        } catch (PDOException $e) {
            $error_message = "Error fetching writer: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_writer'])) {
        // Update writer information
        $writer_id = $_POST['writer_id'];
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $bio = $_POST['bio'] ?? '';
        $address = $_POST['address'] ?? '';
        
        try {
            // Handle file upload if a new image is provided
            $image_url = $_POST['current_image'] ?? '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'assets/writer_images/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('writer_') . '.' . $fileExt;
                $targetPath = $uploadDir . $filename;
                
                // Validate file type
                $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array(strtolower($fileExt), $allowedTypes)) {
                    throw new Exception("Only JPG, JPEG, PNG, and WEBP files are allowed.");
                }
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // Delete old image if it exists
                    if ($image_url && file_exists($image_url)) {
                        unlink($image_url);
                    }
                    $image_url = $targetPath;
                } else {
                    throw new Exception("Failed to upload image.");
                }
            }
            
            // Update writer in database
            $stmt = $pdo->prepare(
                "UPDATE writers SET 
                    name = ?, 
                    email = ?, 
                    bio = ?, 
                    address = ?, 
                    image_url = ?
                 WHERE writer_id = ?"
            );
            $stmt->execute([
                $name,
                $email,
                $bio,
                $address,
                $image_url,
                $writer_id
            ]);
            
            $success_message = "Writer updated successfully!";
            $edit_mode = false;
            
        } catch (Exception $e) {
            $error_message = "Error updating writer: " . $e->getMessage();
        }
    } elseif (isset($_POST['cancel_edit'])) {
        // Cancel edit mode
        $edit_mode = false;
    } elseif (isset($_POST['delete_writer'])) {
        // Delete writer
        $writer_id = $_POST['writer_id'];
        try {
            // First get the writer details to delete image
            $stmt = $pdo->prepare("SELECT image_url FROM writers WHERE writer_id = ?");
            $stmt->execute([$writer_id]);
            $writer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // First delete from book_writers junction table
            $stmt = $pdo->prepare("DELETE FROM book_writers WHERE writer_id = ?");
            $stmt->execute([$writer_id]);
            
            // Then delete the writer record
            $stmt = $pdo->prepare("DELETE FROM writers WHERE writer_id = ?");
            $stmt->execute([$writer_id]);
            
            // Delete associated image if it exists
            if ($writer['image_url'] && file_exists($writer['image_url'])) {
                unlink($writer['image_url']);
            }
            
            $_SESSION['success_message'] = "Writer deleted successfully!";
            header("Location: writers.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error deleting writer: " . $e->getMessage();
        }
    }
}

// Fetch writers data with book counts
try {
    // Get total number of writers
    $stats['total_writers'] = (int)$pdo->query("SELECT COUNT(*) FROM writers")->fetchColumn();
    
    // Get all writers with their book counts
    $writers = $pdo->query(
        "SELECT 
            w.writer_id,
            w.name,
            w.email,
            w.bio,
            w.address,
            w.image_url,
            COUNT(bw.book_id) AS book_count
         FROM writers w
         LEFT JOIN book_writers bw ON w.writer_id = bw.writer_id
         GROUP BY w.writer_id
         ORDER BY w.name"
    )->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching writers: " . $e->getMessage();
}

$success_message = $success_message ?: ($_SESSION['success_message'] ?? '');
$error_message = $error_message ?: ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Writers</title>
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

    /* Stats Grid */
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

    /* Tables Section */
    .section-title {
      margin: 2rem 0 1rem;
      color: #2c3e50;
      font-size: 1.5rem;
    }

    .admin-dark-mode .section-title {
      color: #f0f0f0;
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
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
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
      margin-right: .5rem
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

    /* Edit Form Styles */
    .edit-form-container {
      background: #fff;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 2rem;
    }

    .admin-dark-mode .edit-form-container {
      background: #3d3d3d;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: bold;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }

    .admin-dark-mode .form-group input[type="text"],
    .admin-dark-mode .form-group input[type="email"],
    .admin-dark-mode .form-group textarea {
      background-color: #3d3d3d;
      border-color: #444;
      color: #f0f0f0;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    .file-upload {
      margin-top: 1rem;
    }

    .file-upload-preview {
      margin-top: 1rem;
      max-width: 150px;
      border: 1px solid #ddd;
      padding: 0.5rem;
      border-radius: 4px;
    }

    .admin-dark-mode .file-upload-preview {
      border-color: #444;
    }

    .file-upload-preview img {
      max-width: 100%;
      height: auto;
      display: block;
      border-radius: 50%;
    }

    .form-actions {
      display: flex;
      gap: 1rem;
      margin-top: 1.5rem;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.3s;
    }

    .btn-primary {
      background-color: #3498db;
      color: white;
    }

    .btn-primary:hover {
      background-color: #2980b9;
    }

    .btn-secondary {
      background-color: #95a5a6;
      color: white;
    }

    .btn-secondary:hover {
      background-color: #7f8c8d;
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .admin_main {
        flex-direction: column;
      }
      
      .admin_sidebar {
        width: 100%;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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
      
      .admin_header_right {
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
      }
      
      .action-btn {
        padding: .3rem .6rem;
        font-size: .8rem;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
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
      <?php if ($error_message): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>
      <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>
      
      <h1>Writers Management</h1>
      
      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Writers</h3>
          <div class="stat-value"><?= number_format($stats['total_writers']) ?></div>
        </div>
        <div class="stat-card">
          <h3>Most Prolific</h3>
          <div class="stat-value">
            <?php 
              $most_books = 0;
              $prolific_writer = '';
              foreach ($writers as $writer) {
                if ($writer['book_count'] > $most_books) {
                  $most_books = $writer['book_count'];
                  $prolific_writer = $writer['name'];
                }
              }
              echo htmlspecialchars($prolific_writer ?: 'None');
            ?>
          </div>
        </div>
        <div class="stat-card">
          <h3>Total Books</h3>
          <div class="stat-value">
            <?php 
              $total_books = 0;
              foreach ($writers as $writer) {
                $total_books += $writer['book_count'];
              }
              echo number_format($total_books);
            ?>
          </div>
        </div>
      </div>
      
      <?php if ($edit_mode && $writer_to_edit): ?>
        <!-- Edit Form -->
        <div class="edit-form-container">
          <h2>Edit Writer: <?= htmlspecialchars($writer_to_edit['name']) ?></h2>
          
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="writer_id" value="<?= $writer_to_edit['writer_id'] ?>">
            <input type="hidden" name="current_image" value="<?= $writer_to_edit['image_url'] ?>">
            
            <div class="form-group">
              <label for="name">Name</label>
              <input type="text" id="name" name="name" value="<?= htmlspecialchars($writer_to_edit['name']) ?>" required>
            </div>
            
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($writer_to_edit['email']) ?>">
            </div>
            
            <div class="form-group">
              <label for="bio">Biography</label>
              <textarea id="bio" name="bio"><?= htmlspecialchars($writer_to_edit['bio']) ?></textarea>
            </div>
            
            <div class="form-group">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" value="<?= htmlspecialchars($writer_to_edit['address']) ?>">
            </div>
            
            <div class="form-group">
              <label>Current Image</label>
              <?php if ($writer_to_edit['image_url']): ?>
                <div class="file-upload-preview">
                  <img src="/BookHeaven2.0/<?= htmlspecialchars($writer_to_edit['image_url']) ?>" alt="Current Writer Image">
                </div>
              <?php else: ?>
                <p>No image uploaded</p>
              <?php endif; ?>
            </div>
            
            <div class="form-group">
              <label for="image">Update Image (optional)</label>
              <input type="file" id="image" name="image" class="file-upload" accept="image/*">
              <small>Allowed formats: JPG, JPEG, PNG, WEBP</small>
            </div>
            
            <div class="form-actions">
              <button type="submit" name="update_writer" class="btn btn-primary">Save Changes</button>
              <button type="submit" name="cancel_edit" class="btn btn-secondary">Cancel</button>
            </div>
          </form>
        </div>
      <?php endif; ?>
      
      <!-- Writers Table -->
      <h2 class="section-title">Writers List</h2>
      <table class="admin_table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Name</th>
            <th>Email</th>
            <th>Books</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($writers as $writer): ?>
            <tr>
              <td>#<?= $writer['writer_id'] ?></td>
              <td>
                <img src="/BookHeaven2.0/<?= $writer['image_url'] ? htmlspecialchars($writer['image_url']) : 'https://via.placeholder.com/50?text=No+Image' ?>" 
                     alt="<?= htmlspecialchars($writer['name']) ?>" class="user-avatar">
              </td>
              <td><?= htmlspecialchars($writer['name']) ?></td>
              <td><?= htmlspecialchars($writer['email']) ?></td>
              <td><?= $writer['book_count'] ?></td>
              <td>
                <form method="POST" class="action-form">
                  <input type="hidden" name="writer_id" value="<?= $writer['writer_id'] ?>">
                  <button type="submit" name="edit_writer" class="action-btn edit-btn">Edit</button>
                  <button type="submit" name="delete_writer" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this writer?')">Delete</button>
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