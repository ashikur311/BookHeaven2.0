<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Initialize variables
$audiobooks = [];
$error_message = '';
$success_message = '';
$edit_mode = false;
$audiobook_to_edit = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_audiobook'])) {
        // Enter edit mode for a specific audiobook
        $audiobook_id = $_POST['audiobook_id'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM audiobooks WHERE audiobook_id = ?");
            $stmt->execute([$audiobook_id]);
            $audiobook_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
            $edit_mode = true;
        } catch (PDOException $e) {
            $error_message = "Error fetching audiobook: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_audiobook'])) {
        // Update audiobook information
        $audiobook_id = $_POST['audiobook_id'];
        $title = $_POST['title'] ?? '';
        $writer = $_POST['writer'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $category = $_POST['category'] ?? '';
        $language = $_POST['language'] ?? '';
        $description = $_POST['description'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $status = $_POST['status'] ?? 'visible';
        
        try {
            // Handle poster upload if a new image is provided
            $poster_url = $_POST['current_poster'] ?? '';
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'assets/audiobook_covers/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $fileExt = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('audiobook_') . '.' . $fileExt;
                $targetPath = $uploadDir . $filename;
                
                // Validate file type
                $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array(strtolower($fileExt), $allowedTypes)) {
                    throw new Exception("Only JPG, JPEG, PNG, and WEBP files are allowed for posters.");
                }
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['poster']['tmp_name'], $targetPath)) {
                    // Delete old poster if it exists
                    if ($poster_url && file_exists($poster_url)) {
                        unlink($poster_url);
                    }
                    $poster_url = $targetPath;
                } else {
                    throw new Exception("Failed to upload poster image.");
                }
            }
            
            // Handle audio file upload if a new file is provided
            $audio_url = $_POST['current_audio'] ?? '';
            if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'assets/audiobooks/';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $fileExt = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('audio_') . '.' . $fileExt;
                $targetPath = $uploadDir . $filename;
                
                // Validate file type
                $allowedTypes = ['mp3', 'm4a', 'wav', 'ogg'];
                if (!in_array(strtolower($fileExt), $allowedTypes)) {
                    throw new Exception("Only MP3, M4A, WAV, and OGG files are allowed for audio.");
                }
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['audio']['tmp_name'], $targetPath)) {
                    // Delete old audio file if it exists
                    if ($audio_url && file_exists($audio_url)) {
                        unlink($audio_url);
                    }
                    $audio_url = $targetPath;
                } else {
                    throw new Exception("Failed to upload audio file.");
                }
            }
            
            // Update audiobook in database
            $stmt = $pdo->prepare(
                "UPDATE audiobooks SET 
                    title = ?, 
                    writer = ?, 
                    genre = ?, 
                    category = ?, 
                    language = ?, 
                    audio_url = ?,
                    poster_url = ?,
                    description = ?,
                    duration = ?,
                    status = ?,
                    updated_at = NOW()
                 WHERE audiobook_id = ?"
            );
            $stmt->execute([
                $title,
                $writer,
                $genre,
                $category,
                $language,
                $audio_url,
                $poster_url,
                $description,
                $duration,
                $status,
                $audiobook_id
            ]);
            
            $success_message = "Audiobook updated successfully!";
            $edit_mode = false;
            
        } catch (Exception $e) {
            $error_message = "Error updating audiobook: " . $e->getMessage();
        }
    } elseif (isset($_POST['cancel_edit'])) {
        // Cancel edit mode
        $edit_mode = false;
    } elseif (isset($_POST['delete_audiobook'])) {
        // Delete audiobook
        $audiobook_id = $_POST['audiobook_id'];
        try {
            // First get the audiobook details to delete files
            $stmt = $pdo->prepare("SELECT poster_url, audio_url FROM audiobooks WHERE audiobook_id = ?");
            $stmt->execute([$audiobook_id]);
            $audiobook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete the audiobook record
            $stmt = $pdo->prepare("DELETE FROM audiobooks WHERE audiobook_id = ?");
            $stmt->execute([$audiobook_id]);
            
            // Delete associated files if they exist
            if ($audiobook['poster_url'] && file_exists($audiobook['poster_url'])) {
                unlink($audiobook['poster_url']);
            }
            if ($audiobook['audio_url'] && file_exists($audiobook['audio_url'])) {
                unlink($audiobook['audio_url']);
            }
            
            $_SESSION['success_message'] = "Audiobook deleted successfully!";
            header("Location: audiobooks.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error deleting audiobook: " . $e->getMessage();
        }
    }
}

// Fetch audiobooks data
try {
    $audiobooks = $pdo->query(
        "SELECT 
            audiobook_id,
            title,
            writer,
            genre,
            category,
            language,
            audio_url,
            poster_url,
            description,
            duration,
            status,
            created_at
         FROM audiobooks
         ORDER BY created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching audiobooks: " . $e->getMessage();
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
  <title>Admin Dashboard - Audiobooks</title>
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

    .cover-image {
      width: 60px;
      height: 80px;
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
    .form-group input[type="time"],
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }

    .admin-dark-mode .form-group input[type="text"],
    .admin-dark-mode .form-group input[type="time"],
    .admin-dark-mode .form-group select,
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
      max-width: 200px;
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
    }

    .audio-preview {
      margin-top: 1rem;
    }

    .audio-preview audio {
      width: 100%;
      max-width: 300px;
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
    <div class="logo"><img src="/BookHeaven2.0/assets/images/logo.png" alt="Logo"></div>
    <h1>Admin Dashboard</h1>
    <div class="admin_header_right">
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
          <li><a href="books.php"><i class="fas fa-book"></i> Books</a></li>
          <li><a href="audiobooks.php" class="active"><i class="fas fa-headphones"></i> Audiobooks</a></li>
          <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
          <li><a href="subscription.php"><i class="fas fa-star"></i> Subscription</a></li>
          <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
          <li><a href="writers.php"><i class="fas fa-pen-fancy"></i> Writers</a></li>
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
      
      <h1>Audiobooks Management</h1>
      
      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Audiobooks</h3>
          <div class="stat-value"><?= count($audiobooks) ?></div>
        </div>
        <div class="stat-card">
          <h3>Visible</h3>
          <div class="stat-value">
            <?= count(array_filter($audiobooks, function($ab) { return $ab['status'] === 'visible'; })) ?>
          </div>
        </div>
        <div class="stat-card">
          <h3>Hidden</h3>
          <div class="stat-value">
            <?= count(array_filter($audiobooks, function($ab) { return $ab['status'] === 'hidden'; })) ?>
          </div>
        </div>
        <div class="stat-card">
          <h3>Pending</h3>
          <div class="stat-value">
            <?= count(array_filter($audiobooks, function($ab) { return $ab['status'] === 'pending'; })) ?>
          </div>
        </div>
      </div>
      
      <?php if ($edit_mode && $audiobook_to_edit): ?>
        <!-- Edit Form -->
        <div class="edit-form-container">
          <h2>Edit Audiobook: <?= htmlspecialchars($audiobook_to_edit['title']) ?></h2>
          
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="audiobook_id" value="<?= $audiobook_to_edit['audiobook_id'] ?>">
            <input type="hidden" name="current_poster" value="<?= $audiobook_to_edit['poster_url'] ?>">
            <input type="hidden" name="current_audio" value="<?= $audiobook_to_edit['audio_url'] ?>">
            
            <div class="form-group">
              <label for="title">Title</label>
              <input type="text" id="title" name="title" value="<?= htmlspecialchars($audiobook_to_edit['title']) ?>" required>
            </div>
            
            <div class="form-group">
              <label for="writer">Writer</label>
              <input type="text" id="writer" name="writer" value="<?= htmlspecialchars($audiobook_to_edit['writer']) ?>" required>
            </div>
            
            <div class="form-group">
              <label for="genre">Genre</label>
              <input type="text" id="genre" name="genre" value="<?= htmlspecialchars($audiobook_to_edit['genre']) ?>" required>
            </div>
            
            <div class="form-group">
              <label for="category">Category</label>
              <input type="text" id="category" name="category" value="<?= htmlspecialchars($audiobook_to_edit['category']) ?>" required>
            </div>
            
            <div class="form-group">
              <label for="language">Language</label>
              <input type="text" id="language" name="language" value="<?= htmlspecialchars($audiobook_to_edit['language']) ?>">
            </div>
            
            <div class="form-group">
              <label for="description">Description</label>
              <textarea id="description" name="description"><?= htmlspecialchars($audiobook_to_edit['description']) ?></textarea>
            </div>
            
            <div class="form-group">
              <label for="duration">Duration (HH:MM:SS)</label>
              <input type="time" id="duration" name="duration" step="1" value="<?= $audiobook_to_edit['duration'] ?>">
            </div>
            
            <div class="form-group">
              <label for="status">Status</label>
              <select id="status" name="status" required>
                <option value="visible" <?= $audiobook_to_edit['status'] === 'visible' ? 'selected' : '' ?>>Visible</option>
                <option value="hidden" <?= $audiobook_to_edit['status'] === 'hidden' ? 'selected' : '' ?>>Hidden</option>
                <option value="pending" <?= $audiobook_to_edit['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Current Poster</label>
              <?php if ($audiobook_to_edit['poster_url']): ?>
                <div class="file-upload-preview">
                  <img src="/BookHeaven2.0/<?= htmlspecialchars($audiobook_to_edit['poster_url']) ?>" alt="Current Audiobook Poster">
                </div>
              <?php else: ?>
                <p>No poster uploaded</p>
              <?php endif; ?>
            </div>
            
            <div class="form-group">
              <label for="poster">Update Poster (optional)</label>
              <input type="file" id="poster" name="poster" class="file-upload" accept="image/*">
              <small>Allowed formats: JPG, JPEG, PNG, WEBP</small>
            </div>
            
            <div class="form-group">
              <label>Current Audio File</label>
              <?php if ($audiobook_to_edit['audio_url']): ?>
                <div class="audio-preview">
                  <audio controls>
                    <source src="/BookHeaven2.0/<?= htmlspecialchars($audiobook_to_edit['audio_url']) ?>" type="audio/mpeg">
                    Your browser does not support the audio element.
                  </audio>
                </div>
              <?php else: ?>
                <p>No audio file uploaded</p>
              <?php endif; ?>
            </div>
            
            <div class="form-group">
              <label for="audio">Update Audio File (optional)</label>
              <input type="file" id="audio" name="audio" class="file-upload" accept="audio/*">
              <small>Allowed formats: MP3, M4A, WAV, OGG</small>
            </div>
            
            <div class="form-actions">
              <button type="submit" name="update_audiobook" class="btn btn-primary">Save Changes</button>
              <button type="submit" name="cancel_edit" class="btn btn-secondary">Cancel</button>
            </div>
          </form>
        </div>
      <?php endif; ?>
      
      <!-- Audiobooks Table -->
      <h2 class="section-title">Audiobooks List</h2>
      <table class="admin_table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Poster</th>
            <th>Title</th>
            <th>Writer</th>
            <th>Genre</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($audiobooks as $audiobook): ?>
            <tr>
              <td>#<?= $audiobook['audiobook_id'] ?></td>
              <td>
                <img src="/BookHeaven2.0/<?= $audiobook['poster_url'] ? htmlspecialchars($audiobook['poster_url']) : 'https://via.placeholder.com/60x80?text=No+Poster' ?>" 
                     alt="<?= htmlspecialchars($audiobook['title']) ?>" class="cover-image">
              </td>
              <td><?= htmlspecialchars($audiobook['title']) ?></td>
              <td><?= htmlspecialchars($audiobook['writer']) ?></td>
              <td><?= htmlspecialchars($audiobook['genre']) ?></td>
              <td><?= ucfirst($audiobook['status']) ?></td>
              <td>
                <form method="POST" class="action-form">
                  <input type="hidden" name="audiobook_id" value="<?= $audiobook['audiobook_id'] ?>">
                  <button type="submit" name="edit_audiobook" class="action-btn edit-btn">Edit</button>
                  <button type="submit" name="delete_audiobook" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this audiobook?')">Delete</button>
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