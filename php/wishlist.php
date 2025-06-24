<?php
// Start session and include database connection
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authentication.php");
  exit();
}
require_once('../db_connection.php');



$user_id = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => '', 'already_in_cart' => false];
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    
    try {
        if ($_POST['action'] === 'remove_from_wishlist') {
            // Remove from wishlist
            $delete_query = "DELETE FROM wishlist WHERE user_id = ? AND book_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("ii", $user_id, $book_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Book removed from wishlist successfully';
            } else {
                $response['message'] = 'Error removing book from wishlist';
            }
        } 
        elseif ($_POST['action'] === 'check_cart') {
            // Check if book is already in cart
            $check_query = "SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $response['success'] = true;
            $response['already_in_cart'] = $result->num_rows > 0;
        }
        elseif ($_POST['action'] === 'add_to_cart') {
            // First check if book is already in cart
            $check_query = "SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Book is already in cart
                $response['success'] = false;
                $response['message'] = 'This book is already in your cart';
                $response['already_in_cart'] = true;
            } else {
                // Add to cart and remove from wishlist
                $conn->begin_transaction();
                
                // Insert new item
                $insert_query = "INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, 1)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("ii", $user_id, $book_id);
                $stmt->execute();
                
                // Remove from wishlist
                $delete_query = "DELETE FROM wishlist WHERE user_id = ? AND book_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("ii", $user_id, $book_id);
                $stmt->execute();
                
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Book added to cart and removed from wishlist';
            }
        }
    } catch (Exception $e) {
        if (isset($conn) && $conn->rollback) {
            $conn->rollback();
        }
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Fetch user data for sidebar
$user_query = "SELECT u.*, ui.* FROM users u 
               LEFT JOIN user_info ui ON u.user_id = ui.user_id 
               WHERE u.user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Fetch wishlist items
$wishlist_query = "SELECT b.*, w.added_at 
                   FROM wishlist w
                   JOIN books b ON w.book_id = b.book_id
                   WHERE w.user_id = ?
                   ORDER BY w.added_at DESC";
$stmt = $conn->prepare($wishlist_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_result = $stmt->get_result();
$wishlist_count = $wishlist_result->num_rows;

// Fetch writers for each book
function getBookWriters($conn, $book_id) {
    $writer_query = "SELECT w.name 
                     FROM book_writers bw
                     JOIN writers w ON bw.writer_id = w.writer_id
                     WHERE bw.book_id = ?";
    $stmt = $conn->prepare($writer_query);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $writers = [];
    while ($row = $result->fetch_assoc()) {
        $writers[] = $row['name'];
    }
    
    return implode(", ", $writers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/user_wishlist.css">
    <style>
        .book-card {
            transition: all 0.3s ease;
        }
        .book-card.removing {
            transform: scale(0.9);
            opacity: 0;
        }
        .btn {
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .added-to-cart {
            background-color: #28a745 !important;
            cursor: default;
        }
        .added-to-cart:hover {
            background-color: #28a745 !important;
        }
        .empty-wishlist {
            text-align: center;
            padding: 40px;
            width: 100%;
            grid-column: 1 / -1;
        }
        .empty-wishlist i {
            font-size: 50px;
            color: #ccc;
            margin-bottom: 20px;
        }
        .empty-wishlist p {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include_once("../header.php"); ?>

    <main>
        <aside>
            <section class="user-info">
                <img src="/BookHeaven2.0/<?php echo htmlspecialchars($user_data['user_profile'] ?? 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                            alt="<?php echo htmlspecialchars($user_data['username']); ?>" class="user-avatar">
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user_data['username']); ?></div>
                    <small>Member since: <?php echo date('M Y', strtotime($user_data['create_time'])); ?></small>
                </div>
            </section>
            <section>
                <nav>
                    <ul>
                        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="wishlist.php" class="active"><i class="fas fa-heart"></i> Wish List</a></li>
                        <li><a href="user_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                        <li><a href="user_subscription.php"><i class="fas fa-calendar-check"></i> My Subscription</a></li>
                        <li><a href="user_setting.php"><i class="fas fa-cog"></i> Setting</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </section>
        </aside>
        <div class="wishlist_content">
            <div class="wishlist-header">
                <h2>My Wishlist (<span id="wishlist-count"><?php echo $wishlist_count; ?></span> items)</h2>
            </div>
            
            <div class="books-grid">
                <?php if ($wishlist_count > 0): ?>
                    <?php while ($book = $wishlist_result->fetch_assoc()): ?>
                        <div class="book-card" id="book-<?php echo $book['book_id']; ?>">
                            <img src="/BookHeaven2.0/<?php echo htmlspecialchars($book['cover_image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                 class="book-image">
                            <div class="book-details">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author"><?php echo htmlspecialchars(getBookWriters($conn, $book['book_id'])); ?></p>
                                <div class="book-rating">
                                    <div class="stars">
                                        <?php 
                                        $rating = isset($book['rating']) ? $book['rating'] : 0;
                                        $fullStars = floor($rating);
                                        $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                                        $emptyStars = 5 - $fullStars - $halfStar;
                                        
                                        echo str_repeat('★', $fullStars);
                                        echo $halfStar ? '½' : '';
                                        echo str_repeat('☆', $emptyStars);
                                        ?>
                                    </div>
                                    <span>(<?php echo number_format($rating, 1); ?>)</span>
                                </div>
                                <div class="book-price">৳<?php echo number_format($book['price'], 2); ?></div>
                                <div class="book-actions">
                                    <button type="button" class="btn btn-primary add-to-cart-btn" 
                                            data-book-id="<?php echo $book['book_id']; ?>">Add to Cart</button>
                                    <button type="button" class="btn btn-outline remove-from-wishlist-btn" 
                                            data-book-id="<?php echo $book['book_id']; ?>">Remove</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-wishlist">
                        <i class="fas fa-heart-broken"></i>
                        <p>Your wishlist is empty</p>
                        <a href="/BookHeaven2.0/index.php" class="btn btn-primary">Browse Books</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include_once("../footer.php");?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to update wishlist count
            function updateWishlistCount() {
                const count = document.querySelectorAll('.book-card:not(.removing)').length;
                document.getElementById('wishlist-count').textContent = count;
                
                if (count === 0) {
                    const emptyState = `
                        <div class="empty-wishlist">
                            <i class="fas fa-heart-broken"></i>
                            <p>Your wishlist is empty</p>
                            <a href="/BookHeaven2.0/index.php" class="btn btn-primary">Browse Books</a>
                        </div>
                    `;
                    document.querySelector('.books-grid').innerHTML = emptyState;
                }
            }
            
            // Function to check if book is in cart and update button
            function checkCartStatus(bookId, button) {
                fetch('wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=check_cart&book_id=${bookId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.already_in_cart) {
                        button.textContent = 'Added to Cart';
                        button.classList.add('added-to-cart');
                        button.title = 'Click to see cart';
                    }
                })
                .catch(error => {
                    console.error('Error checking cart:', error);
                });
            }
            
            // Check cart status for all books on page load
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                const bookId = button.getAttribute('data-book-id');
                checkCartStatus(bookId, button);
            });
            
            // Remove from wishlist functionality
            document.querySelectorAll('.remove-from-wishlist-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const bookId = this.getAttribute('data-book-id');
                    const bookCard = document.getElementById(`book-${bookId}`);
                    const originalText = this.textContent;
                    
                    // Visual feedback
                    this.disabled = true;
                    this.textContent = 'Removing...';
                    bookCard.classList.add('removing');
                    
                    fetch('wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=remove_from_wishlist&book_id=${bookId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove book card after animation completes
                            setTimeout(() => {
                                bookCard.remove();
                                updateWishlistCount();
                                alert(data.message);
                            }, 300);
                        } else {
                            // Revert changes if error
                            bookCard.classList.remove('removing');
                            this.disabled = false;
                            this.textContent = originalText;
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        bookCard.classList.remove('removing');
                        this.disabled = false;
                        this.textContent = originalText;
                        alert('Error removing book from wishlist');
                    });
                });
            });
            
            // Add to cart functionality
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const bookId = this.getAttribute('data-book-id');
                    
                    // If button is in "Added to Cart" state, just show message
                    if (this.classList.contains('added-to-cart')) {
                        alert('This book is already in your cart');
                        return;
                    }
                    
                    const bookCard = document.getElementById(`book-${bookId}`);
                    const originalText = this.textContent;
                    
                    // Visual feedback
                    this.disabled = true;
                    this.textContent = 'Adding...';
                    bookCard.classList.add('removing');
                    
                    fetch('wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=add_to_cart&book_id=${bookId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove book card after animation completes
                            setTimeout(() => {
                                bookCard.remove();
                                updateWishlistCount();
                                alert(data.message);
                            }, 300);
                        } else {
                            // Revert changes if error
                            bookCard.classList.remove('removing');
                            this.disabled = false;
                            this.textContent = originalText;
                            
                            // If book is already in cart, update button
                            if (data.already_in_cart) {
                                this.textContent = 'Added to Cart';
                                this.classList.add('added-to-cart');
                                this.title = 'Click to see cart';
                            }
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        bookCard.classList.remove('removing');
                        this.disabled = false;
                        this.textContent = originalText;
                        alert('Error adding book to cart');
                    });
                });
            });
        });
    </script>
</body>
</html>