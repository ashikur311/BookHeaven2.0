<?php
require_once 'db_connection.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (isset($_POST['add_to_cart'])) {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $book_id = (int) $_POST['book_id'];

    if (!$user_id) {
        $_SESSION['error_message'] = 'Please login to add items to cart';
        header("Location: login.php");
        exit();
    }

    $check_query = "SELECT * FROM cart WHERE user_id = $user_id AND book_id = $book_id";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {

        $_SESSION['info_message'] = 'This book is already in your cart';
    } else {

        $insert_query = "INSERT INTO cart (user_id, book_id, quantity) VALUES ($user_id, $book_id, 1)";
        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['success_message'] = 'Book added to cart successfully!';
        } else {
            $_SESSION['error_message'] = 'Error adding to cart: ' . mysqli_error($conn);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHeaven - Your Literary Paradise</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- <link rel="stylesheet" href="/BookHeaven2.0/css/home.css"> -->
     <style>
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
  --border-color: #dee2e6;
  --promo-card-bg: #ffffff;
  --book-card-bg: #ffffff;
  --writer-section-bg: #ffffff;
  --filter-section-bg: #ffffff;
  --genre-section-bg: #ffffff;
  --header-bg: #ffffff;
  --footer-bg: #f8f9fa;
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
  --border-color: #4a5568;
  --promo-card-bg: #2d3748;
  --book-card-bg: #2d3748;
  --writer-section-bg: #2d3748;
  --filter-section-bg: #2d3748;
  --genre-section-bg: #2d3748;
  --header-bg: #1a202c;
  --footer-bg: #1a202c;
}

body {
  font-family: "Nunito", sans-serif;
  color: var(--dark-text);
  background-color: var(--secondary-color);
  transition: background-color 0.3s, color 0.3s;
}

/* Header Section */
.header-section {
  margin-bottom: 30px;
}

/* Main Content Sections */
.content-container {
  display: flex;
  flex-wrap: wrap;
  gap: 2%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 15px;
}

.left-content {
  flex: 0 0 30%;
  max-width: 30%;
}

.right-content {
  flex: 0 0 66%;
  max-width: 66%;
}

/* Carousel Styling */
.carousel-wrapper {
  margin-bottom: 30px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
  border-radius: 10px;
  overflow: hidden;
  margin-left: 50px;
  margin-right: 50px;
  margin-top: 20px;
  border-width: 1rem;
  border-style: solid;
  border-color: var(--border-color);
  transition: border-color 0.3s;
}

.carousel-item img {
  height: 400px;
  object-fit: cover;
}

/* Left Side Sections */
.filter-section,
.writer-section,
.genre-section {
  background: var(--card-bg);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border-width: 0.1rem;
  border-style: solid;
  border-color: var(--border-color);
  transition: background-color 0.3s, border-color 0.3s;
}

.writer-section {
  background: var(--writer-section-bg);
}

.filter-section {
  background: var(--filter-section-bg);
}

.genre-section {
  background: var(--genre-section-bg);
}

.section-title {
  color: var(--primary-color);
  font-size: 18px;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--light-purple);
  transition: color 0.3s, border-color 0.3s;
}

.filter-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.filter-btn {
  background-color: var(--light-purple);
  color: var(--primary-color);
  border: none;
  padding: 8px 15px;
  border-radius: 5px;
  text-align: left;
  transition: all 0.3s;
  cursor: pointer;
}

.filter-btn:hover,
.filter-btn.active {
  background-color: var(--primary-color);
  color: white;
}

.filter-btn.active {
  font-weight: bold;
}

.writer-list,
.genre-list {
  list-style: none;
  padding: 0;
}

.writer-item {
  display: flex;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--border-color);
  transition: border-color 0.3s;
}

.writer-item:last-child {
  border-bottom: none;
}

.writer-img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 10px;
}

.writer-info {
  flex: 1;
}

.writer-name {
  color: var(--dark-text);
  font-weight: 600;
  margin-bottom: 2px;
  font-size: 14px;
  transition: color 0.3s;
}

.writer-name a {
  color: inherit;
  text-decoration: none;
}

.writer-name a:hover {
  color: var(--primary-color);
}

.writer-genre {
  color: var(--text-color);
  font-size: 16px;
  font-weight: bold;
  transition: color 0.3s;
}

.genre-list li {
  padding: 8px 0;
  border-bottom: 1px solid var(--border-color);
  transition: border-color 0.3s;
}

.genre-list li:last-child {
  border-bottom: none;
}

.genre-list li a {
  color: var(--dark-text);
  text-decoration: none;
  transition: color 0.3s, padding-left 0.3s;
  display: block;
}

.genre-list li a:hover {
  color: var(--primary-color);
  padding-left: 5px;
}

/* Right Side Sections */
.promo-section {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  padding: 20px;
  background: var(--light-purple);
  transition: background-color 0.3s;
}

.promo-card {
  flex: 1;
  background: var(--promo-card-bg);
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s, box-shadow 0.3s, background-color 0.3s;
  text-align: center;
}

.promo-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

.promo-card img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 8px;
  margin-bottom: 15px;
}

.promo-card h3 {
  color: var(--primary-color);
  margin-bottom: 15px;
  transition: color 0.3s;
}

.promo-card p {
  color: var(--text-color);
  transition: color 0.3s;
}

.btn-promo {
  background-color: var(--primary-color);
  color: white;
  border: none;
  padding: 8px 20px;
  border-radius: 5px;
  transition: background-color 0.3s;
  display: inline-block;
  width: auto;
  margin: 0 auto;
}

.btn-promo:hover {
  background-color: var(--accent-color);
}

/* Books Section */
.books-section {
  background: var(--card-bg);
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--border-color);
  transition: background-color 0.3s, border-color 0.3s;
}

.section-heading {
  color: var(--primary-color);
  margin-bottom: 20px;
  font-size: 22px;
  padding-bottom: 10px;
  border-bottom: 2px solid var(--light-purple);
  transition: color 0.3s, border-color 0.3s;
}

.book-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}

.book-card {
  background: var(--book-card-bg);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
  transition: transform 0.3s, box-shadow 0.3s, background-color 0.3s;
  border: 1px solid var(--border-color);
}

.book-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.book-cover {
  width: 100%;
  height: 250px;
  object-fit: cover;
}

.book-info {
  padding: 15px;
  text-align: center;
}

.book-title {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 5px;
  color: var(--primary-color);
  transition: color 0.3s;
}

.book-author {
  color: var(--text-color);
  margin-bottom: 10px;
  font-size: 14px;
  transition: color 0.3s;
}

.book-rating {
  color: #ffc107;
  margin-bottom: 10px;
  font-size: 14px;
}

.book-price {
  font-weight: bold;
  font-size: 16px;
  margin-bottom: 15px;
  color: var(--accent-color);
  transition: color 0.3s;
}

.add-to-cart {
  width: 100%;
  background-color: var(--primary-color);
  border: none;
  padding: 8px;
  font-size: 14px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  transition: background-color 0.3s;
}

.add-to-cart:hover {
  background-color: var(--accent-color);
}

/* Book Filter Sections */
.filter-content {
  display: none;
}

.filter-content.active {
  display: grid;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
  .left-content,
  .right-content {
    flex: 0 0 100%;
    max-width: 100%;
  }

  .promo-section {
    flex-direction: column;
  }

  .carousel-item img {
    height: 300px;
  }

  .left-content {
    order: 2;
    margin-top: 30px;
  }

  .right-content {
    order: 1;
  }
}

@media (max-width: 768px) {
  .carousel-item img {
    height: 200px;
  }

  .book-cover {
    height: 200px;
  }

  .book-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
  
  .carousel-wrapper {
    margin-left: 15px;
    margin-right: 15px;
  }
}

/* Header and Footer theming */
header {
  background-color: var(--header-bg);
  transition: background-color 0.3s;
}

footer {
  background-color: var(--footer-bg);
  transition: background-color 0.3s;
}

.nav-link {
  color: var(--dark-text);
  transition: color 0.3s;
}

.nav-link:hover {
  color: var(--primary-color);
}

/* Theme toggle button in header */
.theme-toggle-btn {
  background: transparent;
  border: none;
  color: var(--dark-text);
  cursor: pointer;
  transition: color 0.3s;
}

.theme-toggle-btn:hover {
  color: var(--primary-color);
}

.theme-toggle-btn i {
  transition: transform 0.3s;
}

.dark-mode .theme-toggle-btn i {
  transform: rotate(180deg);
}
     </style>
</head>

<body>
    <?php
    include 'header.php';
    // Get all books (limit 20)
    $all_books_query = "SELECT b.*, GROUP_CONCAT(DISTINCT w.name) as writers, 
                        GROUP_CONCAT(DISTINCT g.name) as genres, 
                        GROUP_CONCAT(DISTINCT c.name) as categories
                        FROM books b
                        LEFT JOIN book_writers bw ON b.book_id = bw.book_id
                        LEFT JOIN writers w ON bw.writer_id = w.writer_id
                        LEFT JOIN book_genres bg ON b.book_id = bg.book_id
                        LEFT JOIN genres g ON bg.genre_id = g.genre_id
                        LEFT JOIN book_categories bc ON b.book_id = bc.book_id
                        LEFT JOIN categories c ON bc.category_id = c.id
                        GROUP BY b.book_id
                        ORDER BY b.created_at DESC
                        LIMIT 20";
    $all_books_result = mysqli_query($conn, $all_books_query);

    // Get popular books (most ordered, limit 20)
    $popular_books_query = "SELECT b.*, COUNT(oi.book_id) as order_count, 
                           GROUP_CONCAT(DISTINCT w.name) as writers
                           FROM books b
                           LEFT JOIN order_items oi ON b.book_id = oi.book_id
                           LEFT JOIN book_writers bw ON b.book_id = bw.book_id
                           LEFT JOIN writers w ON bw.writer_id = w.writer_id
                           GROUP BY b.book_id
                           ORDER BY order_count DESC
                           LIMIT 20";
    $popular_books_result = mysqli_query($conn, $popular_books_query);
    // Get top rated books (limit 20)
    $top_rated_query = "SELECT b.*, GROUP_CONCAT(DISTINCT w.name) as writers
                       FROM books b
                       LEFT JOIN book_writers bw ON b.book_id = bw.book_id
                       LEFT JOIN writers w ON bw.writer_id = w.writer_id
                       WHERE b.rating IS NOT NULL
                       GROUP BY b.book_id
                       ORDER BY b.rating DESC
                       LIMIT 20";
    $top_rated_result = mysqli_query($conn, $top_rated_query);

    // Get recently added books (limit 20)
    $recent_books_query = "SELECT b.*, GROUP_CONCAT(DISTINCT w.name) as writers
                          FROM books b
                          LEFT JOIN book_writers bw ON b.book_id = bw.book_id
                          LEFT JOIN writers w ON bw.writer_id = w.writer_id
                          GROUP BY b.book_id
                          ORDER BY b.created_at DESC
                          LIMIT 20";
    $recent_books_result = mysqli_query($conn, $recent_books_query);

    // Get books on sale (quantity > 0, limit 20)
    $sale_books_query = "SELECT b.*, GROUP_CONCAT(DISTINCT w.name) as writers
                        FROM books b
                        LEFT JOIN book_writers bw ON b.book_id = bw.book_id
                        LEFT JOIN writers w ON bw.writer_id = w.writer_id
                        WHERE b.quantity > 0
                        GROUP BY b.book_id
                        ORDER BY b.created_at DESC
                        LIMIT 20";
    $sale_books_result = mysqli_query($conn, $sale_books_query);

    // Get writers (limit 15)
    $writers_query = "SELECT * FROM writers ORDER BY name LIMIT 15";
    $writers_result = mysqli_query($conn, $writers_query);

    // Get genres (limit 15)
    $genres_query = "SELECT * FROM genres ORDER BY name LIMIT 15";
    $genres_result = mysqli_query($conn, $genres_query);
    ?>

    <!-- Upper Section: Carousel -->
    <div class="header-section">
        <div class="carousel-wrapper">
            <div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="../BookHeaven/asset/rentposter.png" class="d-block w-100" alt="Book Rental Promotion">
                    </div>
                    <div class="carousel-item">
                        <img src="../BookHeaven/asset/Audiobookposter.png" class="d-block w-100"
                            alt="Audiobook Collection">
                    </div>
                    <div class="carousel-item">
                        <img src="../BookHeaven/asset/Beawritter.png" class="d-block w-100" alt="Become a Writer">
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Lower Section: Main Content -->
    <div class="content-container">
        <!-- Left Side (30% width) -->
        <div class="left-content">

            <!-- Writers Section -->
            <div class="writer-section">
                <h3 class="section-title">Popular Writers</h3>
                <ul class="writer-list" style="max-height: 400px; overflow-y: auto;">
                    <?php while ($writer = mysqli_fetch_assoc($writers_result)): ?>
                        <li class="writer-item">
                            <img src="<?php echo $writer['image_url'] ? $writer['image_url'] : 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&auto=format&fit=crop&w=100&q=80'; ?>"
                                alt="<?php echo htmlspecialchars($writer['name']); ?>" class="writer-img">
                            <div class="writer-info">
                                <a href="/BookHeaven2.0/php/writer_books.php?writer_id=<?php echo $writer['writer_id']; ?>"
                                    class="writer-name"><?php echo htmlspecialchars($writer['name']); ?></a>
                                <div class="writer-genre">
                                    <?php echo $writer['bio'] ? substr($writer['bio'], 0, 30) . '...' : 'Not specified'; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <!-- Filter Section -->
            <div class="filter-section">
                <h3 class="section-title">Filter Options</h3>
                <div class="filter-options">
                    <button class="filter-btn active" data-filter="all">All Books</button>
                    <button class="filter-btn" data-filter="popular">Popular</button>
                    <button class="filter-btn" data-filter="top-rated">Top Rated</button>
                    <button class="filter-btn" data-filter="recent">Recently Added</button>
                    <button class="filter-btn" data-filter="sale">On Sale</button>
                </div>
            </div>
            <!-- Genre Section -->
            <div class="genre-section">
                <h3 class="section-title">Browse Genres</h3>
                <ul class="genre-list" style="max-height: 300px; overflow-y: auto;">
                    <?php while ($genre = mysqli_fetch_assoc($genres_result)): ?>
                        <li><a href="/BookHeaven2.0/php/genre_books.php?genre_id=<?php echo $genre['genre_id']; ?>"><i
                                    class="fas fa-book"></i>
                                <?php echo htmlspecialchars($genre['name']); ?></a></li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>

        <!-- Right Side (66% width) -->
        <div class="right-content">
            <!-- Upper Right: Promo Cards -->
            <div class="promo-section">
                <div class="promo-card">
                    <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
                        alt="Subscription Plans">
                    <h3>Subscribe plans</h3>
                    <p>Get unlimited access to thousands of books with our subscription plans.</p>
                    <a href="/BookHeaven2.0/php/subscription_plan.php">
                        <button class="btn-promo">
                            <i class="fas fa-shopping-cart"></i> Subscribe now
                        </button>
                    </a>
                </div>

                <div class="promo-card">
                    <img src="https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80"
                        alt="Upcoming Events">
                    <h3>Upcoming Events</h3>
                    <p>Join our book clubs, author meetups, and literary festivals.</p>
                    <a href="/BookHeaven2.0/php/events.php">
                        <button class="btn-promo">
                            <i class="fas fa-calendar-alt"></i> View Events
                        </button>
                    </a>
                </div>
            </div>

            <!-- Books Section with Filter Content -->
            <div class="books-section">
                <h1 class="section-heading" style="text-decoration: underline;">All Books</h1>

                <!-- All Books (Default Active) -->
                <div class="filter-content active" id="all-books">
                    <div class="book-grid">
                        <?php while ($book = mysqli_fetch_assoc($all_books_result)): ?>
                            <div class="book-card">
                                <a href="/BookHeaven2.0/php/book_details.php?book_id=<?php echo $book['book_id']; ?>">
                                    <img src="<?php echo $book['cover_image_url'] ? $book['cover_image_url'] : 'https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; ?>"
                                        alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover">
                                </a>
                                <div class="book-info">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="book-author"><?php echo htmlspecialchars($book['writers']); ?></p>
                                    <div class="book-rating">
                                        <?php
                                        $rating = isset($book['rating']) ? $book['rating'] : 0;
                                        $full_stars = floor($rating);
                                        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                                        $empty_stars = 5 - $full_stars - $half_star;

                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }
                                        for ($i = 0; $i < $empty_stars; $i++) {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                        <span><?php echo number_format($rating, 1); ?></span>
                                    </div>
                                    <p class="book-price">৳<?php echo $book['price']; ?></p>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                        <button type="submit" name="add_to_cart" class="add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Popular Books -->
                <div class="filter-content" id="popular-books">
                    <div class="book-grid">
                        <?php while ($book = mysqli_fetch_assoc($popular_books_result)): ?>
                            <div class="book-card">
                                <a href="/BookHeaven2.0/php/book_details.php?book_id=<?php echo $book['book_id']; ?>">
                                    <img src="<?php echo $book['cover_image_url'] ? $book['cover_image_url'] : 'https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; ?>"
                                        alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover">
                                </a>
                                <div class="book-info">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="book-author"><?php echo htmlspecialchars($book['writers']); ?></p>
                                    <div class="book-rating">
                                        <?php
                                        $rating = isset($book['rating']) ? $book['rating'] : 0;
                                        $full_stars = floor($rating);
                                        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                                        $empty_stars = 5 - $full_stars - $half_star;

                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }
                                        for ($i = 0; $i < $empty_stars; $i++) {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                        <span><?php echo number_format($rating, 1); ?></span>
                                    </div>
                                    <p class="book-price">৳<?php echo $book['price']; ?></p>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                        <button type="submit" name="add_to_cart" class="add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Top Rated Books -->
                <div class="filter-content" id="top-rated-books">
                    <div class="book-grid">
                        <?php while ($book = mysqli_fetch_assoc($top_rated_result)): ?>
                            <div class="book-card">
                                <a href="/BookHeaven2.0/php/book_details.php?book_id=<?php echo $book['book_id']; ?>">
                                    <img src="<?php echo $book['cover_image_url'] ? $book['cover_image_url'] : 'https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; ?>"
                                        alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover">
                                </a>
                                <div class="book-info">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="book-author"><?php echo htmlspecialchars($book['writers']); ?></p>
                                    <div class="book-rating">
                                        <?php
                                        $rating = isset($book['rating']) ? $book['rating'] : 0;
                                        $full_stars = floor($rating);
                                        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                                        $empty_stars = 5 - $full_stars - $half_star;

                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }
                                        for ($i = 0; $i < $empty_stars; $i++) {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                        <span><?php echo number_format($rating, 1); ?></span>
                                    </div>
                                    <p class="book-price">৳<?php echo $book['price']; ?></p>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                        <button type="submit" name="add_to_cart" class="add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Recently Added Books -->
                <div class="filter-content" id="recent-books">
                    <div class="book-grid">
                        <?php while ($book = mysqli_fetch_assoc($recent_books_result)): ?>
                            <div class="book-card">
                                <a href="/BookHeaven2.0/php/book_details.php?book_id=<?php echo $book['book_id']; ?>">
                                    <img src="<?php echo $book['cover_image_url'] ? $book['cover_image_url'] : 'https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; ?>"
                                        alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover">
                                </a>
                                <div class="book-info">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="book-author"><?php echo htmlspecialchars($book['writers']); ?></p>
                                    <div class="book-rating">
                                        <?php
                                        $rating = isset($book['rating']) ? $book['rating'] : 0;
                                        $full_stars = floor($rating);
                                        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                                        $empty_stars = 5 - $full_stars - $half_star;

                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }
                                        for ($i = 0; $i < $empty_stars; $i++) {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                        <span><?php echo number_format($rating, 1); ?></span>
                                    </div>
                                    <p class="book-price">৳<?php echo $book['price']; ?></p>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                        <button type="submit" name="add_to_cart" class="add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- On Sale Books -->
                <div class="filter-content" id="sale-books">
                    <div class="book-grid">
                        <?php while ($book = mysqli_fetch_assoc($sale_books_result)): ?>
                            <div class="book-card">
                                <a href="/BookHeaven2.0/php/book_details.php?book_id=<?php echo $book['book_id']; ?>">
                                    <img src="<?php echo $book['cover_image_url'] ? $book['cover_image_url'] : 'https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; ?>"
                                        alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover">
                                </a>
                                <div class="book-info">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="book-author"><?php echo htmlspecialchars($book['writers']); ?></p>
                                    <div class="book-rating">
                                        <?php
                                        $rating = isset($book['rating']) ? $book['rating'] : 0;
                                        $full_stars = floor($rating);
                                        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                                        $empty_stars = 5 - $full_stars - $half_star;

                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($half_star) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }
                                        for ($i = 0; $i < $empty_stars; $i++) {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                        <span><?php echo number_format($rating, 1); ?></span>
                                    </div>
                                    <p class="book-price">৳<?php echo $book['price']; ?></p>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                        <button type="submit" name="add_to_cart" class="add-to-cart">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Filter Functionality -->
    <!-- Keep only the filter functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const sectionHeading = document.querySelector('.section-heading');
            const filterTitles = {
                'all': 'All Books',
                'popular': 'Popular Books',
                'top-rated': 'Top Rated Books',
                'recent': 'Recently Added Books',
                'sale': 'On Sale Books'
            };

            filterButtons.forEach(button => {
                button.addEventListener('click', function () {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));

                    // Add active class to clicked button
                    this.classList.add('active');

                    // Hide all filter content
                    document.querySelectorAll('.filter-content').forEach(content => {
                        content.classList.remove('active');
                    });

                    // Show the selected filter content and update heading
                    const filterType = this.getAttribute('data-filter');
                    document.getElementById(`${filterType}-books`).classList.add('active');
                    sectionHeading.textContent = filterTitles[filterType];
                });
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (isset($_SESSION['success_message'])): ?>
                alert('<?php echo $_SESSION['success_message']; ?>');
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                alert('<?php echo $_SESSION['error_message']; ?>');
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['info_message'])): ?>
                alert('<?php echo $_SESSION['info_message']; ?>');
                <?php unset($_SESSION['info_message']); ?>
            <?php endif; ?>
        });
        // new added code
        document.addEventListener('DOMContentLoaded', function() {
  const themeToggle = document.getElementById('themeToggle');
  const icon = themeToggle.querySelector('i');
  
  // Check for saved theme preference or use preferred color scheme
  const currentTheme = localStorage.getItem('theme') || 
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  
  // Apply the current theme
  if (currentTheme === 'dark') {
    document.body.classList.add('dark-mode');
    icon.classList.replace('fa-moon', 'fa-sun');
  }

});
    </script>
</body>

</html>