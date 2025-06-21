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

// Get all writers for the sidebar
$all_writers_query = "SELECT * FROM writers ORDER BY name";
$all_writers_result = mysqli_query($conn, $all_writers_query);

// Check if writer_id is set, otherwise use first writer as default
if (!isset($_GET['writer_id'])) {
    if (mysqli_num_rows($all_writers_result) > 0) {
        $first_writer = mysqli_fetch_assoc($all_writers_result);
        mysqli_data_seek($all_writers_result, 0); // Reset pointer
        header("Location: writer_books.php?writer_id=".$first_writer['writer_id']);
        exit;
    }
}

$writer_id = isset($_GET['writer_id']) ? (int)$_GET['writer_id'] : 0;

// Get writer details
$writer_query = "SELECT * FROM writers WHERE writer_id = $writer_id";
$writer_result = mysqli_query($conn, $writer_query);
$writer = mysqli_fetch_assoc($writer_result);

if (!$writer && mysqli_num_rows($all_writers_result) > 0) {
    $first_writer = mysqli_fetch_assoc($all_writers_result);
    mysqli_data_seek($all_writers_result, 0); // Reset pointer
    header("Location: writer_books.php?writer_id=".$first_writer['writer_id']);
    exit;
}

// Get books by this writer with rating information
$books_query = "SELECT b.*, 
                AVG(r.rating) as avg_rating
                FROM books b
                JOIN book_writers bw ON b.book_id = bw.book_id
                LEFT JOIN reviews r ON b.book_id = r.book_id
                WHERE bw.writer_id = $writer_id
                GROUP BY b.book_id
                ORDER BY b.title";
$books_result = mysqli_query($conn, $books_query) or die(mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books by <?php echo htmlspecialchars($writer['name'] ?? 'Writer'); ?> - BookHeaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/BookHeaven2.0/css/writer_books.css">
<body>
    <?php include_once("../header.php") ?>

    <!-- Writer Header Section -->
    <section class="writer-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="fas fa-feather-alt me-2"></i>Books by Writers</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <div class="container mb-5">
        <div class="writer-container">
            <div class="row g-0">
                <!-- Writer Sidebar -->
                <div class="col-lg-3 writer-sidebar">
                    <h3><i class="fas fa-users me-2"></i>Writers</h3>
                    <div class="writer-list">
                        <?php if (mysqli_num_rows($all_writers_result) > 0): ?>
                            <?php while($w = mysqli_fetch_assoc($all_writers_result)): ?>
                                <a href="writer_books.php?writer_id=<?php echo $w['writer_id']; ?>" 
                                   class="text-decoration-none">
                                    <div class="writer-item <?php echo ($w['writer_id'] == $writer_id) ? 'active' : ''; ?>">
                                        <span><?php echo htmlspecialchars($w['name']); ?></span>
                                        <span class="badge rounded-pill">
                                            <?php 
                                                // Count books by this writer
                                                $count_query = "SELECT COUNT(*) as count FROM book_writers WHERE writer_id = ".$w['writer_id'];
                                                $count_result = mysqli_query($conn, $count_query);
                                                $count = mysqli_fetch_assoc($count_result);
                                                echo $count['count'];
                                            ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">No writers found</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Writer Content -->
                <div class="col-lg-9 writer-content">
                    <?php if ($writer): ?>
                        <div class="writer-info">
                            <h3 class="writer-name"><?php echo htmlspecialchars($writer['name']); ?></h3>
                            <?php if ($writer['bio']): ?>
                                <p class="writer-bio"><?php echo htmlspecialchars($writer['bio']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <h2 class="writer-title">
                        <i class="fas fa-book me-2"></i>
                        <?php echo htmlspecialchars($writer['name'] ?? 'Select a Writer'); ?>'s Books
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
                                        
                                        <div class="book-rating">
                                            <div class="rating-stars">
                                                <?php echo $stars; ?>
                                            </div>
                                            <span class="rating-value"><?php echo $rating > 0 ? $rating : 'No ratings'; ?></span>
                                        </div>
                                        
                                        <p class="book-price">৳<?php echo number_format($book['price'], 2); ?></p>
                                        <button class="btn btn-add-to-cart btn-success <?php echo $in_cart ? 'disabled' : ''; ?>" 
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
                            <h4>No books found by this writer</h4>
                            <p class="text-muted">Check back later or browse other writers</p>
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