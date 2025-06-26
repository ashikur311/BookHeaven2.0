<?php
// Start session and include necessary files
session_start();
include_once("../../header.php");

// Simulated data
$currentCommunity = [
    'id' => 1,
    'name' => 'Web Developers',
    'image' => 'https://via.placeholder.com/150'
];

$communityMembers = [
    ['id' => 1, 'name' => 'John Doe', 'avatar' => 'https://via.placeholder.com/50', 'last_message' => 'Hey, how are you?', 'time' => '2 hours ago', 'unread' => 3],
    ['id' => 2, 'name' => 'Jane Smith', 'avatar' => 'https://via.placeholder.com/50', 'last_message' => 'Can you review my code?', 'time' => '1 day ago', 'unread' => 0],
    ['id' => 3, 'name' => 'Mike Johnson', 'avatar' => 'https://via.placeholder.com/50', 'last_message' => 'Meeting at 3pm', 'time' => '3 days ago', 'unread' => 0],
    ['id' => 4, 'name' => 'Sarah Williams', 'avatar' => 'https://via.placeholder.com/50', 'last_message' => 'Thanks for the help!', 'time' => '1 week ago', 'unread' => 0],
];

$currentRecipient = [
    'id' => 1,
    'name' => 'John Doe',
    'avatar' => 'https://via.placeholder.com/50',
    'status' => 'online'
];

$messages = [
    ['sender' => 'John Doe', 'avatar' => 'https://via.placeholder.com/50', 'content' => 'Hey there!', 'time' => '10:30 AM', 'is_me' => false],
    ['sender' => 'You', 'avatar' => 'https://via.placeholder.com/50', 'content' => 'Hi John! How are you?', 'time' => '10:32 AM', 'is_me' => true],
    ['sender' => 'John Doe', 'avatar' => 'https://via.placeholder.com/50', 'content' => 'I\'m good, thanks! Working on that new project we discussed.', 'time' => '10:33 AM', 'is_me' => false],
    ['sender' => 'You', 'avatar' => 'https://via.placeholder.com/50', 'content' => 'Great! How\'s it coming along?', 'time' => '10:35 AM', 'is_me' => true],
    ['sender' => 'John Doe', 'avatar' => 'https://via.placeholder.com/50', 'content' => 'Pretty well. I could use your feedback on the UI design when you have time.', 'time' => '10:36 AM', 'is_me' => false],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Messages</title>
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
            height: calc(100vh - 120px);
            padding: 20px;
            gap: 20px;
        }

        .members-sidebar {
            flex: 1;
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            max-width: 350px;
            overflow: hidden;
        }

        .chat-container {
            flex: 3;
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Members List */
        .community-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .back-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--primary-color);
            cursor: pointer;
            margin-right: 10px;
            transition: var(--transition);
        }

        .back-btn:hover {
            color: var(--primary-dark);
        }

        .community-info {
            display: flex;
            align-items: center;
        }

        .community-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .community-title {
            font-weight: 600;
        }

        .members-list {
            flex: 1;
            overflow-y: auto;
        }

        .member-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .member-item:hover,
        .member-item.active {
            background-color: var(--hover-bg);
        }

        .member-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 600;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
        }

        .member-last-message {
            font-size: 0.8rem;
            color: var(--text-light);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .member-time {
            font-size: 0.7rem;
            color: var(--text-light);
        }

        .unread-count {
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            margin-left: 10px;
        }

        /* Chat Area */
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            background-color: var(--header-bg);
        }

        .recipient-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .recipient-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
        }

        .recipient-name {
            font-weight: 600;
        }

        .recipient-status {
            font-size: 0.8rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--success-color);
            margin-right: 5px;
        }

        .chat-actions {
            display: flex;
            gap: 15px;
        }

        .chat-action-btn {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .chat-action-btn:hover {
            color: var(--primary-color);
        }

        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: var(--aside-bg);
            display: flex;
            flex-direction: column;
        }

        .message {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            margin-bottom: 15px;
            position: relative;
            word-wrap: break-word;
        }

        .message-them {
            align-self: flex-start;
            background-color: var(--card-bg);
            border-top-left-radius: 5px;
            color: var(--text-color);
        }

        .message-me {
            align-self: flex-end;
            background-color: var(--primary-color);
            border-top-right-radius: 5px;
            color: white;
        }

        .message-sender {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .message-time {
            font-size: 0.7rem;
            color: var(--text-light);
            margin-top: 5px;
            text-align: right;
        }

        .message-me .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        .message-input-container {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            background-color: var(--card-bg);
            display: flex;
            align-items: center;
        }

        .message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            background-color: var(--card-bg);
            color: var(--text-color);
            transition: var(--transition);
            resize: none;
            max-height: 100px;
        }

        .message-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(87, 171, 210, 0.2);
        }

        .send-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            margin-left: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover {
            background-color: var(--primary-dark);
        }
.message-sender-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 8px;
}

.message-them {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
        /* Responsive Design */
        @media (max-width: 992px) {
            main {
                flex-direction: column;
                height: auto;
            }

            .members-sidebar {
                max-width: 100%;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 768px) {
            .message {
                max-width: 85%;
            }
        }

        @media (max-width: 576px) {

            .community-header,
            .recipient-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .community-image,
            .recipient-avatar {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .message-input-container {
                flex-direction: column;
                gap: 10px;
            }

            .message-input {
                width: 100%;
            }

            .send-btn {
                width: 100%;
                border-radius: 25px;
                height: auto;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <?php include_once("../../header.php"); ?>
    <main>
        <div class="members-sidebar">
            <div class="community-header">
                <button class="back-btn" onclick="window.location.href='community_dashboard.php'">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="community-info">
                    <img src="<?php echo $currentCommunity['image']; ?>" alt="<?php echo $currentCommunity['name']; ?>"
                        class="community-image">
                    <div class="community-title"><?php echo $currentCommunity['name']; ?></div>
                </div>
            </div>

            <div class="members-list">
                <?php foreach ($communityMembers as $member): ?>
                    <div class="member-item <?php echo $member['id'] == $currentRecipient['id'] ? 'active' : ''; ?>">
                        <img src="<?php echo $member['avatar']; ?>" alt="<?php echo $member['name']; ?>"
                            class="member-avatar">
                        <div class="member-info">
                            <div class="member-name">
                                <?php echo $member['name']; ?>
                                <span class="member-time"><?php echo $member['time']; ?></span>
                            </div>
                            <div class="member-last-message"><?php echo $member['last_message']; ?></div>
                        </div>
                        <?php if ($member['unread'] > 0): ?>
                            <div class="unread-count"><?php echo $member['unread']; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <div class="recipient-info">
                    <img src="<?php echo $currentRecipient['avatar']; ?>" alt="<?php echo $currentRecipient['name']; ?>"
                        class="recipient-avatar">
                    <div>
                        <div class="recipient-name"><?php echo $currentRecipient['name']; ?></div>
                        <div class="recipient-status">
                            <span class="status-indicator"></span>
                            <?php echo ucfirst($currentRecipient['status']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="messages-container" id="messagesContainer">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['is_me'] ? 'message-me' : 'message-them'; ?>">
                        <?php if (!$message['is_me']): ?>
                            <div class="message-sender">
                                <img src="<?php echo $message['sender_avatar']; ?>" alt="<?php echo $message['sender']; ?>"
                                    class="message-sender-avatar">
                            </div>
                        <?php endif; ?>
                        <div class="message-content"><?php echo $message['content']; ?></div>
                        <div class="message-time"><?php echo $message['time']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="message-input-container">
                <input type="text" class="message-input" placeholder="Type a message...">
                <button class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </main>

    <?php include_once("../../footer.php"); ?>

    <script>
        // Auto-scroll to bottom of messages
        document.addEventListener('DOMContentLoaded', function () {
            const messagesContainer = document.getElementById('messagesContainer');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });

        // Send message functionality
        const messageInput = document.querySelector('.message-input');
        const sendBtn = document.querySelector('.send-btn');

        function sendMessage() {
            const messageText = messageInput.value.trim();
            if (messageText) {
                const messagesContainer = document.getElementById('messagesContainer');

                // Create new message element
                const newMessage = document.createElement('div');
                newMessage.className = 'message message-me';
                newMessage.innerHTML = `
                    <div class="message-content">${messageText}</div>
                    <div class="message-time">Just now</div>
                `;

                // Add to messages container
                messagesContainer.appendChild(newMessage);

                // Clear input
                messageInput.value = '';

                // Scroll to bottom
                messagesContainer.scrollTop = messagesContainer.scrollHeight;

                // Simulate reply after 1 second
                setTimeout(() => {
                    const replyMessage = document.createElement('div');
                    replyMessage.className = 'message message-them';
                    replyMessage.innerHTML = `
                        <div class="message-sender"><?php echo $currentRecipient['name']; ?></div>
                        <div class="message-content">Thanks for your message! I'll get back to you soon.</div>
                        <div class="message-time">Just now</div>
                    `;
                    messagesContainer.appendChild(replyMessage);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }, 1000);
            }
        }

        // Send message on button click
        sendBtn.addEventListener('click', sendMessage);

        // Send message on Enter key
        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Member click functionality
        document.querySelectorAll('.member-item').forEach(item => {
            item.addEventListener('click', function () {
                // Remove active class from all members
                document.querySelectorAll('.member-item').forEach(i => {
                    i.classList.remove('active');
                });

                // Add active class to clicked member
                this.classList.add('active');

                // Here you would typically load the conversation with this member
                // For demo, we'll just show an alert
                const memberName = this.querySelector('.member-name').textContent.split('\n')[0];
                alert(`Loading conversation with ${memberName}`);
            });
        });
    </script>
</body>

</html>