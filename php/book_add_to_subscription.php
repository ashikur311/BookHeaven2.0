<?php
// Start session and include database connection
session_start();
require_once('../db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /BookHeaven2.0/php/authentication.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sub_id = isset($_GET['sub_id']) ? intval($_GET['sub_id']) : null;
$plan_type = isset($_GET['plan_type']) ? htmlspecialchars($_GET['plan_type']) : null;

// Get user's specific subscription
$subscription = null;
if ($sub_id) {
    $subscription_query = "SELECT us.*, sp.* 
                         FROM user_subscriptions us
                         JOIN subscription_plans sp ON us.subscription_plan_id = sp.plan_id
                         WHERE us.user_subscription_id = ? AND us.user_id = ? 
                         AND us.status = 'active' AND us.end_date > NOW()";
    $stmt = $conn->prepare($subscription_query);
    $stmt->bind_param("ii", $sub_id, $user_id);
    $stmt->execute();
    $subscription_result = $stmt->get_result();

    if ($subscription_result->num_rows > 0) {
        $subscription = $subscription_result->fetch_assoc();
        $plan = $subscription; // Since we joined the tables
        
        // Calculate days left in subscription
        $end_date = new DateTime($subscription['end_date']);
        $today = new DateTime();
        $days_left = $today->diff($end_date)->days;
        
        // Count books already added to this subscription
        $books_used_query = "SELECT COUNT(*) as count FROM user_subscription_rent_book_access 
                            WHERE user_subscription_id = ?";
        $stmt = $conn->prepare($books_used_query);
        $stmt->bind_param("i", $sub_id);
        $stmt->execute();
        $books_used_result = $stmt->get_result();
        $books_used = $books_used_result->fetch_assoc()['count'];
        
        // Calculate remaining books
        $books_remaining = $plan['book_quantity'] - $books_used;
        
        // Calculate progress percentage
        $books_progress = ($books_used / $plan['book_quantity']) * 100;
    }
}

// Get all unique genres from rent_books table for sidebar
$genres = [];
$genre_query = "SELECT DISTINCT genre FROM rent_books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre";
$genre_result = $conn->query($genre_query);
while ($row = $genre_result->fetch_assoc()) {
    $genres[] = $row['genre'];
}

// Get rent books based on selected genre (default: all)
$selected_genre = isset($_GET['genre']) ? $_GET['genre'] : 'all';
$rent_books = [];

$book_query = "SELECT * FROM rent_books";
               
if ($selected_genre !== 'all') {
    $book_query .= " WHERE genre = ?";
}

$book_query .= " ORDER BY title";

$stmt = $conn->prepare($book_query);
if ($selected_genre !== 'all') {
    $stmt->bind_param("s", $selected_genre);
}
$stmt->execute();
$book_result = $stmt->get_result();

while ($row = $book_result->fetch_assoc()) {
    $rent_books[] = $row;
}

// Handle adding book to subscription
if (isset($_POST['add_book']) && $subscription) {
    $rent_book_id = intval($_POST['rent_book_id']);
    
    if ($books_remaining > 0) {
        // Check if book is already in subscription
        $check_query = "SELECT * FROM user_subscription_rent_book_access 
                       WHERE user_subscription_id = ? AND rent_book_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $sub_id, $rent_book_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            // Add book to subscription
            $add_query = "INSERT INTO user_subscription_rent_book_access 
                         (user_subscription_id, rent_book_id, access_date, status, user_id)
                         VALUES (?, ?, NOW(), 'borrowed', ?)";
            $stmt = $conn->prepare($add_query);
            $stmt->bind_param("iii", $sub_id, $rent_book_id, $user_id);
            $stmt->execute();
            
            // Update books used count in user_subscriptions table
            $update_query = "UPDATE user_subscriptions 
                            SET available_rent_book = available_rent_book - 1 
                            WHERE user_subscription_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $sub_id);
            $stmt->execute();
            
            $_SESSION['message'] = "Book added to your subscription successfully!";
            header("Location: book_add_to_subscription.php?sub_id=$sub_id&plan_type=$plan_type");
            exit();
        } else {
            $_SESSION['error'] = "This book is already in your subscription.";
            header("Location: book_add_to_subscription.php?sub_id=$sub_id&plan_type=$plan_type");
            exit();
        }
    } else {
        $_SESSION['error'] = "You've reached your book limit for this subscription period.";
        header("Location: book_add_to_subscription.php?sub_id=$sub_id&plan_type=$plan_type");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Books to Subscription | BookHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/addbooktosubscription.css">
</head>
<body>
    <?php include_once("../header.php") ?>
    
    <!-- Display messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert success">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <main>
        <section>
            <?php if ($subscription): ?>
                <!-- Compact Plan Overview Section -->
                <div class="plan-overview">
                    <div class="plan-header">
                        <h1 class="plan-title"><?= htmlspecialchars($plan['plan_name']) ?> Subscription</h1>
                        <div class="plan-status">Active</div>
                    </div>
                    
                    <div class="plan-details-container">
                        <div class="plan-features">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M21 5h-8v14h8V5zm-10 0H3v14h8V5z"/>
                                    </svg>
                                </div>
                                <div class="feature-text">
                                    <div class="feature-label">Books per month</div>
                                    <div class="feature-value"><?= $plan['book_quantity'] ?></div>
                                </div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                                    </svg>
                                </div>
                                <div class="feature-text">
                                    <div class="feature-label">Books Used</div>
                                    <div class="feature-value"><?= $books_used ?></div>
                                </div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                    </svg>
                                </div>
                                <div class="feature-text">
                                    <div class="feature-label">Access to</div>
                                    <div class="feature-value">All Genres</div>
                                </div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 12H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z"/>
                                    </svg>
                                </div>
                                <div class="feature-text">
                                    <div class="feature-label">Premium Support</div>
                                    <div class="feature-value">24/7</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="plan-progress">
                            <div class="progress-item">
                                <div class="progress-header">
                                    <div class="progress-title">Books Remaining</div>
                                    <div class="progress-value"><?= $books_remaining ?>/<?= $plan['book_quantity'] ?></div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $books_progress ?>%"></div>
                                </div>
                                <div class="progress-text">
                                    <span>Used: <?= $books_used ?></span>
                                    <span>Reset: <?= date('F j', strtotime($subscription['end_date'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="days-left">
                                <div class="days-left-value"><?= $days_left ?></div>
                                <div class="days-left-label">days left</div>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="add-books-header">
                    <div class="header-container">
                        <h2>Add Rent Books</h2>
                        <a href="audio_book_add_to_subscription.php?sub_id=<?= $sub_id ?>&plan_type=<?= $plan_type ?>" class="add-audiobook-btn">
                            <i class="fas fa-headphones"></i> Add Audio Books
                        </a>
                    </div>
                </section>

                <!-- Catalog Section with Block Genre Display -->
                <div class="catalog-container">
                    <aside class="genre-sidebar">
                        <h3 class="genre-title">Browse Genres</h3>
                        <div class="genre-list">
                            <a href="?sub_id=<?= $sub_id ?>&plan_type=<?= $plan_type ?>&genre=all" 
                               class="genre-item <?= $selected_genre === 'all' ? 'active' : '' ?>">All Genres</a>
                            <?php foreach ($genres as $genre): ?>
                                <a href="?sub_id=<?= $sub_id ?>&plan_type=<?= $plan_type ?>&genre=<?= urlencode($genre) ?>" 
                                   class="genre-item <?= $selected_genre === $genre ? 'active' : '' ?>">
                                    <?= htmlspecialchars($genre) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </aside>

                    <div class="book-grid">
                        <?php if (count($rent_books) > 0): ?>
                            <?php foreach ($rent_books as $book): ?>
                                <div class="book-card">
                                    <img src="/BookHeaven2.0/<?= htmlspecialchars($book['poster_url']) ?>"
                                         alt="<?= htmlspecialchars($book['title']) ?>" class="book-cover">
                                    <div class="book-info">
                                        <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                                        <p class="book-author"><?= htmlspecialchars($book['writer']) ?></p>
                                        <p class="book-genre"><?= htmlspecialchars($book['genre']) ?></p>
                                        <form method="post">
                                            <input type="hidden" name="rent_book_id" value="<?= $book['rent_book_id'] ?>">
                                            <button type="submit" name="add_book" class="add-button" 
                                                <?= $books_remaining <= 0 ? 'disabled title="You have reached your book limit for this period"' : '' ?>>
                                                Add to My Subscription
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-books">No rent books found in this category.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-subscription">
                    <?php if ($sub_id): ?>
                        <h2>Subscription Not Found or Expired</h2>
                        <p>The subscription you're trying to access is either expired or doesn't exist.</p>
                    <?php else: ?>
                        <h2>You don't have an active subscription</h2>
                        <p>To access our book collection, please subscribe to one of our plans.</p>
                    <?php endif; ?>
                    <a href="/BookHeaven2.0/php/subscription_plan.php" class="subscribe-btn">View Subscription Plans</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include_once("../footer.php") ?>
</body>
</html>