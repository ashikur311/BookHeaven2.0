<?php
require_once 'db_connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Get current user ID if logged in
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
    <link rel="stylesheet" href="/BookHeaven2.0/css/home.css">
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
    </script>
</body>

</html>