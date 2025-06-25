<?php
// Start session and include database connection
session_start();
require_once '../db_connection.php';

// Check if user is logged in and is a partner
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get partner information
$partner_id = $_SESSION['user_id'];
$partner_query = "SELECT * FROM partners WHERE user_id = ?";
$stmt = $conn->prepare($partner_query);
$stmt->bind_param("i", $partner_id);
$stmt->execute();
$partner_result = $stmt->get_result();

if ($partner_result->num_rows === 0) {
    // User is not a partner
    header("Location: partner_dashboard.php");
    exit();
}

$partner = $partner_result->fetch_assoc();

$username_query = "SELECT username FROM users WHERE user_id = ?";
$stmt = $conn->prepare($username_query);
$stmt->bind_param("i", $partner_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $title = trim($_POST['bookTitle']);
    $writer = trim($_POST['bookWriter']);
    $genre = trim($_POST['bookGenre']);
    $language = trim($_POST['bookLanguage']);
    $description = trim($_POST['bookDescription']);

    // Handle file upload
    $cover_path = '';
    if (isset($_FILES['bookCover']) && $_FILES['bookCover']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/rent_book_covers/'; // Changed path to go up one level
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid() . '_' . basename($_FILES['bookCover']['name']);
        $target_path = $upload_dir . $file_name;

        // Check file type and size
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['bookCover']['type'];
        $file_size = $_FILES['bookCover']['size'];

        if (in_array($file_type, $allowed_types)) {
            if ($file_size <= 5000000) { // 5MB max
                if (move_uploaded_file($_FILES['bookCover']['tmp_name'], $target_path)) {
                    $cover_path = 'assets/rent_book_covers/' . $file_name; // Relative path for database
                }
            }
        }
    }

    // Validate required fields
    if (empty($title) || empty($writer) || empty($genre) || empty($description) || empty($cover_path)) {
        $_SESSION['error_message'] = "All fields are required, including the book cover.";
        header("Location: add_book.php");
        exit();
    }

    // Insert into rent_books table
    $insert_book_query = "INSERT INTO rent_books 
                         (title, writer, genre, language, poster_url, description, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($insert_book_query);
    $stmt->bind_param("ssssss", $title, $writer, $genre, $language, $cover_path, $description);

    if ($stmt->execute()) {
        $rent_book_id = $conn->insert_id;

        // Insert into partner_books table
        $insert_partner_book_query = "INSERT INTO partner_books 
                                    (partner_id, rent_book_id, added_at, status) 
                                    VALUES (?, ?, NOW(), 'pending')";

        $stmt = $conn->prepare($insert_partner_book_query);
        $stmt->bind_param("ii", $partner['partner_id'], $rent_book_id);

        if ($stmt->execute()) {
            // Success
            $_SESSION['success_message'] = "Book submitted successfully! It will be reviewed by our team before being listed.";
            header("Location: add_book.php");
            exit();
        } else {
            // Error with partner_books insertion
            $_SESSION['error_message'] = "Error submitting book. Please try again.";
        }
    } else {
        // Error with rent_books insertion
        $_SESSION['error_message'] = "Error submitting book. Please try again.";
    }
    header("Location: add_book.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book for Rent - Partner Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain the same */
        :root {
            --primary-color: #57abd2;
            --secondary-color: #f8f5fc;
            --accent-color: rgb(223, 219, 227);
            --text-color: #333;
            --light-purple: #e6d9f2;
            --dark-text: #212529;
            --light-text: #f8f9fa;
            --card-bg: #f8f9fa;
            --aside-bg: #f0f2f5;
            --nav-hover: #e0e0e0;
            --column-hover: #cee9ea;
        }

        .dark-mode {
            --primary-color: #57abd2;
            --secondary-color: #2d3748;
            --accent-color: #4a5568;
            --text-color: #f8f9fa;
            --light-purple: #4a5568;
            --dark-text: #f8f9fa;
            --light-text: #212529;
            --card-bg: #1a202c;
            --aside-bg: #1a202c;
            --nav-hover: #4a5568;
            --column-hover: #656565;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-container {
            display: flex;
            flex: 1;
        }

        aside {
            width: 250px;
            background-color: var(--aside-bg);
            padding: 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            border-right: 1px solid #ddd;
        }

        .nav-logo {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
            color: var(--primary-color);
        }

        nav ul {
            list-style: none;
        }

        nav ul li {
            margin-bottom: 10px;
        }

        nav ul li a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--text-color);
            border-radius: 5px;
            transition: all 0.3s;
        }

        nav ul li a:hover {
            background-color: var(--nav-hover);
        }

        nav ul li a.active {
            background-color: var(--primary-color);
            color: white;
        }

        nav ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        main {
            flex: 1;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        .card-header {
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #3a8fc7;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .preview-image {
            max-width: 200px;
            max-height: 300px;
            margin-top: 10px;
            display: none;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .file-input-label {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--accent-color);
            color: var(--text-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-label:hover {
            background-color: var(--nav-hover);
        }

        #bookCover {
            display: none;
        }

        .status-message {
            padding: 10px;
            margin-top: 20px;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            aside {
                width: 100%;
                height: auto;
                position: relative;
            }

            main {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include_once("../header.php") ?>
    
    <div class="main-container">
        <aside>
            <div class="nav-logo">
                <?php echo htmlspecialchars($user_data['username']) ?>
                <div style="font-size: 0.8rem; margin-top: 5px;">
                    Partner since <?php echo date('M Y', strtotime($partner['joined_at'])); ?>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="partner_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="add_book.php" class="active"><i class="fas fa-book"></i> Add Book</a></li>
                </ul>
            </nav>
        </aside>

        <main>
            <div class="page-header">
                <h1 class="page-title">Add Book for Rent</h1>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="status-message success">
                    <?php
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="status-message error">
                    <?php
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2 class="card-header">Book Information</h2>
                <form id="bookForm" method="POST" enctype="multipart/form-data" action="add_book.php">
                    <div class="form-group">
                        <label for="bookTitle">Book Title *</label>
                        <input type="text" id="bookTitle" name="bookTitle" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="bookWriter">Writer *</label>
                        <input type="text" id="bookWriter" name="bookWriter" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="bookGenre">Genre *</label>
                        <input type="text" id="bookGenre" name="bookGenre" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="bookLanguage">Language</label>
                        <input type="text" id="bookLanguage" name="bookLanguage" class="form-control" value="English">
                    </div>

                    <div class="form-group">
                        <label for="bookDescription">Description *</label>
                        <textarea id="bookDescription" name="bookDescription" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Book Cover *</label>
                        <label for="bookCover" class="file-input-label">
                            <i class="fas fa-upload"></i> Choose Cover Image
                        </label>
                        <input type="file" id="bookCover" name="bookCover" accept="image/*" required>
                        <img id="coverPreview" class="preview-image" src="#" alt="Cover Preview">
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Submit Book</button>
                        <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <?php include_once("../footer.php") ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Preview cover image when selected
            document.getElementById('bookCover').addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        const preview = document.getElementById('coverPreview');
                        preview.src = event.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Cancel button clears the form
            document.getElementById('cancelBtn').addEventListener('click', function () {
                document.getElementById('bookForm').reset();
                document.getElementById('coverPreview').style.display = 'none';
            });
        });
    </script>
</body>
</html>