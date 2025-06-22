<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

$event_id = $_GET['id'] ?? 0;
$event = [];
$error_message = '';
$success_message = '';

// Fetch event data
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $_SESSION['error_message'] = "Event not found!";
        header("Location: events.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching event: " . $e->getMessage();
    header("Location: events.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $venue = $_POST['venue'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'upcoming';

    try {
        // Handle file upload if a new poster is provided
        $banner_url = $event['banner_url'];
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/event_banners/';

            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $fileExt = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('event_') . '.' . $fileExt;
            $targetPath = $uploadDir . $filename;

            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array(strtolower($fileExt), $allowedTypes)) {
                throw new Exception("Only JPG, JPEG, PNG, and WEBP files are allowed.");
            }

            // Move uploaded file
            if (move_uploaded_file($_FILES['banner']['tmp_name'], $targetPath)) {
                // Delete old banner if it exists
                if ($banner_url && file_exists($banner_url)) {
                    unlink($banner_url);
                }
                $banner_url = $targetPath;
            } else {
                throw new Exception("Failed to upload image.");
            }
        }

        // Update event in database
        $stmt = $pdo->prepare(
            "UPDATE events SET 
                name = ?, 
                venue = ?, 
                event_date = ?, 
                description = ?, 
                banner_url = ?, 
                status = ?,
                updated_at = NOW()
             WHERE event_id = ?"
        );
        $stmt->execute([
            $name,
            $venue,
            $event_date,
            $description,
            $banner_url,
            $status,
            $event_id
        ]);

        $_SESSION['success_message'] = "Event updated successfully!";
        header("Location: events.php");
        exit();

    } catch (Exception $e) {
        $error_message = "Error updating event: " . $e->getMessage();
    }
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $error_message ?: ($_SESSION['error_message'] ?? '');
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Admin Dashboard</title>
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

        /* Form Styles */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
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
        .form-group input[type="datetime-local"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .admin-dark-mode .form-group input[type="text"],
        .admin-dark-mode .form-group input[type="datetime-local"],
        .admin-dark-mode .form-group textarea,
        .admin-dark-mode .form-group select {
            background-color: #3d3d3d;
            border-color: #444;
            color: #f0f0f0;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .file-upload {
            margin-top: 1rem;
        }

        .file-upload-preview {
            margin-top: 1rem;
            max-width: 300px;
            border: 1px solid #ddd;
            padding: 0.5rem;
            border-radius: 4px;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .admin-dark-mode .event-header {
            border-color: #444;
        }

        .admin-dark-mode .file-upload-preview {
            border-color: #444;
        }

        .file-upload-preview img {
            max-width: 100%;
            height: auto;
            display: block;
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
            margin-right: 1rem;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .admin_main {
                flex-direction: column;
            }

            .admin_sidebar {
                width: 100%;
            }

            .form-container {
                padding: 0 1rem;
            }
        }

        @media (max-width: 480px) {
            .admin_header_right {
                flex-direction: column;
                align-items: flex-end;
                gap: 0.5rem;
            }

            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
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
            <div class="form-container">
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <div class="event-header">
                    <h1>Edit Event</h1>
                    <a href="events.php" class="btn btn-secondary">Back to Events</a>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Event Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($event['name']) ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="venue">Venue</label>
                        <input type="text" id="venue" name="venue" value="<?= htmlspecialchars($event['venue']) ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="event_date">Event Date & Time</label>
                        <input type="datetime-local" id="event_date" name="event_date"
                            value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"
                            required><?= htmlspecialchars($event['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="upcoming" <?= $event['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming
                            </option>
                            <option value="ongoing" <?= $event['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing
                            </option>
                            <option value="completed" <?= $event['status'] === 'completed' ? 'selected' : '' ?>>Completed
                            </option>
                            <option value="cancelled" <?= $event['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Current Banner</label>
                        <?php if ($event['banner_url']): ?>
                            <div class="file-upload-preview">
                                <img src="/BookHeaven2.0/<?= htmlspecialchars($event['banner_url']) ?>"
                                    alt="Current Event Banner">
                            </div>
                        <?php else: ?>
                            <p>No banner uploaded</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="banner">Update Banner (optional)</label>
                        <input type="file" id="banner" name="banner" class="file-upload" accept="image/*">
                        <small>Allowed formats: JPG, JPEG, PNG, WEBP</small>
                    </div>

                    <div class="form-group">
                        <a href="events.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Event</button>
                    </div>
                </form>
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