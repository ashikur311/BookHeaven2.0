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
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to insert comment']);
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
        "https://via.placeholder.com/300";
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
function time_elapsed_string($datetime, $full = false) {
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

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
// Rest of your existing code...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($community) ? htmlspecialchars($community['name']) : 'Community'; ?> | Book Heaven</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Rest of your HTML remains the same -->
    <?php include_once("../../header.php"); ?>
    
    <main>
        <aside>
            <div class="community-header">
                <?php if (isset($community)): ?>
                    <div class="community-info">
                        <h1><?php echo htmlspecialchars($community['name']); ?></h1>
                        <p><?php echo htmlspecialchars($community['description']); ?></p>
                        <?php if ($community['cover_image_url']): ?>
                            <img src="<?php echo $community['cover_image_url']; ?>" alt="Community Cover" style="width:100%; border-radius:8px; margin-top:10px;">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <h3>Community Members</h3>
            <div class="member-list">
                <?php if ($members_result->num_rows > 0): ?>
                    <?php while ($member = $members_result->fetch_assoc()): ?>
                        <div class="member-item">
                            <div class="member-info">
                                <span class="member-status <?php echo $member['role'] === 'admin' ? '' : 'inactive'; ?>"></span>
                                <img src="<?php echo !empty($member['user_profile']) ? '/BookHeaven2.0/' . htmlspecialchars($member['user_profile']) : 'https://via.placeholder.com/40'; ?>" 
                                     alt="<?php echo htmlspecialchars($member['username']); ?>" 
                                     class="member-avatar">
                                <div>
                                    <div><?php echo htmlspecialchars($member['username']); ?></div>
                                    <small><?php echo ucfirst($member['role']); ?></small>
                                </div>
                            </div>
                            <button class="message-btn" onclick="window.location.href='messages.php?user_id=<?php echo $member['user_id']; ?>'">
                                <i class="fas fa-envelope"></i> Message
                            </button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No other members found</p>
                <?php endif; ?>
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
                                    <img src="<?php echo $post['avatar']; ?>" alt="<?php echo $post['user']; ?>" class="user-avatar">
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
                                    <button class="action-btn share-btn">
                                        <i class="fas fa-share-alt"></i>
                                        <span>Share</span>
                                    </button>
                                </div>
                                
                                <div class="comment-section" id="comments-<?php echo $post['id']; ?>">
                                    <form class="comment-form" method="post" onsubmit="return addComment(event, <?php echo $post['id']; ?>)">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <input type="text" name="content" class="comment-input" placeholder="Write a comment..." required>
                                        <button type="submit" name="add_comment" class="comment-submit">Post</button>
                                    </form>
                                    
                                    <div class="comments-list" id="comments-list-<?php echo $post['id']; ?>">
                                        <?php foreach ($post['comments'] as $comment): ?>
                                            <div class="comment-item">
                                                <img src="<?php echo $comment['avatar']; ?>" alt="<?php echo $comment['user']; ?>" class="comment-avatar">
                                                <div class="comment-content">
                                                    <div class="comment-user"><?php echo $comment['user']; ?></div>
                                                    <div class="comment-text"><?php echo $comment['content']; ?></div>
                                                    <div class="comment-time"><?php echo $comment['time']; ?></div>
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
        function addComment(event, postId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const commentInput = form.querySelector('.comment-input');
            const commentsList = document.getElementById('comments-list-' + postId);
            const commentBtn = document.querySelector(`#post-${postId} .comment-btn span`);
            
            // Show loading state
            const submitBtn = form.querySelector('.comment-submit');
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = 'Posting...';
            submitBtn.disabled = true;
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Create a new comment element with the server response
                    const newComment = document.createElement('div');
                    newComment.className = 'comment-item';
                    newComment.innerHTML = `
                        <img src="${data.comment.avatar}" alt="${data.comment.user}" class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-user">${data.comment.user}</div>
                            <div class="comment-text">${data.comment.content}</div>
                            <div class="comment-time">${data.comment.time}</div>
                        </div>
                    `;
                    
                    // Add the new comment to the top of the comments list
                    if (commentsList.firstChild) {
                        commentsList.insertBefore(newComment, commentsList.firstChild);
                    } else {
                        commentsList.appendChild(newComment);
                    }
                    
                    commentInput.value = '';
                    
                    // Update comment count
                    const currentCount = parseInt(commentBtn.textContent.match(/\d+/)[0] || 0);
                    commentBtn.textContent = `${currentCount + 1} Comments`;
                    
                    // Ensure comment section is visible
                    document.getElementById('comments-' + postId).style.display = 'block';
                } else {
                    alert(data.error || 'Failed to post comment');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error posting comment');
            })
            .finally(() => {
                submitBtn.textContent = originalBtnText;
                submitBtn.disabled = false;
            });
            
            return false;
        }
        
        // Initialize comment sections as hidden
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.comment-section').forEach(section => {
                section.style.display = 'none';
            });
        });
    </script>
</body>
</html>