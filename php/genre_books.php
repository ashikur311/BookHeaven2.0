<?php
require_once '../db_connection.php';
session_start();

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
    <link rel="stylesheet" href="/BookHeaven2.0/css/genre_books.css">
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
