<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Get book ID from URL
$book_id = $_GET['id'] ?? 0;

// Fetch book details
$book = [];
$categories = [];
$genres = [];
$languages = [];
$writers = [];
$bookCategories = [];
$bookGenres = [];
$bookLanguages = [];

try {
    // Get book info
    $stmt = $pdo->prepare(
        "SELECT b.*, w.writer_id, w.name AS writer_name
     FROM books b
     LEFT JOIN book_writers bw ON bw.book_id = b.book_id
     LEFT JOIN writers w ON w.writer_id = bw.writer_id
     WHERE b.book_id = ?"
    );
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $_SESSION['error_message'] = "Book not found!";
        header("Location: books.php");
        exit();
    }

    // Get all categories for dropdown
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

    // Get book's categories
    $stmt = $pdo->prepare(
        "SELECT c.id, c.name 
     FROM book_categories bc
     JOIN categories c ON c.id = bc.category_id
     WHERE bc.book_id = ?"
    );
    $stmt->execute([$book_id]);
    $bookCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all genres for dropdown
    $genres = $pdo->query("SELECT * FROM genres")->fetchAll(PDO::FETCH_ASSOC);

    // Get book's genres
    $stmt = $pdo->prepare(
        "SELECT g.genre_id, g.name 
     FROM book_genres bg
     JOIN genres g ON g.genre_id = bg.genre_id
     WHERE bg.book_id = ?"
    );
    $stmt->execute([$book_id]);
    $bookGenres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all languages for dropdown
    $languages = $pdo->query("SELECT * FROM languages")->fetchAll(PDO::FETCH_ASSOC);

    // Get book's languages
    $stmt = $pdo->prepare(
        "SELECT l.language_id, l.name 
     FROM book_languages bl
     JOIN languages l ON l.language_id = bl.language_id
     WHERE bl.book_id = ?"
    );
    $stmt->execute([$book_id]);
    $bookLanguages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all writers for dropdown
    $writers = $pdo->query("SELECT * FROM writers")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching book details: " . $e->getMessage();
    header("Location: books.php");
    exit();
}

// Handle book update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $title = $_POST['title'];
    $published = $_POST['published'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $details = $_POST['details'];
    $writer_id = $_POST['writer_id'];
    $selected_categories = $_POST['categories'] ?? [];
    $selected_genres = $_POST['genres'] ?? [];
    $selected_languages = $_POST['languages'] ?? [];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Update books table
        $stmt = $pdo->prepare(
            "UPDATE books SET 
        title = ?, published = ?, price = ?, 
        quantity = ?, details = ?
       WHERE book_id = ?"
        );
        $stmt->execute([$title, $published, $price, $quantity, $details, $book_id]);

        // Update writer mapping
        $pdo->prepare("DELETE FROM book_writers WHERE book_id = ?")->execute([$book_id]);
        $writerStmt = $pdo->prepare("INSERT INTO book_writers (book_id, writer_id) VALUES (?, ?)");
        $writerStmt->execute([$book_id, $writer_id]);

        // Update categories
        $pdo->prepare("DELETE FROM book_categories WHERE book_id = ?")->execute([$book_id]);
        $categoryStmt = $pdo->prepare("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)");
        foreach ($selected_categories as $cat_id) {
            $categoryStmt->execute([$book_id, $cat_id]);
        }

        // Update genres
        $pdo->prepare("DELETE FROM book_genres WHERE book_id = ?")->execute([$book_id]);
        $genreStmt = $pdo->prepare("INSERT INTO book_genres (book_id, genre_id) VALUES (?, ?)");
        foreach ($selected_genres as $genre_id) {
            $genreStmt->execute([$book_id, $genre_id]);
        }

        // Update languages
        $pdo->prepare("DELETE FROM book_languages WHERE book_id = ?")->execute([$book_id]);
        $languageStmt = $pdo->prepare("INSERT INTO book_languages (book_id, language_id) VALUES (?, ?)");
        foreach ($selected_languages as $lang_id) {
            $languageStmt->execute([$book_id, $lang_id]);
        }

        $pdo->commit();
        $_SESSION['success_message'] = "Book updated successfully!";
        header("Location: books.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error updating book: " . $e->getMessage();
        header("Location: bookedit.php?id=$book_id");
        exit();
    }
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
    <title>Admin Dashboard - Edit Book</title>
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

        .book-cover {
            max-width: 200px;
            max-height: 300px;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .subscription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .admin-dark-mode .subscription-header {
            border-color: #444;
        }

        .input-field {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .admin-dark-mode .input-field {
            background: #3d3d3d;
            border-color: #555;
            color: #f0f0f0
        }

        textarea.input-field {
            min-height: 150px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .save-btn {
            background: #2ecc71;
            color: white;
        }

        .save-btn:hover {
            background: #27ae60;
        }

        .cancel-btn {
            background: #95a5a6;
            color: white;
            margin-right: 1rem;
        }

        .cancel-btn:hover {
            background: #7f8c8d;
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
                    <li><a href="admin_dashboard.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li><a href="add.php"><i class="fas fa-plus-circle"></i> Add</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                    <li><a href="writers.php"><i class="fas fa-pen-fancy"></i> Writers</a></li>
                    <li><a href="books.php" class="active"><i class="fas fa-book"></i> Books</a></li>
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
            <div class="subscription-header">
                <h1 class="form-title">Edit Books</h1>
                <a href="books.php" class="btn btn-secondary">Back to Books</a>
            </div>
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" class="input-field"
                        value="<?= htmlspecialchars($book['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="writer_id">Author</label>
                    <select id="writer_id" name="writer_id" class="input-field" required>
                        <?php foreach ($writers as $writer): ?>
                            <option value="<?= $writer['writer_id'] ?>" <?= ($writer['writer_id'] == $book['writer_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($writer['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="published">Published Date</label>
                    <input type="date" id="published" name="published" class="input-field"
                        value="<?= htmlspecialchars($book['published']) ?>">
                </div>

                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" step="0.01" id="price" name="price" class="input-field"
                        value="<?= htmlspecialchars($book['price']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" class="input-field"
                        value="<?= htmlspecialchars($book['quantity']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Categories</label>
                    <div class="checkbox-group">
                        <?php foreach ($categories as $category): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="cat_<?= $category['id'] ?>" name="categories[]"
                                    value="<?= $category['id'] ?>" <?= in_array($category['id'], array_column($bookCategories, 'id')) ? 'checked' : '' ?>>
                                <label for="cat_<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Genres</label>
                    <div class="checkbox-group">
                        <?php foreach ($genres as $genre): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="genre_<?= $genre['genre_id'] ?>" name="genres[]"
                                    value="<?= $genre['genre_id'] ?>" <?= in_array($genre['genre_id'], array_column($bookGenres, 'genre_id')) ? 'checked' : '' ?>>
                                <label for="genre_<?= $genre['genre_id'] ?>"><?= htmlspecialchars($genre['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Languages</label>
                    <div class="checkbox-group">
                        <?php foreach ($languages as $language): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="lang_<?= $language['language_id'] ?>" name="languages[]"
                                    value="<?= $language['language_id'] ?>" <?= in_array($language['language_id'], array_column($bookLanguages, 'language_id')) ? 'checked' : '' ?>>
                                <label
                                    for="lang_<?= $language['language_id'] ?>"><?= htmlspecialchars($language['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="details">Details</label>
                    <textarea id="details" name="details"
                        class="input-field"><?= htmlspecialchars($book['details']) ?></textarea>
                </div>

                <div class="form-group">
                    <img src="/BookHeaven2.0/<?= htmlspecialchars($book['cover_image_url']) ?>" alt="Book Cover"
                        class="book-cover">
                    <label for="cover_image">Change Cover Image</label>
                    <input type="file" id="cover_image" name="cover_image" class="input-field" accept="image/*">
                </div>

                <div class="form-group">
                    <button type="button" class="action-btn cancel-btn"
                        onclick="window.location.href='books.php'">Cancel</button>
                    <button type="submit" name="update_book" class="action-btn save-btn">Save Changes</button>
                </div>
            </form>
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