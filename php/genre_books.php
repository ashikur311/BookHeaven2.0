<?php
require_once '../db_connection.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authentication.php");
  exit();
}

// Handle adding to cart
if (isset($_POST['action']) && $_POST['action'] == 'add' && isset($_POST['book_id'])) {
    $user_id = $_SESSION['user_id'];
    $book_id = $_POST['book_id'];

    // Function to add book to the cart
    function addToCart($user_id, $book_id, $conn) {
        // Check if the book is already in the cart
        $check_cart_query = "SELECT * FROM cart WHERE user_id = $user_id AND book_id = $book_id";
        $check_result = mysqli_query($conn, $check_cart_query);

        if (mysqli_num_rows($check_result) > 0) {
            return json_encode(['success' => false, 'message' => 'Book is already in your cart']);
        }

        // Add the book to the cart
        $add_to_cart_query = "INSERT INTO cart (user_id, book_id) VALUES ($user_id, $book_id)";
        if (mysqli_query($conn, $add_to_cart_query)) {
            // Return the updated cart count
            $cart_count_query = "SELECT COUNT(*) as cart_count FROM cart WHERE user_id = $user_id";
            $cart_count_result = mysqli_query($conn, $cart_count_query);
            $cart_count = mysqli_fetch_assoc($cart_count_result)['cart_count'];

            return json_encode(['success' => true, 'cart_count' => $cart_count]);
        } else {
            return json_encode(['success' => false, 'message' => 'Error adding book to cart']);
        }
    }

    // Call the function to add the book to the cart and return the result
    echo addToCart($user_id, $_POST['book_id'], $conn);
    exit;
}

// Get all genres for the sidebar
$all_genres_query = "SELECT * FROM genres ORDER BY name";
$all_genres_result = mysqli_query($conn, $all_genres_query);

// Check if genre_id is set, otherwise use first genre as default
if (!isset($_GET['genre_id'])) {
    if (mysqli_num_rows($all_genres_result) > 0) {
        $first_genre = mysqli_fetch_assoc($all_genres_result);
        mysqli_data_seek($all_genres_result, 0); // Reset pointer
        header("Location: genre_books.php?genre_id=".$first_genre['genre_id']);
        exit;
    }
}

$genre_id = isset($_GET['genre_id']) ? (int)$_GET['genre_id'] : 0;

// Get genre details
$genre_query = "SELECT * FROM genres WHERE genre_id = $genre_id";
$genre_result = mysqli_query($conn, $genre_query);
$genre = mysqli_fetch_assoc($genre_result);

if (!$genre && mysqli_num_rows($all_genres_result) > 0) {
    $first_genre = mysqli_fetch_assoc($all_genres_result);
    mysqli_data_seek($all_genres_result, 0); // Reset pointer
    header("Location: genre_books.php?genre_id=".$first_genre['genre_id']);
    exit;
}

// Get books in this genre with writer and rating information
$books_query = "SELECT b.*, 
                GROUP_CONCAT(DISTINCT w.name SEPARATOR ', ') as writers,
                AVG(r.rating) as avg_rating
                FROM books b
                JOIN book_genres bg ON b.book_id = bg.book_id
                LEFT JOIN book_writers bw ON b.book_id = bw.book_id
                LEFT JOIN writers w ON bw.writer_id = w.writer_id
                LEFT JOIN reviews r ON b.book_id = r.book_id
                WHERE bg.genre_id = $genre_id
                GROUP BY b.book_id
                ORDER BY b.title";
$books_result = mysqli_query($conn, $books_query) or die(mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books in <?php echo htmlspecialchars($genre['name'] ?? 'Genre'); ?> - BookHeaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- <link rel="stylesheet" href="/BookHeaven2.0/css/genre_books.css"> -->
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
  --footer-bg: #343a40;
  --footer-text: #f8f9fa;
  --badge-bg: #6c757d;
  --badge-text: #ffffff;
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
  --footer-bg: #1a202c;
  --badge-bg: #4a5568;
  --badge-text: #f8f9fa;
}

body {
  font-family: "Nunito", sans-serif;
  color: var(--dark-text);
  background-color: var(--secondary-color);
  transition: background-color 0.3s, color 0.3s;
}

.genre-header {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  color: var(--light-text);
  padding: 2rem 0;
  margin-bottom: 2rem;
  border-radius: 0 0 10px 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: background 0.3s;
}

.genre-container {
  background: var(--card-bg);
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  margin-bottom: 3rem;
  transition: background-color 0.3s;
}

.genre-sidebar {
  background: var(--aside-bg);
  padding: 1.5rem;
  height: 100%;
  border-right: 1px solid var(--border-color);
  transition: background-color 0.3s, border-color 0.3s;
}

.genre-sidebar h3 {
  color: var(--primary-color);
  padding-bottom: 1rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid var(--border-color);
  font-weight: 700;
  transition: color 0.3s, border-color 0.3s;
}

.genre-list {
  max-height: 500px;
  overflow-y: auto;
  padding-right: 10px;
}

/* Updated block-style genre items */
.genre-item {
  display: block;
  padding: 0.75rem 1rem;
  margin-bottom: 0.5rem;
  border-radius: 5px;
  transition: all 0.3s ease;
  cursor: pointer;
  color: var(--dark-text);
  background-color: var(--card-bg);
  border: 1px solid var(--border-color);
  transition: background-color 0.3s, color 0.3s, border-color 0.3s;
}

.genre-item > span {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Fix for book count visibility */
.genre-item .badge {
  background-color: var(--badge-bg);
  color: var(--badge-text) !important; /* Force text color */
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  transition: background-color 0.3s;
}

.genre-item:hover {
  background-color: var(--light-purple);
}

.genre-item.active {
  background-color: var(--primary-color);
  color: var(--light-text);
  border-color: var(--primary-color);
}

.genre-item.active .badge {
  background-color: var(--light-text);
  color: var(--primary-color) !important;
}

.genre-content {
  padding: 2rem;
  background-color: var(--card-bg);
  transition: background-color 0.3s;
}

.genre-title {
  color: var(--primary-color);
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--border-color);
  font-weight: 700;
  transition: color 0.3s, border-color 0.3s;
}

/* Fix for book title visibility in dark mode */
.book-title {
  font-weight: 700;
  color: var(--dark-text) !important; /* Force dark text */
  margin-bottom: 0.5rem;
  height: 60px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.book-title a {
  color: inherit !important; /* Force inheritance */
  text-decoration: none;
}

.book-title a:hover {
  color: var(--primary-color) !important;
}

.book-card {
  border: none;
  border-radius: 10px;
  overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  height: 100%;
  background: var(--card-bg);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  border: 1px solid var(--border-color);
  transition: background-color 0.3s, border-color 0.3s;
}

.book-card-img-container {
  position: relative;
  overflow: hidden;
  height: 250px;
}

.book-card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.book-card:hover .book-card-img {
  transform: scale(1.05);
}

.book-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.book-card-body {
  padding: 1.5rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.book-writer {
  color: var(--text-color);
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
  font-weight: 600;
  transition: color 0.3s;
}

.book-writer i {
  color: var(--primary-color);
}

.book-rating {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}

.rating-stars {
  color: #ffc107;
  margin-right: 0.5rem;
}

.rating-value {
  font-weight: 600;
  color: var(--dark-text);
  transition: color 0.3s;
}

.book-price {
  font-weight: 700;
  color: var(--primary-color);
  font-size: 1.2rem;
  margin-bottom: 1.25rem;
  transition: color 0.3s;
}

.btn-add-to-cart {
  background-color: var(--primary-color);
  border: none;
  width: 100%;
  transition: all 0.3s ease;
  margin-top: auto;
  font-weight: 600;
  padding: 0.5rem;
  color: var(--light-text) !important;
}

.btn-add-to-cart:hover {
  background-color: var(--accent-color);
  transform: translateY(-2px);
}

.btn-add-to-cart.disabled {
  background-color: var(--accent-color);
  opacity: 0.8;
}

.no-books {
  text-align: center;
  padding: 3rem;
  background-color: var(--aside-bg);
  border-radius: 10px;
  color: var(--dark-text);
  transition: background-color 0.3s, color 0.3s;
}

/* Responsive adjustments */
@media (max-width: 1199.98px) {
  .book-card-img-container {
    height: 220px;
  }
}

@media (max-width: 991.98px) {
  .book-card-img-container {
    height: 200px;
  }

  .book-title {
    height: 54px;
  }
}

@media (max-width: 767.98px) {
  .genre-sidebar {
    border-right: none;
    border-bottom: 1px solid var(--border-color);
  }

  .genre-list {
    max-height: 200px;
    margin-bottom: 2rem;
  }

  .book-card-img-container {
    height: 180px;
  }
}

@media (max-width: 575.98px) {
  .genre-header h1 {
    font-size: 1.8rem;
  }

  .genre-content {
    padding: 1rem;
  }

  .book-card-img-container {
    height: 160px;
  }

  .book-title {
    font-size: 1rem;
    height: 48px;
  }

  .book-writer {
    font-size: 0.8rem;
  }

  .book-price {
    font-size: 1.1rem;
  }

  .book-card-body {
    padding: 1rem;
  }
}

@media (max-width: 400px) {
  .book-card-img-container {
    height: 140px;
  }

  .genre-header {
    padding: 1.5rem 0;
  }

  .genre-sidebar {
    padding: 1rem;
  }
}
</style>
</head>
<body>
    <?php include_once("../header.php") ?>

    <!-- Genre Header Section -->
    <section class="genre-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-book-open me-2"></i>Books by Genre</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <div class="container mb-5">
        <div class="genre-container">
            <div class="row g-0">
                <!-- Genre Sidebar -->
                <div class="col-lg-3 genre-sidebar">
                    <h3><i class="fas fa-list me-2"></i>Genres</h3>
                    <div class="genre-list">
                        <?php if (mysqli_num_rows($all_genres_result) > 0): ?>
                            <?php while($g = mysqli_fetch_assoc($all_genres_result)): ?>
                                <a href="genre_books.php?genre_id=<?php echo $g['genre_id']; ?>" 
                                   class="text-decoration-none">
                                    <div class="genre-item <?php echo ($g['genre_id'] == $genre_id) ? 'active' : ''; ?>">
                                        <span><?php echo htmlspecialchars($g['name']); ?></span>
                                        <span class="badge rounded-pill">
                                            <?php 
                                                // Count books in this genre
                                                $count_query = "SELECT COUNT(*) as count FROM book_genres WHERE genre_id = ".$g['genre_id'];
                                                $count_result = mysqli_query($conn, $count_query);
                                                $count = mysqli_fetch_assoc($count_result);
                                                echo $count['count'];
                                            ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No genres found</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Genre Content -->
                <div class="col-lg-9 genre-content">
                    <h2 class="genre-title">
                        <i class="fas fa-tag me-2"></i>
                        <?php echo htmlspecialchars($genre['name'] ?? 'Select a Genre'); ?>
                    </h2>

                    <?php if (mysqli_num_rows($books_result) > 0): ?>
                        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                            <?php while($book = mysqli_fetch_assoc($books_result)): 
                                // Check if book is already in cart
                                $in_cart = false;
                                if (isset($_SESSION['user_id'])) {
                                    $user_id = $_SESSION['user_id'];
                                    $cart_query = "SELECT * FROM cart WHERE user_id = $user_id AND book_id = ".$book['book_id'];
                                    $cart_result = mysqli_query($conn, $cart_query);
                                    $in_cart = mysqli_num_rows($cart_result) > 0;
                                }
                                
                                // Format rating
                                $rating = $book['avg_rating'] ? round($book['avg_rating'], 1) : 0;
                                $stars = str_repeat('<i class="fas fa-star"></i>', floor($rating));
                                if ($rating - floor($rating) >= 0.5) {
                                    $stars .= '<i class="fas fa-star-half-alt"></i>';
                                }
                                $stars .= str_repeat('<i class="far fa-star"></i>', 5 - ceil($rating));
                            ?>
                            <div class="col">
                                <div class="book-card card h-100">
                                    <div class="book-card-img-container">
                                        <a href="book_details.php?book_id=<?php echo $book['book_id']; ?>">
                                            <img src="/BookHeaven2.0/<?php echo htmlspecialchars($book['cover_image_url']); ?>" 
                                                 class="book-card-img" 
                                                 alt="<?php echo htmlspecialchars($book['title']); ?>">
                                        </a>
                                    </div>
                                    <div class="book-card-body">
                                        <h5 class="book-title">
                                            <a href="/BookHeaven2.0/php/book_details.php?book_id=<?php echo $book['book_id']; ?>" 
                                               class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($book['title']); ?>
                                            </a>
                                        </h5>
                                        <p class="book-writer">
                                            <i class="fas fa-user-edit me-1"></i>
                                            <?php echo htmlspecialchars($book['writers'] ?? 'Unknown Writer'); ?>
                                        </p>
                                        
                                        <div class="book-rating">
                                            <div class="rating-stars">
                                                <?php echo $stars; ?>
                                            </div>
                                            <span class="rating-value"><?php echo $rating > 0 ? $rating : 'No ratings'; ?></span>
                                        </div>
                                        
                                        <p class="book-price">৳<?php echo number_format($book['price'], 2); ?></p>
                                        <button class="btn btn-add-to-cart btn-primary <?php echo $in_cart ? 'disabled' : ''; ?>" 
                                                data-book-id="<?php echo $book['book_id']; ?>">
                                            <i class="fas <?php echo $in_cart ? 'fa-check' : 'fa-cart-plus'; ?> me-2"></i>
                                            <?php echo $in_cart ? 'Added to Cart' : 'Add to Cart'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-books">
                            <i class="fas fa-book-open fa-3x mb-3 text-muted"></i>
                            <h4>No books found in this genre</h4>
                            <p class="text-muted">Check back later or browse other genres</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include_once ("../footer.php"); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Add to cart functionality
            $('.btn-add-to-cart').click(function() {
                const button = $(this);
                const bookId = button.data('book-id');
                
                if (button.hasClass('disabled')) {
                    alert('This book is already in your cart');
                    return;
                }
                
                $.ajax({
                    url: '', // Same file
                    type: 'POST',
                    data: {
                        book_id: bookId,
                        action: 'add'
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Update button state
                            button.addClass('disabled');
                            button.html('<i class="fas fa-check me-2"></i>Added to Cart');
                            
                            // Update cart count
                            if (result.cart_count) {
                                $('.cart-count').text(result.cart_count);
                            }
                            
                            // Show success message
                            alert('Book added to your cart');
                        } else {
                            alert('Error: ' + result.message);
                        }
                    },
                    error: function() {
                        alert('Error: Could not add book to cart. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>
