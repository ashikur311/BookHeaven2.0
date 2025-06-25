<?php
// Start session and include database connection
session_start();
require_once('../db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sub_id = isset($_GET['sub_id']) ? intval($_GET['sub_id']) : null;
$plan_type = isset($_GET['plan_type']) ? htmlspecialchars($_GET['plan_type']) : null;

// Get user's specific subscription
$subscription = null;
if ($sub_id) {
    $subscription_query = "SELECT us.*, sp.plan_name, sp.audiobook_quantity 
                          FROM user_subscriptions us
                          JOIN subscription_plans sp ON us.subscription_plan_id = sp.plan_id
                          WHERE us.user_subscription_id = ? AND us.user_id = ? 
                          AND us.status = 'active' AND us.end_date > NOW()";
    $stmt = $conn->prepare($subscription_query);
    $stmt->bind_param("ii", $sub_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $subscription = $result->fetch_assoc();
        
        // Get count of used audiobooks
        $used_audio_query = "SELECT COUNT(*) as used_count 
                            FROM user_subscription_audiobook_access 
                            WHERE user_subscription_id = ? AND status = 'borrowed'";
        $stmt2 = $conn->prepare($used_audio_query);
        $stmt2->bind_param("i", $subscription['user_subscription_id']);
        $stmt2->execute();
        $used_result = $stmt2->get_result();
        $used_data = $used_result->fetch_assoc();
        
        $subscription['used_audio'] = $used_data['used_count'];
        $subscription['remaining_audio'] = $subscription['audiobook_quantity'] - $used_data['used_count'];
        
        // Calculate days left
        $end_date = new DateTime($subscription['end_date']);
        $today = new DateTime();
        $interval = $today->diff($end_date);
        $subscription['days_left'] = $interval->days;
    } else {
        // Invalid or expired subscription
        header("Location: subscription_plans.php");
        exit();
    }
} else {
    // No subscription ID provided
    header("Location: subscription_plans.php");
    exit();
}

// Get all genres from audiobooks
$genres = array();
$genre_query = "SELECT DISTINCT genre FROM audiobooks WHERE status = 'visible'";
$genre_result = $conn->query($genre_query);
while ($row = $genre_result->fetch_assoc()) {
    $genres[] = $row['genre'];
}

// Get audiobooks based on selected genre (default to all)
$selected_genre = isset($_GET['genre']) ? $_GET['genre'] : 'all';
$audiobooks = array();

$audiobook_query = "SELECT * FROM audiobooks WHERE status = 'visible'";
if ($selected_genre != 'all') {
    $audiobook_query .= " AND genre = ?";
}

$audiobook_query .= " ORDER BY title ASC";

$stmt3 = $conn->prepare($audiobook_query);
if ($selected_genre != 'all') {
    $stmt3->bind_param("s", $selected_genre);
}
$stmt3->execute();
$audiobook_result = $stmt3->get_result();

while ($row = $audiobook_result->fetch_assoc()) {
    // Check if user already has this audiobook in their subscription
    $check_query = "SELECT * FROM user_subscription_audiobook_access 
                   WHERE user_subscription_id = ? AND audiobook_id = ? AND status = 'borrowed'";
    $stmt4 = $conn->prepare($check_query);
    $stmt4->bind_param("ii", $subscription['user_subscription_id'], $row['audiobook_id']);
    $stmt4->execute();
    $check_result = $stmt4->get_result();
    
    $row['already_added'] = ($check_result->num_rows > 0);
    $audiobooks[] = $row;
}

// Handle adding audiobook to subscription
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_audiobook'])) {
    $audiobook_id = intval($_POST['audiobook_id']);
    
    // Check if user has remaining audiobook quota
    if ($subscription['remaining_audio'] > 0) {
        // Verify audiobook isn't already added
        $check_query = "SELECT * FROM user_subscription_audiobook_access 
                       WHERE user_subscription_id = ? AND audiobook_id = ? AND status = 'borrowed'";
        $stmt5 = $conn->prepare($check_query);
        $stmt5->bind_param("ii", $sub_id, $audiobook_id);
        $stmt5->execute();
        $check_result = $stmt5->get_result();
        
        if ($check_result->num_rows == 0) {
            // Add to user's subscription
            $add_query = "INSERT INTO user_subscription_audiobook_access 
                         (user_subscription_id, audiobook_id, access_date, status, user_id)
                         VALUES (?, ?, NOW(), 'borrowed', ?)";
            $stmt6 = $conn->prepare($add_query);
            $stmt6->bind_param("iii", $sub_id, $audiobook_id, $user_id);
            
            if ($stmt6->execute()) {
                // Update subscription count
                $update_query = "UPDATE user_subscriptions 
                               SET available_audio = available_audio - 1 
                               WHERE user_subscription_id = ?";
                $stmt7 = $conn->prepare($update_query);
                $stmt7->bind_param("i", $sub_id);
                $stmt7->execute();
                
                $_SESSION['message'] = "Audiobook added to your subscription successfully!";
                header("Location: audio_book_add_to_subscription.php?sub_id=$sub_id&plan_type=$plan_type");
                exit();
            } else {
                $_SESSION['error'] = "Failed to add audiobook to your subscription.";
            }
        } else {
            $_SESSION['error'] = "This audiobook is already in your subscription.";
        }
    } else {
        $_SESSION['error'] = "You've reached your monthly audiobook limit. Please wait until your subscription renews.";
    }
    
    header("Location: audio_book_add_to_subscription.php?sub_id=$sub_id&plan_type=$plan_type");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Audio Books to Subscription | BookHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/addbooktosubscription.css">
    <style>
      :root {
        --primary-color: #57abd2;
        --primary-dark: #3d8eb4;
        --secondary-color: #f8f5fc;
        --accent-color: rgb(223, 219, 227);
        --text-color: #333;
        --text-light: #666;
        --light-purple: #e6d9f2;
        --dark-text: #212529;
        --light-text: #f8f9fa;
        --card-bg: #ffffff;
        --aside-bg: #f0f2f5;
        --nav-hover: #e0e0e0;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --border-color: #e0e0e0;
        --hover-bg: #f5f5f5;
        --even-row-bg: #f9f9f9;
        --header-bg: #f0f0f0;
        --header-text: #333;
        --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        --plan-bg: linear-gradient(135deg, #57abd2 0%, #3d8eb4 100%);
    }

    .dark-mode {
        --primary-color: #57abd2;
        --primary-dark: #4a9bc1;
        --secondary-color: #2d3748;
        --accent-color: #4a5568;
        --text-color: #f8f9fa;
        --text-light: #a0aec0;
        --light-purple: #4a5568;
        --dark-text: #f8f9fa;
        --light-text: #212529;
        --card-bg: #1a202c;
        --aside-bg: #1a202c;
        --nav-hover: #4a5568;
        --border-color: #4a5568;
        --hover-bg: #2d3748;
        --even-row-bg: #2d3748;
        --header-bg: #1a202c;
        --header-text: #f8f9fa;
        --card-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
        --plan-bg: linear-gradient(135deg, #1a3d4a 0%, #123140 100%);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        transition: background-color 0.3s, color 0.3s;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--secondary-color);
        color: var(--text-color);
        line-height: 1.6;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    main {
        padding: 1.5rem 5%;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
        flex: 1;
    }

    /* Error Message */
    .error-message {
        background-color: var(--danger-color);
        color: white;
        padding: 0.8rem 1rem;
        border-radius: 5px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .error-message svg {
        fill: white;
        width: 18px;
        height: 18px;
    }

    /* Compact Plan Overview Section */
    .plan-overview {
        background: var(--plan-bg);
        border-radius: 10px;
        box-shadow: var(--card-shadow);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        color: white;
    }

    .plan-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .plan-title {
        font-size: 1.5rem;
        font-weight: 600;
    }

    .plan-status {
        background-color: rgba(255, 255, 255, 0.2);
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .plan-details-container {
        display: flex;
        gap: 1.5rem;
    }

    .plan-features {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        background-color: rgba(255, 255, 255, 0.1);
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.8rem;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .feature-item:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .feature-icon {
        width: 40px;
        height: 40px;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .feature-icon svg {
        width: 18px;
        height: 18px;
        fill: white;
    }

    .feature-text {
        flex: 1;
    }

    .feature-label {
        font-size: 0.8rem;
        opacity: 0.8;
        margin-bottom: 0.1rem;
    }

    .feature-value {
        font-size: 1rem;
        font-weight: 600;
    }

    .plan-progress {
        flex: 0 0 300px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1rem;
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .progress-item {
        margin-bottom: 1rem;
    }

    .progress-item:last-child {
        margin-bottom: 0;
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .progress-title {
        font-weight: 500;
    }

    .progress-value {
        font-weight: 600;
    }

    .progress-bar {
        height: 6px;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 0.3rem;
    }

    .progress-fill {
        height: 100%;
        background-color: white;
        border-radius: 3px;
    }

    .progress-text {
        font-size: 0.75rem;
        opacity: 0.8;
        display: flex;
        justify-content: space-between;
    }

    .days-left {
        text-align: center;
        padding: 0.8rem;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        margin-top: 1rem;
    }

    .days-left-value {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.2rem;
    }

    .days-left-label {
        font-size: 0.85rem;
        opacity: 0.8;
    }

    /* Add Books Header Section */
    .add-books-header {
        background-color: var(--card-bg);
        border-radius: 10px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .add-books-header h2 {
        font-size: 1.3rem;
        color: var(--primary-color);
        margin: 0;
    }

    .add-book-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1rem;
        background-color: var(--primary-color);
        color: white;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.3s;
        white-space: nowrap;
    }

    .add-book-btn:hover {
        background-color: var(--primary-dark);
    }

    .add-book-btn svg {
        fill: white;
    }

    /* Audio Book Grid */
    .catalog-container {
        display: flex;
        gap: 1.5rem;
    }

    .book-grid {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .book-card {
        background-color: var(--card-bg);
        border-radius: 10px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        transition: transform 0.3s;
        display: flex;
        flex-direction: column;
    }

    .book-card:hover {
        transform: translateY(-5px);
    }

    .book-cover {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .book-info {
        padding: 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .book-title {
        font-weight: 600;
        margin-bottom: 0.3rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .book-author {
        color: var(--text-light);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .book-rating {
        color: var(--warning-color);
        font-size: 0.9rem;
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
    }

    .star-icon {
        margin-right: 0.3rem;
    }

    /* Audio Player Styles - Compact Version */
    .audio-player {
        width: 100%;
        height: 36px; /* Reduced height */
        margin: 0.5rem 0;
        min-height: 36px; /* Ensures minimum height */
    }

    .audio-player::-webkit-media-controls-panel {
        background-color: var(--primary-color);
        border-radius: 5px;
        height: 36px;
    }

    .audio-player::-webkit-media-controls-play-button,
    .audio-player::-webkit-media-controls-mute-button {
        filter: brightness(0) invert(1);
    }

    .audio-player::-webkit-media-controls-current-time-display,
    .audio-player::-webkit-media-controls-time-remaining-display {
        color: white;
        font-size: 0.8rem;
    }

    .audio-player::-webkit-media-controls-timeline {
        background-color: rgba(255, 255, 255, 0.5);
        border-radius: 2px;
    }

    /* Hide download button */
    .audio-player::-webkit-media-controls-download-button {
        display: none !important;
    }

    .audio-player::-webkit-media-controls-enclosure {
        border-radius: 5px;
        height: 36px;
    }

    .add-button {
        width: 100%;
        padding: 0.5rem;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.3s;
        margin-top: auto;
    }

    .add-button:hover {
        background-color: var(--primary-dark);
    }

    .add-button:disabled {
        background-color: var(--text-light);
        cursor: not-allowed;
    }

    /* Genre Sidebar */
    .genre-sidebar {
        width: 250px;
        background-color: var(--card-bg);
        border-radius: 10px;
        box-shadow: var(--card-shadow);
        padding: 1rem;
        height: fit-content;
    }

    .genre-title {
        font-size: 1.1rem;
        margin-bottom: 0.8rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--primary-color);
    }

    .genre-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .genre-item {
        padding: 0.6rem 0.8rem;
        background-color: var(--hover-bg);
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .genre-item:hover {
        background-color: var(--primary-color);
        color: white;
    }

    .genre-item.active {
        background-color: var(--primary-color);
        color: white;
        font-weight: 500;
    }

    @media (max-width: 1200px) {
        .plan-progress {
            flex: 0 0 250px;
        }
    }

    @media (max-width: 992px) {
        .plan-details-container {
            flex-direction: column;
            gap: 1rem;
        }
        
        .plan-features {
            grid-template-columns: 1fr;
        }
        
        .plan-progress {
            flex: 1;
        }
    }

    @media (max-width: 768px) {
        main {
            padding: 1rem;
        }

        .catalog-container {
            flex-direction: column;
        }

        .genre-sidebar {
            width: 100%;
            margin-bottom: 1rem;
        }

        .book-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .book-cover {
            height: 160px;
        }

        .audio-player {
            height: 32px;
            min-height: 32px;
        }

        .audio-player::-webkit-media-controls-panel,
        .audio-player::-webkit-media-controls-enclosure {
            height: 32px;
        }
    }

    @media (max-width: 576px) {
        .plan-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .book-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }

        .book-cover {
            height: 140px;
        }

        .book-info {
            padding: 0.8rem;
        }

        .book-title {
            font-size: 0.9rem;
        }

        .book-author, .book-rating {
            font-size: 0.8rem;
        }

        .audio-player {
            height: 28px;
            min-height: 28px;
            margin: 0.3rem 0;
        }

        .audio-player::-webkit-media-controls-panel,
        .audio-player::-webkit-media-controls-enclosure {
            height: 28px;
        }

        .add-button {
            padding: 0.4rem;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 400px) {
        .book-grid {
            grid-template-columns: 1fr 1fr;
        }

        .feature-item {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
            padding: 0.8rem 0.5rem;
        }

        .feature-icon {
            width: 36px;
            height: 36px;
        }

        .feature-text {
            width: 100%;
        }
    }
    </style>
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
                        <h1 class="plan-title"><?= htmlspecialchars($subscription['plan_name']) ?> Subscription</h1>
                        <div class="plan-status">Active</div>
                    </div>
                    
                    <div class="plan-details-container">
                        <div class="plan-features">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-headphones"></i>
                                </div>
                                <div class="feature-text">
                                    <div class="feature-label">Audio Books per month</div>
                                    <div class="feature-value"><?= $subscription['audiobook_quantity'] ?></div>
                                </div>
                            </div>
                            
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-music"></i>
                                </div>
                                <div class="feature-text">
                                    <div class="feature-label">Audio Books Used</div>
                                    <div class="feature-value"><?= $subscription['used_audio'] ?></div>
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
                                    <div class="progress-title">Audio Books Remaining</div>
                                    <div class="progress-value"><?= $subscription['remaining_audio'] ?>/<?= $subscription['audiobook_quantity'] ?></div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= ($subscription['remaining_audio'] / $subscription['audiobook_quantity']) * 100 ?>%"></div>
                                </div>
                                <div class="progress-text">
                                    <span>Used: <?= $subscription['used_audio'] ?></span>
                                    <span>Reset: <?= date('F j', strtotime($subscription['end_date'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="days-left">
                                <div class="days-left-value"><?= $subscription['days_left'] ?></div>
                                <div class="days-left-label">days left</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Books Header Section -->
                <div class="add-books-header">
                    <div class="header-container">
                        <h2>Add Audio Books</h2>
                        <a href="book_add_to_subscription.php?sub_id=<?= $sub_id ?>&plan_type=<?= $plan_type ?>" class="add-book-btn">
                            <i class="fas fa-book"></i> Add Regular Books
                        </a>
                    </div>
                </div>

                <!-- Catalog Section with Block Genre Display -->
                <div class="catalog-container">
                    <aside class="genre-sidebar">
                        <h3 class="genre-title">Browse Genres</h3>
                        <div class="genre-list">
                            <a href="?sub_id=<?= $sub_id ?>&plan_type=<?= $plan_type ?>&genre=all" 
                               class="genre-item <?= $selected_genre == 'all' ? 'active' : '' ?>">All Genres</a>
                            <?php foreach ($genres as $genre): ?>
                                <a href="?sub_id=<?= $sub_id ?>&plan_type=<?= $plan_type ?>&genre=<?= urlencode($genre) ?>" 
                                   class="genre-item <?= $selected_genre == $genre ? 'active' : '' ?>">
                                    <?= htmlspecialchars($genre) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </aside>

                    <div class="book-grid">
                        <?php if (count($audiobooks) > 0): ?>
                            <?php foreach ($audiobooks as $audiobook): ?>
                                <div class="book-card">
                                    <img src="/BookHeaven2.0/<?= htmlspecialchars($audiobook['poster_url'] ? $audiobook['poster_url'] : 'https://via.placeholder.com/250x200/57abd2/ffffff?text=Audio+Book') ?>" 
                                         alt="<?= htmlspecialchars($audiobook['title']) ?>" class="book-cover">
                                    <div class="book-info">
                                        <h3 class="book-title"><?= htmlspecialchars($audiobook['title']) ?></h3>
                                        <p class="book-author"><?= htmlspecialchars($audiobook['writer']) ?></p>
                                        <div class="book-rating">
                                            <span class="star-icon">★</span> 4.5
                                        </div>
                                        <audio controls class="audio-player" ontimeupdate="limitPlayback(this)">
                                            <source src="<?= htmlspecialchars($audiobook['audio_url']) ?>" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>
                                        <form method="post" action="">
                                            <input type="hidden" name="audiobook_id" value="<?= $audiobook['audiobook_id'] ?>">
                                            <button type="submit" name="add_audiobook" class="add-button" 
                                                <?= ($audiobook['already_added'] || $subscription['remaining_audio'] <= 0) ? 'disabled' : '' ?>>
                                                <?= $audiobook['already_added'] ? 'Already Added' : 'Add to My Subscription' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-books">No audiobooks found in this category.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-subscription">
                    <h2>Subscription Not Found or Expired</h2>
                    <p>The subscription you're trying to access is either expired or doesn't exist.</p>
                    <a href="/BookHeaven2.0/php/subscription_plans.php" class="subscribe-btn">View Subscription Plans</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include_once("../footer.php") ?>

    <script>
        // Function to limit audio playback to 5 minutes (300 seconds)
        function limitPlayback(audioElement) {
            if (audioElement.currentTime > 300) { // 5 minutes in seconds
                audioElement.pause();
                audioElement.currentTime = 0;
                alert('Preview limited to 5 minutes. Subscribe to listen to the full audiobook.');
            }
        }

        // Disable right-click and context menu on audio elements to prevent download
        document.querySelectorAll('audio').forEach(audio => {
            audio.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
        });
    </script>
</body>
</html>