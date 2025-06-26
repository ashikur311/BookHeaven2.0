<?php
// Start session and include necessary files
session_start();
include_once("../../header.php");

// Simulated database data
$communities = [
    ['id' => 1, 'name' => 'Web Developers', 'members' => 12453, 'image' => 'https://via.placeholder.com/150'],
    ['id' => 2, 'name' => 'Design Enthusiasts', 'members' => 8765, 'image' => 'https://via.placeholder.com/150'],
    ['id' => 3, 'name' => 'Tech Startups', 'members' => 5432, 'image' => 'https://via.placeholder.com/150'],
    ['id' => 4, 'name' => 'Digital Marketing', 'members' => 7890, 'image' => 'https://via.placeholder.com/150'],
    ['id' => 5, 'name' => 'AI & Machine Learning', 'members' => 15678, 'image' => 'https://via.placeholder.com/150'],
];

$posts = [
    [
        'id' => 1,
        'community_id' => 1,
        'user' => 'John Doe',
        'avatar' => 'https://via.placeholder.com/50',
        'time' => '2 hours ago',
        'content' => 'Just launched my new portfolio website! Check it out and let me know what you think.',
        'likes' => 24,
        'comments' => [
            ['user' => 'Jane Smith', 'avatar' => 'https://via.placeholder.com/50', 'time' => '1 hour ago', 'content' => 'Looks amazing! Love the minimalist design.'],
            ['user' => 'Mike Johnson', 'avatar' => 'https://via.placeholder.com/50', 'time' => '45 minutes ago', 'content' => 'The animations are smooth. What framework did you use?']
        ]
    ],
    [
        'id' => 2,
        'community_id' => 2,
        'user' => 'Sarah Williams',
        'avatar' => 'https://via.placeholder.com/50',
        'time' => '5 hours ago',
        'content' => 'Does anyone have recommendations for color palette tools? I need something that helps with accessibility contrast ratios.',
        'likes' => 12,
        'comments' => [
            ['user' => 'Alex Chen', 'avatar' => 'https://via.placeholder.com/50', 'time' => '4 hours ago', 'content' => 'I highly recommend Coolors.co - they have great accessibility features built in.']
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Dashboard</title>
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
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            max-width: 350px;
        }

        .dashboard-content {
            flex: 3;
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            overflow-y: auto;
        }

        .community-list {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .community-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 10px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .community-item:hover {
            background-color: var(--hover-bg);
            transform: translateY(-2px);
        }

        .community-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }

        .community-info {
            flex: 1;
        }

        .community-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .community-members {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .message-btn {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            margin-left: 10px;
        }

        .message-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Posts Section */
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

        .post-actions {
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
            padding: 10px;
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
        }

        .comment-content {
            flex: 1;
            background-color: var(--aside-bg);
            padding: 10px 15px;
            border-radius: 15px;
        }

        .comment-user {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .comment-text {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .comment-time {
            font-size: 0.7rem;
            color: var(--text-light);
            margin-top: 5px;
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
            .post-actions {
                justify-content: space-between;
            }

            .action-btn {
                margin-right: 0;
            }
        }

        @media (max-width: 576px) {
            .post-header, .comment-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-avatar, .comment-avatar {
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
        }
    </style>
</head>
<body>
    <?php include_once("../../header.php"); ?>
    <main>
<aside>
    <h2>Community Members</h2>
    <div class="member-list">
        <!-- Demo Community Data -->
        <?php
        $community = [
            'members_list' => [
                ['id' => 1, 'name' => 'John Doe', 'active' => true],
                ['id' => 2, 'name' => 'Jane Smith', 'active' => false],
                ['id' => 3, 'name' => 'Alice Johnson', 'active' => true],
            ]
        ];

        // Loop through the members and display them
        foreach ($community['members_list'] as $member):
        ?>
            <div class="member-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: <?php echo $member['active'] ? 'var(--success-color)' : 'var(--text-light)'; ?>"></span>
                    <div class="member-name"><?php echo $member['name']; ?></div>
                </div>
                <button class="message-btn" onclick="window.location.href='messages.php?member_id=<?php echo $member['id']; ?>'" style="margin-left: 10px;">
                    <i class="fas fa-envelope"></i> Message
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</aside>


        
        <div class="dashboard-content">
            <h1>Community Feed</h1>
            
            <div class="posts-container">
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
                    <div class="post-actions">
                        <button class="action-btn like-btn" onclick="toggleLike(this)">
                            <i class="fas fa-thumbs-up"></i>
                            <span><?php echo $post['likes']; ?> Likes</span>
                        </button>
                        <button class="action-btn comment-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                            <i class="fas fa-comment"></i>
                            <span><?php echo count($post['comments']); ?> Comments</span>
                        </button>
                        <button class="action-btn share-btn">
                            <i class="fas fa-share-alt"></i>
                            <span>Share</span>
                        </button>
                    </div>
                    
                    <div class="comment-section" id="comments-<?php echo $post['id']; ?>">
                        <form class="comment-form" onsubmit="addComment(event, <?php echo $post['id']; ?>)">
                            <input type="text" class="comment-input" placeholder="Write a comment..." required>
                            <button type="submit" class="comment-submit">Post</button>
                        </form>
                        
                        <div class="comments-list">
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
            </div>
        </div>
    </main>
    
    <?php include_once("../../footer.php");?>
    
    <script>
        // Toggle like button
        function toggleLike(button) {
            button.classList.toggle('liked');
            const likeCount = button.querySelector('span');
            const currentCount = parseInt(likeCount.textContent);
            likeCount.textContent = button.classList.contains('liked') ? 
                (currentCount + 1) + ' Likes' : 
                (currentCount - 1) + ' Likes';
        }
        
        // Toggle comments section
        function toggleComments(postId) {
            const commentSection = document.getElementById('comments-' + postId);
            commentSection.style.display = commentSection.style.display === 'block' ? 'none' : 'block';
        }
        
        // Add new comment
        function addComment(event, postId) {
            event.preventDefault();
            const commentInput = event.target.querySelector('.comment-input');
            const commentText = commentInput.value.trim();
            
            if (commentText) {
                const commentsList = document.querySelector('#comments-' + postId + ' .comments-list');
                
                // Create new comment element
                const newComment = document.createElement('div');
                newComment.className = 'comment-item';
                newComment.innerHTML = `
                    <img src="https://via.placeholder.com/50" alt="You" class="comment-avatar">
                    <div class="comment-content">
                        <div class="comment-user">You</div>
                        <div class="comment-text">${commentText}</div>
                        <div class="comment-time">Just now</div>
                    </div>
                `;
                
                // Add to top of comments list
                commentsList.insertBefore(newComment, commentsList.firstChild);
                
                // Update comment count
                const commentBtn = document.querySelector('#post-' + postId + ' .comment-btn span');
                const currentCount = parseInt(commentBtn.textContent);
                commentBtn.textContent = (currentCount + 1) + ' Comments';
                
                // Clear input
                commentInput.value = '';
            }
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