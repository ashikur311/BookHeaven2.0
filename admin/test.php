<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: logout.php");
        exit();
    }
    
    // Check if user is a partner
    $is_partner = false;
    $partner_stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = ?");
    $partner_stmt->execute([$user_id]);
    if ($partner_stmt->fetch()) {
        $is_partner = true;
    }
    
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Handle theme preference
$theme = $_COOKIE['theme'] ?? 'light';
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    setcookie('theme', $theme, time() + (86400 * 30), "/"); // 30 days
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'] ?? $user['name'];
    $email = $_POST['email'] ?? $user['email'];
    $phone = $_POST['phone'] ?? $user['phone'];
    $address = $_POST['address'] ?? $user['address'];
    
    try {
        // Handle file upload if a new image is provided
        $profile_image = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/profile_images/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExt = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('profile_') . '.' . $fileExt;
            $targetPath = $uploadDir . $filename;
            
            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array(strtolower($fileExt), $allowedTypes)) {
                $_SESSION['error_message'] = "Only JPG, JPEG, PNG, and WEBP files are allowed.";
                header("Location: profile.php");
                exit();
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                // Delete old image if it exists
                if ($profile_image && file_exists($profile_image)) {
                    unlink($profile_image);
                }
                $profile_image = $targetPath;
            } else {
                throw new Exception("Failed to upload profile image.");
            }
        }
        
        // Update user in database
        $stmt = $pdo->prepare(
            "UPDATE users SET 
                name = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                profile_image = ?
             WHERE user_id = ?"
        );
        $stmt->execute([
            $name,
            $email,
            $phone,
            $address,
            $profile_image,
            $user_id
        ]);
        
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating profile: " . $e->getMessage();
        header("Location: profile.php");
        exit();
    }
}

// Handle partner application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_partner'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO partner_applications (user_id, application_date) VALUES (?, NOW())");
        $stmt->execute([$user_id]);
        
        $_SESSION['success_message'] = "Partner application submitted successfully!";
        header("Location: profile.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error submitting partner application: " . $e->getMessage();
        header("Location: profile.php");
        exit();
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - BookHeaven</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-dark: #37474F;
      --primary: #546E7A;
      --primary-light: #90A4AE;
      --primary-very-light: #B0BEC5;
      --primary-extra-light: #CFD8DC;
    }
    
    [data-theme="dark"] {
      --primary-dark: #CFD8DC;
      --primary: #B0BEC5;
      --primary-light: #90A4AE;
      --primary-very-light: #546E7A;
      --primary-extra-light: #37474F;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--primary-extra-light);
      color: var(--primary-dark);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Header Styles */
    header {
      background-color: var(--primary);
      color: white;
      padding: 1rem;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: bold;
    }

    .logo a {
      color: white;
      text-decoration: none;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .theme-toggle {
      background: none;
      border: none;
      color: white;
      cursor: pointer;
      font-size: 1.2rem;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid white;
    }

    /* Main Layout */
    .profile-container {
      display: flex;
      flex: 1;
    }

    /* Sidebar Navigation */
    aside {
      width: 250px;
      background-color: var(--primary-very-light);
      padding: 1.5rem 0;
    }

    .profile-nav ul {
      list-style: none;
    }

    .profile-nav li a {
      display: flex;
      align-items: center;
      padding: 0.8rem 1.5rem;
      color: var(--primary-dark);
      text-decoration: none;
      transition: all 0.3s;
    }

    .profile-nav li a:hover,
    .profile-nav li a.active {
      background-color: var(--primary-light);
      color: white;
    }

    .profile-nav li a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    /* Main Content */
    main {
      flex: 1;
      padding: 2rem;
      background-color: white;
    }

    [data-theme="dark"] main {
      background-color: var(--primary-extra-light);
    }

    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--primary-very-light);
    }

    .profile-title {
      font-size: 1.8rem;
      color: var(--primary);
    }

    .edit-btn {
      background-color: var(--primary);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .edit-btn:hover {
      background-color: var(--primary-dark);
    }

    /* Profile Grid */
    .profile-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-bottom: 2rem;
    }

    .profile-card {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
    }

    [data-theme="dark"] .profile-card {
      background-color: var(--primary-very-light);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid var(--primary-very-light);
    }

    .card-title {
      font-size: 1.2rem;
      color: var(--primary);
    }

    .profile-info {
      display: grid;
      grid-template-columns: max-content 1fr;
      gap: 1rem;
    }

    .info-label {
      font-weight: bold;
      color: var(--primary);
    }

    .profile-image-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
    }

    .profile-image {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--primary-light);
    }

    .partner-status {
      display: inline-block;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: bold;
    }

    .partner-true {
      background-color: #4CAF50;
      color: white;
    }

    .partner-false {
      background-color: #F44336;
      color: white;
    }

    .become-partner-btn {
      background-color: var(--primary);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
      margin-top: 1rem;
    }

    .become-partner-btn:hover {
      background-color: var(--primary-dark);
    }

    /* Profile Form */
    .profile-form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: bold;
      color: var(--primary);
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--primary-very-light);
      border-radius: 4px;
      font-size: 1rem;
      background-color: white;
      color: var(--primary-dark);
    }

    [data-theme="dark"] .form-group input,
    [data-theme="dark"] .form-group textarea {
      background-color: var(--primary-very-light);
      border-color: var(--primary-light);
      color: var(--primary-dark);
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-actions {
      grid-column: span 2;
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      margin-top: 1rem;
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
      background-color: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background-color: var(--primary-dark);
    }

    .btn-secondary {
      background-color: var(--primary-very-light);
      color: var(--primary-dark);
    }

    .btn-secondary:hover {
      background-color: var(--primary-light);
      color: white;
    }

    /* Footer */
    footer {
      background-color: var(--primary);
      color: white;
      padding: 1.5rem;
      text-align: center;
    }

    .footer-links {
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      margin-bottom: 1rem;
    }

    .footer-links a {
      color: white;
      text-decoration: none;
    }

    .footer-links a:hover {
      text-decoration: underline;
    }

    .copyright {
      font-size: 0.9rem;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
      .profile-container {
        flex-direction: column;
      }
      
      aside {
        width: 100%;
      }
      
      .profile-nav ul {
        display: flex;
        overflow-x: auto;
        padding: 0 1rem;
      }
      
      .profile-nav li {
        flex-shrink: 0;
      }
    }

    @media (max-width: 768px) {
      .profile-form {
        grid-template-columns: 1fr;
      }
      
      .form-actions {
        grid-column: span 1;
      }
      
      .profile-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 576px) {
      header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }
      
      .profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
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
  <!-- Header -->
  <header>
    <div class="logo">
      <a href="index.php">BookHeaven</a>
    </div>
    <div class="header-actions">
      <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
      </button>
      <img src="<?= $user['profile_image'] ? htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150?text=No+Image' ?>" 
           alt="Profile Image" class="user-avatar">
    </div>
  </header>

  <div class="profile-container">
    <!-- Sidebar Navigation -->
    <aside>
      <nav class="profile-nav">
        <ul>
          <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
          <li><a href="wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a></li>
          <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
          <li><a href="subscription.php"><i class="fas fa-star"></i> My Subscription</a></li>
          <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
          <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
          <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main>
      <div class="profile-header">
        <h1 class="profile-title">My Profile</h1>
        <button class="edit-btn" id="editProfileBtn">
          <i class="fas fa-edit"></i> Edit Profile
        </button>
      </div>

      <?php if ($error_message): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>
      <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>

      <div class="profile-grid">
        <!-- Profile Summary Card -->
        <div class="profile-card">
          <div class="card-header">
            <h2 class="card-title">Profile Summary</h2>
          </div>
          <div class="profile-image-container">
            <img src="<?= $user['profile_image'] ? htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/150?text=No+Image' ?>" 
                 alt="Profile Image" class="profile-image">
            <span class="partner-status <?= $is_partner ? 'partner-true' : 'partner-false' ?>">
              <?= $is_partner ? 'Partner' : 'Regular Member' ?>
            </span>
            <?php if (!$is_partner): ?>
              <form method="POST">
                <button type="submit" name="apply_partner" class="become-partner-btn">
                  Become a Partner
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Account Details Card -->
        <div class="profile-card">
          <div class="card-header">
            <h2 class="card-title">Account Details</h2>
          </div>
          <div class="profile-info">
            <span class="info-label">Join Date:</span>
            <span><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
            
            <span class="info-label">Email:</span>
            <span><?= htmlspecialchars($user['email']) ?></span>
            
            <span class="info-label">Status:</span>
            <span><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span>
            
            <span class="info-label">Role:</span>
            <span><?= $is_partner ? 'Partner' : 'Customer' ?></span>
          </div>
        </div>
      </div>

      <!-- Profile Details Form -->
      <div class="profile-card" id="profileFormContainer" style="display: none;">
        <div class="card-header">
          <h2 class="card-title">Edit Profile</h2>
        </div>
        <form method="POST" enctype="multipart/form-data" class="profile-form">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
          </div>
          
          <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address"><?= htmlspecialchars($user['address']) ?></textarea>
          </div>
          
          <div class="form-group">
            <label for="profile_image">Profile Image</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">
            <small>Allowed formats: JPG, JPEG, PNG, WEBP</small>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <!-- Footer -->
  <footer>
    <div class="footer-links">
      <a href="about.php">About Us</a>
      <a href="contact.php">Contact</a>
      <a href="privacy.php">Privacy Policy</a>
      <a href="terms.php">Terms of Service</a>
    </div>
    <div class="copyright">
      &copy; <?= date('Y') ?> BookHeaven. All rights reserved.
    </div>
  </footer>

  <script>
    // Theme Toggle Functionality
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle.querySelector('i');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    
    // Set initial icon
    if (currentTheme === 'dark') {
      icon.classList.replace('fa-moon', 'fa-sun');
    }
    
    // Toggle theme on button click
    themeToggle.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      
      document.documentElement.setAttribute('data-theme', newTheme);
      if (newTheme === 'dark') {
        icon.classList.replace('fa-moon', 'fa-sun');
      } else {
        icon.classList.replace('fa-sun', 'fa-moon');
      }
      
      // Update theme preference via URL parameter
      window.location.href = `profile.php?theme=${newTheme}`;
    });
    
    // Profile Edit Toggle
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const profileFormContainer = document.getElementById('profileFormContainer');
    
    editProfileBtn.addEventListener('click', () => {
      profileFormContainer.style.display = 'block';
      window.scrollTo({
        top: profileFormContainer.offsetTop - 20,
        behavior: 'smooth'
      });
    });
    
    cancelEditBtn.addEventListener('click', () => {
      profileFormContainer.style.display = 'none';
    });
  </script>
</body>
</html>