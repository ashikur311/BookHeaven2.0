<?php
session_start();
// Database connection
require_once 'db.php';

// Get all genres, categories, writers, and languages for dropdowns
$genres = [];
$categories = [];
$writers = [];
$languages = [];

try {
    $stmt = $pdo->query("SELECT * FROM genres");
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM writers");
    $writers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM languages WHERE status = 'active'");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted
    if (isset($_POST['add_book'])) {
        handleBookSubmission();
    } elseif (isset($_POST['add_audiobook'])) {
        handleAudiobookSubmission();
    } elseif (isset($_POST['add_subscription'])) {
        handleSubscriptionSubmission();
    } elseif (isset($_POST['add_event'])) {
        handleEventSubmission();
    }
}

function handleBookSubmission()
{
    global $pdo;

    // Validate and sanitize input
    $title = trim($_POST['title']);
    $published = $_POST['published'];
    $price = $_POST['price'] ?? 0;
    $details = trim($_POST['details']);
    $writer_id = $_POST['writer_id'];
    $genre_id = $_POST['genre_id'];
    $category_id = $_POST['category_id'];
    $language_id = $_POST['language_id'];

    // Handle file upload
    $cover_image_url = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/book_covers/';

        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error_message'] = "Failed to create upload directory";
                header("Location: add.php");
                exit();
            }
        }

        $fileExt = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $fileName = preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
            // Store relative path in database
            $cover_image_url = 'assets/book_covers/' . $fileName;
        } else {
            $_SESSION['error_message'] = "Failed to upload cover image";
            header("Location: add.php");
            exit();
        }
    }

    try {
        // Insert book with price
        $stmt = $pdo->prepare("INSERT INTO books (title, published, price, details, cover_image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $published, $price, $details, $cover_image_url]);
        $book_id = $pdo->lastInsertId();

        // Link writer
        $stmt = $pdo->prepare("INSERT INTO book_writers (book_id, writer_id) VALUES (?, ?)");
        $stmt->execute([$book_id, $writer_id]);

        // Link genre
        $stmt = $pdo->prepare("INSERT INTO book_genres (book_id, genre_id) VALUES (?, ?)");
        $stmt->execute([$book_id, $genre_id]);

        // Link category
        $stmt = $pdo->prepare("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)");
        $stmt->execute([$book_id, $category_id]);

        // Link language
        $stmt = $pdo->prepare("INSERT INTO book_languages (book_id, language_id) VALUES (?, ?)");
        $stmt->execute([$book_id, $language_id]);

        $_SESSION['success_message'] = "Book added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error adding book: " . $e->getMessage();
    }

    header("Location: add.php");
    exit();
}

function handleAudiobookSubmission()
{
    global $pdo;

    // Validate and sanitize input
    $title = trim($_POST['audio_title']);
    $writer = trim($_POST['audio_writer']);
    $genre = trim($_POST['audio_genre']);
    $category = trim($_POST['audio_category']);
    $language_id = $_POST['audio_language_id'];
    $description = trim($_POST['audio_description']);
    $duration = $_POST['audio_duration'];

    // Get language name from ID
    $language_name = '';
    try {
        $stmt = $pdo->prepare("SELECT name FROM languages WHERE language_id = ?");
        $stmt->execute([$language_id]);
        $language = $stmt->fetch(PDO::FETCH_ASSOC);
        $language_name = $language['name'] ?? '';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error getting language: " . $e->getMessage();
        header("Location: add.php#audiobook-tab");
        exit();
    }

    // Handle file uploads
    $audio_url = '';
    $poster_url = '';

    // Audio file upload
    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/audiobooks/';

        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error_message'] = "Failed to create audio upload directory";
                header("Location: add.php#audiobook-tab");
                exit();
            }
        }

        $fileExt = pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION);
        $fileName = preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $uploadPath)) {
            $audio_url = 'assets/audiobooks/' . $fileName;
        } else {
            $_SESSION['error_message'] = "Failed to upload audio file";
            header("Location: add.php#audiobook-tab");
            exit();
        }
    }

    // Poster image upload
    if (isset($_FILES['audio_poster']) && $_FILES['audio_poster']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/audiobook_covers/';

        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error_message'] = "Failed to create poster upload directory";
                header("Location: add.php#audiobook-tab");
                exit();
            }
        }

        $fileExt = pathinfo($_FILES['audio_poster']['name'], PATHINFO_EXTENSION);
        $fileName = preg_replace('/[^a-zA-Z0-9]/', '_', $title) . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['audio_poster']['tmp_name'], $uploadPath)) {
            $poster_url = 'assets/audiobook_covers/' . $fileName;
        } else {
            $_SESSION['error_message'] = "Failed to upload poster image";
            header("Location: add.php#audiobook-tab");
            exit();
        }
    }

    try {
        // Insert audiobook with language name
        $stmt = $pdo->prepare("INSERT INTO audiobooks (title, writer, genre, category, language, audio_url, poster_url, description, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $writer, $genre, $category, $language_name, $audio_url, $poster_url, $description, $duration]);

        $_SESSION['success_message'] = "Audiobook added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error adding audiobook: " . $e->getMessage();
    }

    header("Location: add.php#audiobook-tab");
    exit();
}

function handleSubscriptionSubmission()
{
    global $pdo;

    // Validate and sanitize input
    $plan_name = trim($_POST['plan_name']);
    $price = $_POST['price'];
    $validity_days = $_POST['validity_days'];
    $book_quantity = $_POST['book_quantity'];
    $audiobook_quantity = $_POST['audiobook_quantity'];
    $description = trim($_POST['plan_description']);
    $status = $_POST['status'];

    try {
        // Insert subscription plan
        $stmt = $pdo->prepare("INSERT INTO subscription_plans (plan_name, price, validity_days, book_quantity, audiobook_quantity, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$plan_name, $price, $validity_days, $book_quantity, $audiobook_quantity, $description, $status]);

        $_SESSION['success_message'] = "Subscription plan added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error adding subscription plan: " . $e->getMessage();
    }

    header("Location: add.php#subscription-tab");
    exit();
}

function handleEventSubmission()
{
    global $pdo;

    // Validate and sanitize input
    $name = trim($_POST['event_name']);
    $venue = trim($_POST['event_venue']);
    $event_date = $_POST['event_date'];
    $description = trim($_POST['event_description']);
    $status = $_POST['event_status'];

    // Handle file upload
    $banner_url = '';
    if (isset($_FILES['event_banner']) && $_FILES['event_banner']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/event_banners/';
        $fileExt = pathinfo($_FILES['event_banner']['name'], PATHINFO_EXTENSION);
        $fileName = preg_replace('/[^a-zA-Z0-9]/', '_', $name) . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['event_banner']['tmp_name'], $uploadPath)) {
            $banner_url = $uploadPath;
        }
    }

    try {
        // Insert event
        $stmt = $pdo->prepare("INSERT INTO events (name, venue, event_date, description, banner_url, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $venue, $event_date, $description, $banner_url, $status]);

        $_SESSION['success_message'] = "Event added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error adding event: " . $e->getMessage();
    }

    header("Location: add.php#event-tab");
    exit();
}

// Display success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Add Content - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/BKH/css/admin_add.css">
</head>

<body>
    <div class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </div>
    <header>
        <nav class="admin_header">
            <div class="logo">
                <img src="images/logo.png" alt="Logo">
            </div>
            <div class="admin_header_right">
                <h1>Admin Dashboard</h1>
                <p>Welcome, Admin</p>
                <button class="admin_theme_toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </nav>
    </header>
    <main class="admin_main">
        <aside class="admin_sidebar">
            <nav class="admin_sidebar_nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-fw fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="add.php" class="active"><i class="fas fa-fw fa-plus-circle"></i> <span>Add</span></a>
                    </li>
                    <li><a href="Users.php"><i class="fas fa-fw fa-users"></i> <span>Users</span></a></li>
                    <li><a href="partners.php"><i class="fas fa-fw fa-handshake"></i> <span>Partners</span></a></li>
                    <li><a href="books.php"><i class="fas fa-fw fa-book"></i> <span>Books</span></a></li>
                    <li><a href="rentbooks.php"><i class="fas fa-fw fa-book-open"></i> <span>Rent Books</span></a></li>
                    <li><a href="audiobook.php"><i class="fas fa-fw fa-headphones"></i> <span>Audio Books</span></a>
                    </li>
                    <li><a href="orders.php"><i class="fas fa-fw fa-shopping-cart"></i> <span>Orders</span></a></li>
                    <li><a href="subscription.php"><i class="fas fa-fw fa-star"></i> <span>Subscription</span></a></li>
                    <li><a href="events.php"><i class="fas fa-fw fa-calendar-alt"></i> <span>Events</span></a></li>
                    <li><a href="reports.php"><i class="fas fa-fw fa-chart-bar"></i> <span>Reports</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>
        <div class="admin_main_content">
            <!-- Display success/error messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="admin_tabs">
                <div class="admin_tab active" data-tab="book">Book</div>
                <div class="admin_tab" data-tab="audiobook">Audio Book</div>
                <div class="admin_tab" data-tab="subscription">Subscription</div>
                <div class="admin_tab" data-tab="event">Event</div>
            </div>

            <!-- Book Tab Content -->
            <div class="admin_tab_content active" id="book-tab">
                <h2>Add New Book</h2>
                <form action="add.php" method="POST" enctype="multipart/form-data">
                    <div class="admin_form_group">
                        <label for="title">Book Title</label>
                        <input type="text" id="title" name="title" class="admin_form_control" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="published">Published Date</label>
                        <input type="date" inputmode="none" id="published" name="published" class="admin_form_control"
                            required>
                    </div>

                    <div class="admin_form_group">
                        <label for="price">Price</label>
                        <input type="number" inputmode="numeric" pattern="[0-9]*" id="price" name="price"
                            class="admin_form_control" min="0" step="0.01">
                    </div>

                    <div class="admin_form_group">
                        <label for="writer_id">Writer</label>
                        <select id="writer_id" name="writer_id" class="admin_form_control admin_select2" required>
                            <option value="">Select Writer</option>
                            <?php foreach ($writers as $writer): ?>
                                <option value="<?= $writer['writer_id'] ?>"><?= htmlspecialchars($writer['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Can't find the writer? <a href="#" id="addNewWriter">Add New Writer</a></small>
                    </div>

                    <div class="admin_form_group">
                        <label for="genre_id">Genre</label>
                        <select id="genre_id" name="genre_id" class="admin_form_control admin_select2" required>
                            <option value="">Select Genre</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?= $genre['genre_id'] ?>"><?= htmlspecialchars($genre['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Can't find the genre? <a href="#" id="addNewGenre">Add New Genre</a></small>
                    </div>

                    <div class="admin_form_group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="admin_form_control admin_select2" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Can't find the category? <a href="#" id="addNewCategory">Add New Category</a></small>
                    </div>

                    <div class="admin_form_group">
                        <label for="language_id">Language</label>
                        <select id="language_id" name="language_id" class="admin_form_control admin_select2" required>
                            <option value="">Select Language</option>
                            <?php foreach ($languages as $language): ?>
                                <option value="<?= $language['language_id'] ?>"><?= htmlspecialchars($language['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Can't find the language? <a href="#" id="addNewLanguage">Add New Language</a></small>
                    </div>

                    <div class="admin_form_group">
                        <label for="details">Book Details</label>
                        <textarea id="details" name="details" class="admin_form_control" rows="5"></textarea>
                    </div>

                    <div class="admin_form_group">
                        <label for="cover_image">Cover Image</label>
                        <input type="file" id="cover_image" name="cover_image" class="admin_form_control"
                            accept="image/*">
                        <small>Image will be saved in assets/bookcover/ with the book title as filename</small>
                    </div>

                    <button type="submit" name="add_book" class="admin_btn">Add Book</button>
                </form>
            </div>

            <!-- Audio Book Tab Content -->
            <div class="admin_tab_content" id="audiobook-tab">
                <h2>Add New Audio Book</h2>
                <form action="add.php" method="POST" enctype="multipart/form-data">
                    <div class="admin_form_group">
                        <label for="audio_title">Title</label>
                        <input type="text" id="audio_title" name="audio_title" class="admin_form_control" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="audio_writer">Writer</label>
                        <input type="text" id="audio_writer" name="audio_writer" class="admin_form_control" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="audio_genre">Genre</label>
                        <input type="text" id="audio_genre" name="audio_genre" class="admin_form_control" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="audio_category">Category</label>
                        <input type="text" id="audio_category" name="audio_category" class="admin_form_control"
                            required>
                    </div>

                    <div class="admin_form_group">
                        <label for="audio_language_id">Language</label>
                        <select id="audio_language_id" name="audio_language_id" class="admin_form_control admin_select2"
                            required>
                            <option value="">Select Language</option>
                            <?php foreach ($languages as $language): ?>
                                <option value="<?= $language['language_id'] ?>"><?= htmlspecialchars($language['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Can't find the language? <a href="#" id="addNewLanguage">Add New Language</a></small>
                    </div>

                    <div class="admin_form_group">
                        <label for="audio_description">Description</label>
                        <textarea id="audio_description" name="audio_description" class="admin_form_control"
                            rows="5"></textarea>
                    </div>

                    <div class="admin_form_group">
                        <label for="audio_duration">Duration (HH:MM:SS)</label>
                        <input type="text" id="audio_duration" name="audio_duration" class="admin_form_control"
                            placeholder="00:45:30" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="audio_file">Audio File</label>
                        <input type="file" id="audio_file" name="audio_file" class="admin_form_control" accept="audio/*"
                            required>
                        <small>Supported formats: MP3, WAV, AAC</small>
                    </div>

                    <div class="admin_form_group">
                        <label for="audio_poster">Poster Image</label>
                        <input type="file" id="audio_poster" name="audio_poster" class="admin_form_control"
                            accept="image/*">
                        <small>Optional cover image for the audiobook</small>
                    </div>

                    <button type="submit" name="add_audiobook" class="admin_btn">Add Audio Book</button>
                </form>
            </div>

            <!-- Subscription Tab Content -->
            <div class="admin_tab_content" id="subscription-tab">
                <h2>Add New Subscription Plan</h2>
                <form action="add.php" method="POST">
                    <div class="admin_form_group">
                        <label for="plan_name">Plan Name</label>
                        <input type="text" id="plan_name" name="plan_name" class="admin_form_control" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="price">Price</label>
                        <input type="number" id="price" name="price" class="admin_form_control" min="0" step="0.01"
                            required>
                    </div>

                    <div class="admin_form_group">
                        <label for="validity_days">Validity (Days)</label>
                        <input type="number" id="validity_days" name="validity_days" class="admin_form_control" min="1"
                            required>
                    </div>

                    <div class="admin_form_group">
                        <label for="book_quantity">Number of Books Allowed</label>
                        <input type="number" id="book_quantity" name="book_quantity" class="admin_form_control" min="0"
                            required>
                    </div>

                    <div class="admin_form_group">
                        <label for="audiobook_quantity">Number of Audiobooks Allowed</label>
                        <input type="number" id="audiobook_quantity" name="audiobook_quantity"
                            class="admin_form_control" min="0" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="plan_description">Description</label>
                        <textarea id="plan_description" name="plan_description" class="admin_form_control"
                            rows="5"></textarea>
                    </div>

                    <div class="admin_form_group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="admin_form_control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" name="add_subscription" class="admin_btn">Add Subscription Plan</button>
                </form>
            </div>

            <!-- Event Tab Content -->
            <div class="admin_tab_content" id="event-tab">
                <h2>Add New Event</h2>
                <form action="add.php" method="POST" enctype="multipart/form-data">
                    <div class="admin_form_group">
                        <label for="event_name">Event Name</label>
                        <input type="text" id="event_name" name="event_name" class="admin_form_control" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="event_venue">Venue</label>
                        <input type="text" id="event_venue" name="event_venue" class="admin_form_control" required>
                    </div>

                    <div class="admin_form_group">
                        <label for="event_date">Event Date & Time</label>
                        <input type="datetime-local" id="event_date" name="event_date" class="admin_form_control"
                            required>
                    </div>

                    <div class="admin_form_group">
                        <label for="event_description">Description</label>
                        <textarea id="event_description" name="event_description" class="admin_form_control"
                            rows="5"></textarea>
                    </div>

                    <div class="admin_form_group">
                        <label for="event_banner">Banner Image</label>
                        <input type="file" id="event_banner" name="event_banner" class="admin_form_control"
                            accept="image/*">
                        <small>Optional banner image for the event</small>
                    </div>

                    <div class="admin_form_group">
                        <label for="event_status">Status</label>
                        <select id="event_status" name="event_status" class="admin_form_control" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <button type="submit" name="add_event" class="admin_btn">Add Event</button>
                </form>
            </div>
        </div>
    </main>

    <!-- Modal for adding new items -->
    <div id="adminModal" class="admin_modal">
        <div class="admin_modal_content">
            <span class="admin_modal_close">&times;</span>
            <h3 id="modalTitle">Add New Item</h3>
            <form id="modalForm">
                <div class="admin_form_group">
                    <label id="modalFieldLabel">Name</label>
                    <input type="text" id="modalFieldInput" class="admin_form_control" required>
                    <input type="hidden" id="modalType" value="">
                </div>
                <button type="submit" class="admin_btn">Add</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

        // Update Select2 theme
        $('.admin_select2').select2({
            theme: document.body.classList.contains('admin-dark-mode') ? 'dark' : 'default',
            dropdownParent: $('.admin_main_content'),
            width: '100%',
            dropdownAutoWidth: true
        });
    });

    // Mobile menu toggle functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.admin_sidebar');
    const body = document.body;

    // Responsive sidebar state management
    function handleSidebarState() {
        if (window.innerWidth <= 992) {
            body.classList.add('sidebar-collapsed');
            mobileMenuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
        } else {
            body.classList.remove('sidebar-collapsed');
        }
    }

    mobileMenuToggle.addEventListener('click', function() {
        body.classList.toggle('sidebar-collapsed');
        
        // Change icon based on state
        const icon = this.querySelector('i');
        if (body.classList.contains('sidebar-collapsed')) {
            icon.classList.replace('fa-bars', 'fa-times');
        } else {
            icon.classList.replace('fa-times', 'fa-bars');
        }
    });

    // Close sidebar when clicking on a link (for mobile)
    document.querySelectorAll('.admin_sidebar_nav a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                body.classList.add('sidebar-collapsed');
                mobileMenuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
            }
        });
    });

    // Close sidebar when clicking outside (for mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth > 992) return;
        
        const isClickInsideSidebar = sidebar.contains(e.target);
        const isClickOnToggle = mobileMenuToggle.contains(e.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle) {
            body.classList.add('sidebar-collapsed');
            mobileMenuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
        }
    });

    // Initialize on page load and window resize
    window.addEventListener('resize', handleSidebarState);
    handleSidebarState();

    // Tab functionality - improved version
    $(document).ready(function() {
        // Initialize tabs - show first tab by default
        $('.admin_tab_content').hide().first().show();
        $('.admin_tab').first().addClass('active');

        // Tab click handler
        $('.admin_tab').click(function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            $('.admin_tab').removeClass('active');
            
            // Add active class to clicked tab
            $(this).addClass('active');
            
            // Hide all tab contents
            $('.admin_tab_content').hide();
            
            // Show the corresponding tab content
            const tabId = $(this).data('tab') + '-tab';
            $('#' + tabId).show();
            
            // Reinitialize Select2 in the active tab if needed
            $('#' + tabId).find('.admin_select2').select2({
                theme: document.body.classList.contains('admin-dark-mode') ? 'dark' : 'default',
                dropdownParent: $('.admin_main_content'),
                width: '100%',
                dropdownAutoWidth: true
            });
        });

        // Initialize Select2 with proper dropdown positioning
        $('.admin_select2').select2({
            theme: document.body.classList.contains('admin-dark-mode') ? 'dark' : 'default',
            dropdownParent: $('.admin_main_content'),
            width: '100%',
            dropdownAutoWidth: true
        });

        // Add new writer modal
        $('#addNewWriter').click(function(e) {
            e.preventDefault();
            showModal('Add New Writer', 'Writer Name', 'writer');
        });

        // Add new genre modal
        $('#addNewGenre').click(function(e) {
            e.preventDefault();
            showModal('Add New Genre', 'Genre Name', 'genre');
        });

        // Add new category modal
        $('#addNewCategory').click(function(e) {
            e.preventDefault();
            showModal('Add New Category', 'Category Name', 'category');
        });

        // Add new language modal
        $('#addNewLanguage').click(function(e) {
            e.preventDefault();
            showModal('Add New Language', 'Language Name', 'language');
        });

        // Close modal when clicking the X button
        $('.admin_modal_close').click(function() {
            $('#adminModal').hide();
        });

        // Close modal when clicking outside
        $(window).click(function(e) {
            if (e.target == document.getElementById('adminModal')) {
                $('#adminModal').hide();
            }
        });

        // Handle modal form submission
        $('#modalForm').submit(function(e) {
            e.preventDefault();
            const type = $('#modalType').val();
            const value = $('#modalFieldInput').val();

            if (!value.trim()) {
                alert('Please enter a valid name');
                return;
            }

            // AJAX call to add new item
            $.ajax({
                url: 'add_item.php',
                method: 'POST',
                data: {
                    type: type,
                    name: value,
                    status: type === 'language' ? 'active' : null
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let selectId, selectElement;

                        switch (type) {
                            case 'writer':
                                selectId = 'writer_id';
                                break;
                            case 'genre':
                                selectId = 'genre_id';
                                break;
                            case 'category':
                                selectId = 'category_id';
                                break;
                            case 'language':
                                // Update both language selects
                                ['language_id', 'audio_language_id'].forEach(id => {
                                    selectElement = $('#' + id);
                                    const newOption = new Option(value, response.id, true, true);
                                    selectElement.append(newOption).trigger('change');
                                });
                                break;
                            default:
                                selectId = '';
                        }

                        if (selectId && type !== 'language') {
                            selectElement = $('#' + selectId);
                            const newOption = new Option(value, response.id, true, true);
                            selectElement.append(newOption).trigger('change');
                        }

                        // Close modal
                        $('#adminModal').hide();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error adding item: ' + error);
                }
            });
        });
    });

    function showModal(title, fieldLabel, type) {
        $('#modalTitle').text(title);
        $('#modalFieldLabel').text(fieldLabel);
        $('#modalType').val(type);
        $('#modalFieldInput').val('');
        $('#adminModal').show();
    }
</script>
</body>

</html>