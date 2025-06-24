<?php
// Start session and include database connection
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once('../db_connection.php');

// Get book ID from URL parameter
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

// Fetch book details

$book = [];
$writers = [];
$genres = [];
$categories = [];
$languages = [];
$reviews = [];
$questions = [];
$related_books = [];

if ($book_id > 0) {
    // Fetch book information
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    if ($book) {
        // Fetch writers
        $stmt = $conn->prepare("
            SELECT w.* FROM writers w
            JOIN book_writers bw ON w.writer_id = bw.writer_id
            WHERE bw.book_id = ?
        ");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $writers = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch genres
        $stmt = $conn->prepare("
            SELECT g.* FROM genres g
            JOIN book_genres bg ON g.genre_id = bg.genre_id
            WHERE bg.book_id = ?
        ");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $genres = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch categories
        $stmt = $conn->prepare("
            SELECT c.* FROM categories c
            JOIN book_categories bc ON c.id = bc.category_id
            WHERE bc.book_id = ?
        ");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch languages
        $stmt = $conn->prepare("
            SELECT l.* FROM languages l
            JOIN book_languages bl ON l.language_id = bl.language_id
            WHERE bl.book_id = ?
        ");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $languages = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch reviews
        $stmt = $conn->prepare("
            SELECT r.*, u.username, ui.userimageurl 
            FROM reviews r
            JOIN users u ON r.user_id = u.user_id
            LEFT JOIN user_info ui ON u.user_id = ui.user_id
            WHERE r.book_id = ?
            ORDER BY r.created_at DESC
            LIMIT 8
        ");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch questions and answers
        $stmt = $conn->prepare("
           SELECT q.*, u.username as questioner_name, ui.userimageurl as questioner_image,
       a.answer_text, a.created_at as answer_date,
       au.username as answerer_name, aui.userimageurl as answerer_image
        FROM questions q
    JOIN users u ON q.user_id = u.user_id
    LEFT JOIN user_info ui ON u.user_id = ui.user_id
    LEFT JOIN answers a ON q.question_id = a.question_id
    LEFT JOIN admin au ON a.admin_id = au.admin_id  -- Use admin_id instead of user_id for answers
    LEFT JOIN user_info aui ON au.admin_id = aui.user_id  -- Assuming admin_info is stored in user_info with admin_id as user_id
    WHERE q.book_id = ?
    ORDER BY q.created_at DESC
    LIMIT 2

        ");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $questions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch related books (same genre)
        if (!empty($genres)) {
            $genre_ids = array_column($genres, 'genre_id');
            $placeholders = implode(',', array_fill(0, count($genre_ids), '?'));

            $stmt = $conn->prepare("
                SELECT b.* FROM books b
                JOIN book_genres bg ON b.book_id = bg.book_id
                WHERE bg.genre_id IN ($placeholders)
                AND b.book_id != ?
                GROUP BY b.book_id
                ORDER BY RAND()
                LIMIT 4
            ");

            $types = str_repeat('i', count($genre_ids)) . 'i';
            $params = array_merge($genre_ids, [$book_id]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $related_books = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        // Add to cart functionality
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

            // Check if book already in cart
            $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND book_id = ?");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Book already in cart, do nothing (no update to quantity)
                $_SESSION['message'] = "This book is already in your cart.";
            } else {
                // Insert new item into cart
                $stmt = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $user_id, $book_id, $quantity);
                $stmt->execute();
                $_SESSION['message'] = "Book added to cart successfully!";
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Please login to add items to cart.";
        }
        header("Location: book_details.php?book_id=$book_id");
        exit();
    }


    if (isset($_POST['add_to_wishlist'])) {
        // Add to wishlist functionality
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];

            // Check if already in wishlist
            $stmt = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND book_id = ?");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $user_id, $book_id);
                $stmt->execute();
                $stmt->close();

                $_SESSION['message'] = "Book added to wishlist successfully!";
            } else {
                $_SESSION['message'] = "Book is already in your wishlist.";
            }
        } else {
            $_SESSION['message'] = "Please login to add items to wishlist.";
        }
        header("Location: book_details.php?book_id=$book_id");
        exit();
    }

    if (isset($_POST['submit_review'])) {
        // Submit review functionality
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $rating = intval($_POST['rating']);
            $review_text = $conn->real_escape_string($_POST['review_text']);

            // Check if user already reviewed this book
            $stmt = $conn->prepare("SELECT * FROM reviews WHERE user_id = ? AND book_id = ?");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO reviews (user_id, book_id, review_text, rating) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisi", $user_id, $book_id, $review_text, $rating);
                $stmt->execute();
                $stmt->close();

                // Update book average rating
                $stmt = $conn->prepare("
                    UPDATE books 
                    SET rating = (SELECT AVG(rating) FROM reviews WHERE book_id = ?)
                    WHERE book_id = ?
                ");
                $stmt->bind_param("ii", $book_id, $book_id);
                $stmt->execute();
                $stmt->close();

                $_SESSION['message'] = "Thank you for your review!";
            } else {
                $_SESSION['message'] = "You have already reviewed this book.";
            }
        } else {
            $_SESSION['message'] = "Please login to submit a review.";
        }
        header("Location: book_details.php?book_id=$book_id");
        exit();
    }

    if (isset($_POST['submit_question'])) {
        // Submit question functionality
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $question_text = $conn->real_escape_string($_POST['question_text']);

            $stmt = $conn->prepare("INSERT INTO questions (user_id, book_id, question_text) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $book_id, $question_text);
            $stmt->execute();
            $stmt->close();

            $_SESSION['message'] = "Your question has been submitted!";
        } else {
            $_SESSION['message'] = "Please login to ask a question.";
        }
        header("Location: book_details.php?book_id=$book_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title'] ?? 'Book Details'); ?> | BookHeaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/book_details.css">
</head>
<body>
    <?php if (isset($_SESSION['message'])): ?>
        <script type="text/javascript">
            alert("<?php echo htmlspecialchars($_SESSION['message']); ?>");
        </script>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    <?php include_once("../header.php"); ?>
    <main>
        <?php if (!empty($book)): ?>
            <div class="book-container">
                <div class="left-section">
                    <!-- Book Header Section -->
                    <div class="book-header">
                        <img src="/BookHeaven2.0/<?php echo htmlspecialchars($book['cover_image_url'] ?? 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                            alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-image">
                        <div class="book-info">
                            <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                            <p class="book-author">By <?php
                            $writer_names = array_map(function ($writer) {
                                return htmlspecialchars($writer['name']);
                            }, $writers);
                            echo implode(', ', $writer_names);
                            ?></p>
                            <div class="book-rating">
                                <?php
                                $rating = $book['rating'] ?? 0;
                                $full_stars = floor($rating);
                                $has_half_star = ($rating - $full_stars) >= 0.5;

                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $full_stars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == $full_stars + 1 && $has_half_star) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span><?php echo number_format($rating, 1); ?> (<?php echo count($reviews); ?>
                                    reviews)</span>
                            </div>
                            <p class="book-price">৳<?php echo number_format($book['price'], 2); ?></p>
                            <p class="book-description">
                                <?php echo htmlspecialchars($book['details'] ?? 'No description available.'); ?>
                            </p>
                            <div class="book-meta">
                                <p><strong>Published:</strong> <?php echo date('F j, Y', strtotime($book['published'])); ?>
                                </p>
                                <p><strong>Genre:</strong> <?php
                                $genre_names = array_map(function ($genre) {
                                    return htmlspecialchars($genre['name']);
                                }, $genres);
                                echo implode(', ', $genre_names);
                                ?></p>
                                <p><strong>Category:</strong> <?php
                                $category_names = array_map(function ($category) {
                                    return htmlspecialchars($category['name']);
                                }, $categories);
                                echo implode(', ', $category_names);
                                ?></p>
                                <p><strong>Language:</strong> <?php
                                $language_names = array_map(function ($language) {
                                    return htmlspecialchars($language['name']);
                                }, $languages);
                                echo implode(', ', $language_names);
                                ?></p>
                            </div>
                            <div class="button-group">
                                <form method="post" action="">
                                    <input type="hidden" name="add_to_cart" value="1">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                                <form method="post" action="">
                                    <input type="hidden" name="add_to_wishlist" value="1">
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-heart"></i> Add to Wishlist
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Author Section -->
                    <?php if (!empty($writers)): ?>
                        <div class="author-section">
                            <?php $main_writer = $writers[0]; ?>
                            <img src="/BookHeaven2.0/<?php echo htmlspecialchars($main_writer['image_url'] ?? 'https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                                alt="<?php echo htmlspecialchars($main_writer['name']); ?>" class="author-image">
                            <div class="author-info">
                                <h2 class="author-name"><?php echo htmlspecialchars($main_writer['name']); ?></h2>
                                <p class="author-bio">
                                    <?php echo htmlspecialchars($main_writer['bio'] ?? 'No biography available.'); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Related Books Section -->
                    <?php if (!empty($related_books)): ?>
                        <div class="related-books">
                            <h2 class="section-title">You May Also Like</h2>
                            <div class="books-grid">
                                <?php foreach ($related_books as $related_book):
                                    // Get writer for related book
                                    $stmt = $conn->prepare("
                                SELECT w.name FROM writers w
                                JOIN book_writers bw ON w.writer_id = bw.writer_id
                                WHERE bw.book_id = ?
                                LIMIT 1
                            ");
                                    $stmt->bind_param("i", $related_book['book_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $related_writer = $result->fetch_assoc();
                                    $stmt->close();
                                    ?>
                                    <div class="book-card">
                                        <a href="book_details.php?book_id=<?php echo $related_book['book_id']; ?>">
                                            <img src="/BookHeaven2.0/<?php echo htmlspecialchars($related_book['cover_image_url'] ?? 'https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'); ?>"
                                                alt="<?php echo htmlspecialchars($related_book['title']); ?>" class="book-cover">
                                        </a>
                                        <div class="book-info-small">
                                            <h3 class="book-title-small">
                                                <a href="book_details.php?id=<?php echo $related_book['book_id']; ?>">
                                                    <?php echo htmlspecialchars($related_book['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="book-author-small">
                                                <?php echo htmlspecialchars($related_writer['name'] ?? 'Unknown'); ?>
                                            </p>
                                            <div class="book-rating-small">
                                                <?php
                                                $related_rating = $related_book['rating'] ?? 0;
                                                $related_full_stars = floor($related_rating);
                                                $related_has_half_star = ($related_rating - $related_full_stars) >= 0.5;

                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $related_full_stars) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } elseif ($i == $related_full_stars + 1 && $related_has_half_star) {
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                                <span><?php echo number_format($related_rating, 1); ?></span>
                                            </div>
                                            <p class="book-price-small">৳<?php echo number_format($related_book['price'], 2); ?></p>
                                            <form method="post" action="">
                                                <input type="hidden" name="add_to_cart" value="1">
                                                <input type="hidden" name="book_id" value="<?php echo $related_book['book_id']; ?>">
                                                <button type="submit" class="add-to-cart">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="right-section">
                    <!-- Reviews Section -->
                    <div class="review-section">
                        <h2 class="section-title">Customer Reviews</h2>

                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <img src="/BookHeaven2.0/<?php echo htmlspecialchars($review['userimageurl'] ?? 'https://randomuser.me/api/portraits/women/43.jpg'); ?>"
                                            alt="<?php echo htmlspecialchars($review['username']); ?>" class="reviewer-image">
                
                                        <span class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></span>
                                        <span
                                            class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $review['rating']) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <p class="review-text">
                                        <?php echo htmlspecialchars($review['review_text']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No reviews yet. Be the first to review!</p>
                        <?php endif; ?>

                        <button class="toggle-button" id="reviewToggle">
                            <i class="fas fa-pen"></i> Write a Review
                        </button>

                        <div class="toggle-form" id="reviewForm" style="display: none;">
                            <h3 class="form-title">Write Your Review</h3>
                            <form method="post" action="">
                                <div class="rating-input">
                                    <label>Rating:</label>
                                    <div class="rating-stars">
                                        <input type="radio" id="star5" name="rating" value="5" required>
                                        <label for="star5" title="5 stars">★</label>
                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4" title="4 stars">★</label>
                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3" title="3 stars">★</label>
                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2" title="2 stars">★</label>
                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1" title="1 star">★</label>
                                    </div>
                                </div>
                                <textarea name="review_text" placeholder="Share your thoughts about this book..."
                                    required></textarea>
                                <button type="submit" name="submit_review" class="submit-btn">Submit Review</button>
                            </form>
                        </div>
                    </div>

                    <!-- Q&A Section -->
                    <div class="qa-section">
                        <h2 class="section-title">Questions & Answers</h2>

                        <?php if (!empty($questions)): ?>
                            <?php foreach ($questions as $question): ?>
                                <div class="qa-item">
                                    <div class="qa-header">
                                        <img src="BookHeaven2.0/<?php echo htmlspecialchars($question['questioner_image'] ?? 'https://randomuser.me/api/portraits/women/68.jpg'); ?>"
                                            alt="<?php echo htmlspecialchars($question['questioner_name']); ?>"
                                            class="questioner-image">
                                        <span
                                            class="questioner-name"><?php echo htmlspecialchars($question['questioner_name']); ?></span>
                                        <span
                                            class="question-date"><?php echo date('F j, Y', strtotime($question['created_at'])); ?></span>
                                    </div>
                                    <p class="question-text">
                                        <?php echo htmlspecialchars($question['question_text']); ?>
                                    </p>
                                    <?php if (!empty($question['answer_text'])): ?>
                                        <div class="answer-container">
                                            <span class="answer-label">ANSWER</span>
                                            <div class="qa-header">
                                                <img src="BookHeaven2.0/<?php echo htmlspecialchars($question['answerer_image'] ?? 'https://randomuser.me/api/portraits/men/75.jpg'); ?>"
                                                    alt="<?php echo htmlspecialchars($question['answerer_name']); ?>"
                                                    class="questioner-image">
                                                <span
                                                    class="questioner-name"><?php echo htmlspecialchars($question['answerer_name']); ?></span>
                                                <span
                                                    class="question-date"><?php echo date('F j, Y', strtotime($question['answer_date'])); ?></span>
                                            </div>
                                            <p class="answer-text">
                                                <?php echo htmlspecialchars($question['answer_text']); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No questions yet. Ask the first question!</p>
                        <?php endif; ?>

                        <button class="toggle-button" id="qaToggle">
                            <i class="fas fa-question"></i> Ask a Question
                        </button>

                        <div class="toggle-form" id="qaForm" style="display: none;">
                            <h3 class="form-title">Ask a Question</h3>
                            <form method="post" action="">
                                <textarea name="question_text" placeholder="What would you like to know about this book?"
                                    required></textarea>
                                <button type="submit" name="submit_question" class="submit-btn">Submit Question</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-book-found">
                <h2>Book not found</h2>
                <p>The book you're looking for doesn't exist or has been removed.</p>
                <a href="/BookHeaven2.0/index.php" class="btn btn-primary">Browse Books</a>
            </div>
        <?php endif; ?>
    </main>
    <?php include_once("../footer.php"); ?>
    <script>
        // Toggle review form
        document.getElementById('reviewToggle').addEventListener('click', function () {
            const form = document.getElementById('reviewForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });

        // Toggle Q&A form
        document.getElementById('qaToggle').addEventListener('click', function () {
            const form = document.getElementById('qaForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });

        // Rating stars interaction
        const ratingStars = document.querySelectorAll('.rating-stars input');
        ratingStars.forEach(star => {
            star.addEventListener('change', function () {
                const ratingValue = this.value;
                // You can use this value if needed
            });
        });
    </script>
</body>
</html>