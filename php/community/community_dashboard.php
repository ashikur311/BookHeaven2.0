<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Session expired']);
    exit();
}
include_once("../../db_connection.php");

// Set the default timezone
date_default_timezone_set('Asia/Dhaka');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /BookHeaven2.0/php/authentication.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$community_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if user is member of this community
if ($community_id > 0) {
    $member_check = "SELECT * FROM community_members 
                    WHERE community_id = ? AND user_id = ? AND status = 'active'";
    $stmt = $conn->prepare($member_check);
    $stmt->bind_param("ii", $community_id, $user_id);
    $stmt->execute();
    $is_member = $stmt->get_result()->num_rows > 0;

    if (!$is_member) {
        header("Location: community_dashboard.php");
        exit();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new post
    if (isset($_POST['create_post'])) {
        $content = trim($_POST['content']);
        $image_url = null;

        // Handle image upload
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/BookHeaven2.0/assets/post_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION);
            $filename = 'post_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $filename;

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['post_image']['type'], $allowed_types)) {
                if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_path)) {
                    $image_url = $filename;
                }
            }
        }

        if (!empty($content)) {
            $insert_sql = "INSERT INTO community_posts 
                          (community_id, user_id, content, image_url) 
                          VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iiss", $community_id, $user_id, $content, $image_url);
            $stmt->execute();
        }

        header("Location: community_dashboard.php?id=$community_id");
        exit();
    }

    // Toggle like
    if (isset($_POST['toggle_like'])) {
        $post_id = intval($_POST['post_id']);

        // Check if already liked
        $check_sql = "SELECT * FROM post_likes 
                     WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Unlike
            $delete_sql = "DELETE FROM post_likes 
                          WHERE post_id = ? AND user_id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();
        } else {
            // Like
            $insert_sql = "INSERT INTO post_likes 
                          (post_id, user_id) 
                          VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ii", $post_id, $user_id);
            $stmt->execute();
        }

        exit(); // AJAX response
    }

    // Add comment
    if (isset($_POST['add_comment'])) {
        $post_id = intval($_POST['post_id']);
        $content = trim($_POST['content']);

        if (!empty($content)) {
            try {
                // Insert the comment into the database
                $insert_sql = "INSERT INTO post_comments 
                          (post_id, user_id, content) 
                          VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("iis", $post_id, $user_id, $content);

                if ($stmt->execute()) {
                    $comment_id = $conn->insert_id;

                    // Get the newly added comment with user info
                    $comment_sql = "SELECT c.*, u.username, u.user_profile
                               FROM post_comments c
                               JOIN users u ON c.user_id = u.user_id
                               WHERE c.comment_id = ?";
                    $stmt = $conn->prepare($comment_sql);
                    $stmt->bind_param("i", $comment_id);
                    $stmt->execute();
                    $comment = $stmt->get_result()->fetch_assoc();

                    if (!$comment) {
                        throw new Exception("Failed to retrieve comment after insertion");
                    }

                    // Format the response
                    $response = [
                        'success' => true,
                        'comment' => [
                            'user' => $comment['username'],
                            'avatar' => !empty($comment['user_profile']) ? "/BookHeaven2.0/" . $comment['user_profile'] : "https://via.placeholder.com/50",
                            'time' => time_elapsed_string($comment['created_at']),
                            'content' => htmlspecialchars($comment['content'])
                        ]
                    ];

                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit();
                } else {
                    throw new Exception("Failed to execute comment insertion");
                }
            } catch (Exception $e) {
                error_log("Comment submission error: " . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Database error: ' . $e->getMessage()
                ]);
                exit();
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
            exit();
        }
    }
}

// Get community details
if ($community_id > 0) {
    $community_sql = "SELECT c.*, u.username as creator_name, u.user_profile as creator_avatar
                     FROM communities c
                     JOIN users u ON c.created_by = u.user_id
                     WHERE c.community_id = ? AND c.status = 'active'";
    $stmt = $conn->prepare($community_sql);
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $community = $stmt->get_result()->fetch_assoc();

    if (!$community) {
        header("Location: community_dashboard.php");
        exit();
    }

    // Format community cover image URL
    $community['cover_image_url'] = !empty($community['cover_image_url']) ?
        "/BookHeaven2.0/assets/community_images/" . basename($community['cover_image_url']) :
        "https://via.placeholder.com/1200x300";
    $community['creator_avatar'] = !empty($community['creator_avatar']) ?
        "/BookHeaven2.0/" . $community['creator_avatar'] :
        "https://via.placeholder.com/50";
}

// Get current user's profile
$user_sql = "SELECT username, user_profile FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$current_user = $user_result->fetch_assoc();
$current_user_avatar = !empty($current_user['user_profile']) ?
    "/BookHeaven2.0/" . $current_user['user_profile'] :
    "https://via.placeholder.com/50";
$current_username = $current_user['username'];

// Get community members (excluding current user)
$members_sql = "SELECT u.user_id, u.username, u.user_profile, cm.role, cm.joined_at 
               FROM community_members cm
               JOIN users u ON cm.user_id = u.user_id
               WHERE cm.community_id = ? AND cm.status = 'active' AND cm.user_id != ?
               ORDER BY 
                 CASE cm.role 
                   WHEN 'admin' THEN 1 
                   WHEN 'moderator' THEN 2 
                   ELSE 3 
                 END, cm.joined_at";
$members_stmt = $conn->prepare($members_sql);
$members_stmt->bind_param("ii", $community_id, $user_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();

// Get community posts with comments and likes
$posts_sql = "SELECT p.*, u.username, u.user_profile,
              (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id) as like_count,
              (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id AND status = 'active') as comment_count
              FROM community_posts p
              JOIN users u ON p.user_id = u.user_id
              WHERE p.community_id = ? AND p.status = 'active'
              ORDER BY p.created_at DESC";
$posts_stmt = $conn->prepare($posts_sql);
$posts_stmt->bind_param("i", $community_id);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();

// Prepare posts data with comments
$posts = [];
while ($post = $posts_result->fetch_assoc()) {
    // Format user avatar URL
    $post['avatar'] = !empty($post['user_profile']) ?
        "/BookHeaven2.0/" . $post['user_profile'] :
        "https://via.placeholder.com/50";

    // Format post image URL
    $post['image_url'] = !empty($post['image_url']) ?
        "/BookHeaven2.0/assets/post_images/" . $post['image_url'] :
        null;

    // Get comments for this post
    $comments_sql = "SELECT c.*, u.username, u.user_profile
                    FROM post_comments c
                    JOIN users u ON c.user_id = u.user_id
                    WHERE c.post_id = ? AND c.status = 'active'
                    ORDER BY c.created_at ASC";
    $comments_stmt = $conn->prepare($comments_sql);
    $comments_stmt->bind_param("i", $post['post_id']);
    $comments_stmt->execute();
    $comments_result = $comments_stmt->get_result();

    $comments = [];
    while ($comment = $comments_result->fetch_assoc()) {
        $comments[] = [
            'user' => $comment['username'],
            'avatar' => !empty($comment['user_profile']) ? "/BookHeaven2.0/" . $comment['user_profile'] : "https://via.placeholder.com/50",
            'time' => time_elapsed_string($comment['created_at']),
            'content' => htmlspecialchars($comment['content'])
        ];
    }

    // Check if current user liked this post
    $like_check_sql = "SELECT * FROM post_likes 
                      WHERE post_id = ? AND user_id = ?";
    $like_check_stmt = $conn->prepare($like_check_sql);
    $like_check_stmt->bind_param("ii", $post['post_id'], $user_id);
    $like_check_stmt->execute();
    $is_liked = $like_check_stmt->get_result()->num_rows > 0;

    $posts[] = [
        'id' => $post['post_id'],
        'community_id' => $post['community_id'],
        'user' => $post['username'],
        'avatar' => $post['avatar'],
        'time' => time_elapsed_string($post['created_at']),
        'content' => htmlspecialchars($post['content']),
        'image_url' => $post['image_url'],
        'likes' => $post['like_count'],
        'is_liked' => $is_liked,
        'comments' => $comments,
        'comment_count' => $post['comment_count']
    ];
}

// Function to format time
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full)
        $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($community) ? htmlspecialchars($community['name']) : 'Community'; ?> | Book Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --transition: all 0.3s ease;
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            transition: var(--transition);
        }

        main {
            display: flex;
            min-height: calc(100vh - 120px);
            padding: 20px;
            gap: 20px;
        }

        aside {
            flex: 1;
            max-width: 350px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .community-info-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 0;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .community-cover {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .community-details {
            padding: 20px;
        }

        .community-details h2 {
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .community-details p {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .community-creator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .creator-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .creator-info small {
            color: var(--text-light);
            font-size: 0.8rem;
        }

        .members-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .members-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .members-header h3 {
            color: var(--text-color);
        }

        .member-count {
            background-color: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .member-list {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
        }

        .member-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .member-item:last-child {
            border-bottom: none;
        }

        .member-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .member-name {
            font-size: 0.9rem;
        }

        .member-role {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .message-btn {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .message-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .dashboard-content {
            flex: 3;
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }

        /* Create post form */
        .create-post {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }

        .post-form {
            display: flex;
            flex-direction: column;
        }

        .post-input {
            width: 100%;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 15px;
            background-color: var(--card-bg);
            color: var(--text-color);
            resize: none;
            min-height: 100px;
        }

        .post-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(87, 171, 210, 0.2);
        }

        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-input {
            display: none;
        }

        .file-label {
            display: flex;
            align-items: center;
            color: var(--text-light);
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 5px;
            transition: var(--transition);
        }

        .file-label:hover {
            background-color: var(--hover-bg);
            color: var(--primary-color);
        }

        .file-label i {
            margin-right: 5px;
        }

        .post-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
        }

        .post-submit:hover {
            background-color: var(--primary-dark);
        }

        /* Posts container */
        .posts-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .post-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .post-time {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .post-content {
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .post-image {
            max-width: 100%;
            max-height: 500px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: block;
        }

        .post-footer {
            display: flex;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }

        .action-btn {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            margin-right: 20px;
            transition: var(--transition);
        }

        .action-btn:hover {
            color: var(--primary-color);
        }

        .action-btn i {
            margin-right: 5px;
        }

        .like-btn.liked {
            color: var(--danger-color);
        }

        .comment-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            display: none;
        }

        .comment-form {
            display: flex;
            margin-bottom: 20px;
        }

        .comment-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            background-color: var(--card-bg);
            color: var(--text-color);
            transition: var(--transition);
            margin-right: 10px;
        }

        .comment-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(87, 171, 210, 0.2);
        }

        .comment-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: var(--transition);
        }

        .comment-submit:hover {
            background-color: var(--primary-dark);
        }

        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .comment-item {
            display: flex;
            gap: 10px;
        }

        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            align-self: flex-start;
        }

        .comment-content {
            flex: 1;
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .comment-user {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .comment-time {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .comment-text {
            font-size: 0.9rem;
            line-height: 1.4;
            padding-left: 10px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            main {
                flex-direction: column;
            }

            aside {
                max-width: 100%;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 768px) {
            .post-footer {
                justify-content: space-between;
            }

            .action-btn {
                margin-right: 0;
            }
        }

        @media (max-width: 576px) {
            main {
                padding: 10px;
            }

            .create-post,
            .post-card,
            .community-info-card,
            .members-card {
                padding: 15px;
            }

            .post-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-avatar {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .comment-form {
                flex-direction: column;
            }

            .comment-input {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .post-actions {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .post-submit {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include_once("../../header.php"); ?>

    <main>
        <aside>
            <div class="community-info-card">
                <?php if (isset($community)): ?>
                    <img src="<?php echo $community['cover_image_url']; ?>" alt="Community Cover" class="community-cover">
                    <div class="community-details">
                        <h2><?php echo htmlspecialchars($community['name']); ?></h2>
                        <p><?php echo htmlspecialchars($community['description']); ?></p>
                        
                        <div class="community-creator">
                            <img src="<?php echo $community['creator_avatar']; ?>" alt="Creator" class="creator-avatar">
                            <div class="creator-info">
                                <div>Created by <?php echo htmlspecialchars($community['creator_name']); ?></div>
                                <small><?php echo time_elapsed_string($community['created_at']); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="members-card">
                <div class="members-header">
                    <h3>Members</h3>
                    <span class="member-count"><?php echo $members_result->num_rows + 1; ?></span>
                </div>
                
                <div class="member-list">
                    <div class="member-item">
                        <div class="member-info">
                            <img src="<?php echo $current_user_avatar; ?>" alt="You" class="member-avatar">
                            <div>
                                <div class="member-name">You</div>
                                <div class="member-role">Member</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($members_result->num_rows > 0): ?>
                        <?php while ($member = $members_result->fetch_assoc()): ?>
                            <div class="member-item">
                                <div class="member-info">
                                    <img src="<?php echo !empty($member['user_profile']) ? '/BookHeaven2.0/' . htmlspecialchars($member['user_profile']) : 'https://via.placeholder.com/40'; ?>"
                                        alt="<?php echo htmlspecialchars($member['username']); ?>" class="member-avatar">
                                    <div>
                                        <div class="member-name"><?php echo htmlspecialchars($member['username']); ?></div>
                                        <div class="member-role"><?php echo ucfirst($member['role']); ?></div>
                                    </div>
                                </div>
                                <button class="message-btn"
                                    onclick="window.location.href='messages.php?user_id=<?php echo $member['user_id']; ?>'">
                                    <i class="fas fa-envelope"></i>
                                </button>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No other members found</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <div class="dashboard-content">
            <?php if (isset($community)): ?>
                <div class="create-post">
                    <form class="post-form" method="post" enctype="multipart/form-data">
                        <textarea name="content" class="post-input" placeholder="What's on your mind?" required></textarea>
                        <div class="post-actions">
                            <div>
                                <input type="file" id="post-image" name="post_image" class="file-input" accept="image/*">
                                <label for="post-image" class="file-label">
                                    <i class="fas fa-image"></i> Add Image
                                </label>
                            </div>
                            <button type="submit" name="create_post" class="post-submit">Post</button>
                        </div>
                    </form>
                </div>

                <div class="posts-container">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post-card" id="post-<?php echo $post['id']; ?>">
                                <div class="post-header">
                                    <img src="<?php echo $post['avatar']; ?>" alt="<?php echo $post['user']; ?>"
                                        class="user-avatar">
                                    <div class="user-info">
                                        <div class="user-name"><?php echo $post['user']; ?></div>
                                        <div class="post-time"><?php echo $post['time']; ?></div>
                                    </div>
                                </div>
                                <div class="post-content">
                                    <?php echo $post['content']; ?>
                                </div>
                                <?php if ($post['image_url']): ?>
                                    <img src="<?php echo $post['image_url']; ?>" alt="Post image" class="post-image">
                                <?php endif; ?>
                                <div class="post-footer">
                                    <button class="action-btn like-btn <?php echo $post['is_liked'] ? 'liked' : ''; ?>"
                                        onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span><?php echo $post['likes']; ?> Likes</span>
                                    </button>
                                    <button class="action-btn comment-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                        <i class="fas fa-comment"></i>
                                        <span><?php echo $post['comment_count']; ?> Comments</span>
                                    </button>
                                </div>
                                <div class="comment-section" id="comments-<?php echo $post['id']; ?>">
                                    <form class="comment-form" method="post"
                                        onsubmit="return addComment(event, <?php echo $post['id']; ?>)">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <input type="text" name="content" class="comment-input" placeholder="Write a comment..."
                                            required>
                                        <button type="submit" name="add_comment" class="comment-submit">Post</button>
                                    </form>

                                    <div class="comments-list" id="comments-list-<?php echo $post['id']; ?>">
                                        <?php foreach ($post['comments'] as $comment): ?>
                                            <div class="comment-item">
                                                <img src="<?php echo $comment['avatar']; ?>" alt="<?php echo $comment['user']; ?>"
                                                    class="comment-avatar">
                                                <div class="comment-content">
                                                    <div class="comment-header">
                                                        <div class="comment-user"><?php echo $comment['user']; ?></div>
                                                        <div class="comment-time"><?php echo $comment['time']; ?></div>
                                                    </div>
                                                    <div class="comment-text"><?php echo $comment['content']; ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="post-card">
                            <div class="post-content">
                                <p>No posts yet. Be the first to post in this community!</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="post-card">
                    <div class="post-content">
                        <p>Community not found or you don't have access to view it.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include_once("../../footer.php"); ?>
    <script>
        // Toggle like button
        function toggleLike(postId, button) {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('toggle_like', '1');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (response.ok) {
                        const likeCount = button.querySelector('span');
                        const currentCount = parseInt(likeCount.textContent);

                        if (button.classList.contains('liked')) {
                            // Unlike
                            button.classList.remove('liked');
                            likeCount.textContent = (currentCount - 1) + ' Likes';
                        } else {
                            // Like
                            button.classList.add('liked');
                            likeCount.textContent = (currentCount + 1) + ' Likes';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Toggle comments section
        function toggleComments(postId) {
            const commentSection = document.getElementById('comments-' + postId);
            commentSection.style.display = commentSection.style.display === 'block' ? 'none' : 'block';

            // Scroll to comments if opening
            if (commentSection.style.display === 'block') {
                commentSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Add new comment
        // Add new comment
        function addComment(event, postId) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            formData.append('add_comment', '1');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear the input field
                        form.querySelector('input[type="text"]').value = '';

                        // Create the new comment element
                        const commentElement = document.createElement('div');
                        commentElement.className = 'comment-item';
                        commentElement.innerHTML = `
                <img src="${data.comment.avatar}" alt="${data.comment.user}" class="comment-avatar">
                <div class="comment-content">
                    <div class="comment-user">${data.comment.user}</div>
                    <div class="comment-text">${data.comment.content}</div>
                    <div class="comment-time">${data.comment.time}</div>
                </div>
            `;

                        // Append the new comment to the comments list
                        const commentsList = document.getElementById('comments-list-' + postId);
                        commentsList.appendChild(commentElement);

                        // Update the comment count
                        const commentBtn = document.querySelector(`.comment-btn[onclick="toggleComments(${postId})"]`);
                        if (commentBtn) {
                            const countSpan = commentBtn.querySelector('span');
                            if (countSpan) {
                                const currentCount = parseInt(countSpan.textContent);
                                countSpan.textContent = (currentCount + 1) + ' Comments';
                            }
                        }
                    } else {
                        alert(data.error || 'Failed to add comment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding the comment');
                });

            return false;
        }
        // Initialize comment sections as hidden
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.comment-section').forEach(section => {
                section.style.display = 'none';
            });
        });
    </script>
</body>

</html>