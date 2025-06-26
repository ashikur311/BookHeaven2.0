<?php
session_start();
require_once("../../db_connection.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
date_default_timezone_set('Asia/Dhaka');
$user_id = $_SESSION['user_id'];

// Initialize all variables
$community_id = isset($_GET['c_id']) ? intval($_GET['c_id']) : 0;
$recipient_id = isset($_GET['u_id']) ? intval($_GET['u_id']) : 0;
$community = [];
$community_members = [];
$current_recipient = null;
$messages = [];

// Get current community details
if ($community_id > 0) {
    // Get community info
    $stmt = $conn->prepare("SELECT * FROM communities WHERE community_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $community_id);
        if ($stmt->execute()) {
            $community = $stmt->get_result()->fetch_assoc();
        } else {
            error_log("Error executing community query: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Error preparing community query: " . $conn->error);
    }

    if ($community) {
        // Get all members of this community with their online status
        $stmt = $conn->prepare("
            SELECT 
                u.user_id, 
                u.username, 
                u.user_profile, 
                cm.role, 
                cm.status as member_status,
                IFNULL(ua.status, 'offline') as user_status,
                (SELECT COUNT(*) FROM community_messages 
                 WHERE community_id = ? AND receiver_id = ? AND sender_id = u.user_id AND status = 'sent') as unread_count
            FROM community_members cm
            JOIN users u ON cm.user_id = u.user_id
            LEFT JOIN (
                SELECT user_id, status 
                FROM user_activities 
                WHERE user_id IN (
                    SELECT user_id FROM community_members WHERE community_id = ?
                )
                AND auth_id IN (
                    SELECT MAX(auth_id) FROM user_activities 
                    WHERE user_id IN (
                        SELECT user_id FROM community_members WHERE community_id = ?
                    )
                    GROUP BY user_id
                )
            ) ua ON u.user_id = ua.user_id
            WHERE cm.community_id = ? AND cm.status = 'active'
            ORDER BY cm.role DESC, u.username ASC
        ");
        
        if ($stmt) {
            $stmt->bind_param("iiiii", $community_id, $user_id, $community_id, $community_id, $community_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $community_members = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                error_log("Error executing members query: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("Error preparing members query: " . $conn->error);
        }

        // Validate recipient is a member of this community
        if ($recipient_id > 0) {
            foreach ($community_members as $member) {
                if ($member['user_id'] == $recipient_id) {
                    $current_recipient = $member;
                    break;
                }
            }
        }

        // If no valid recipient, use the first member (excluding current user)
        if (!$current_recipient) {
            foreach ($community_members as $member) {
                if ($member['user_id'] != $user_id) {
                    $current_recipient = $member;
                    $recipient_id = $member['user_id'];
                    break;
                }
            }
        }

        // Get messages between current user and recipient in this community
        if ($current_recipient) {
            $stmt = $conn->prepare("
                SELECT 
                    m.message_id, 
                    m.sender_id, 
                    m.content, 
                    m.created_at,
                    m.status,
                    u.username as sender_name,
                    u.user_profile as sender_avatar
                FROM community_messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.community_id = ?
                AND (
                    (m.sender_id = ? AND m.receiver_id = ?) OR 
                    (m.sender_id = ? AND m.receiver_id = ?)
                )
                ORDER BY m.created_at ASC
            ");
            
            if ($stmt) {
                $stmt->bind_param("iiiii", $community_id, $user_id, $recipient_id, $recipient_id, $user_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $messages = $result->fetch_all(MYSQLI_ASSOC);
                } else {
                    error_log("Error executing messages query: " . $stmt->error);
                }
                $stmt->close();
            } else {
                error_log("Error preparing messages query: " . $conn->error);
            }

            // Mark messages as read
            $stmt = $conn->prepare("
                UPDATE community_messages 
                SET status = 'read' 
                WHERE community_id = ? AND sender_id = ? AND receiver_id = ? AND status = 'sent'
            ");
            
            if ($stmt) {
                $stmt->bind_param("iii", $community_id, $recipient_id, $user_id);
                if (!$stmt->execute()) {
                    error_log("Error updating message status: " . $stmt->error);
                }
                $stmt->close();
            } else {
                error_log("Error preparing status update query: " . $conn->error);
            }
        }
    }
}

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $community_id > 0 && $current_recipient) {
    $message_content = trim($_POST['message']);

    if (!empty($message_content)) {
        $stmt = $conn->prepare("
            INSERT INTO community_messages 
            (community_id, sender_id, receiver_id, content, is_group_message, status, created_at)
            VALUES (?, ?, ?, ?, 0, 'sent', NOW())
        ");
        
        if ($stmt) {
            $stmt->bind_param("iiis", $community_id, $user_id, $current_recipient['user_id'], $message_content);
            if (!$stmt->execute()) {
                error_log("Error sending message: " . $stmt->error);
            }
            $stmt->close();
            
            // Return JSON response for AJAX
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success']);
                exit();
            } else {
                // Redirect to avoid form resubmission
                header("Location: messages.php?u_id=" . $current_recipient['user_id'] . "&c_id=" . $community_id);
                exit();
            }
        } else {
            error_log("Error preparing message insert query: " . $conn->error);
        }
    }
}

// Handle AJAX request for new messages
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_messages' && $community_id > 0 && $recipient_id > 0) {
    $last_message_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    $stmt = $conn->prepare("
        SELECT 
            m.message_id, 
            m.sender_id, 
            m.content, 
            m.created_at,
            m.status,
            u.username as sender_name,
            u.user_profile as sender_avatar
        FROM community_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.community_id = ?
        AND (
            (m.sender_id = ? AND m.receiver_id = ?) OR 
            (m.sender_id = ? AND m.receiver_id = ?)
        )
        AND m.message_id > ?
        ORDER BY m.created_at ASC
    ");
    
    $new_messages = [];
    if ($stmt) {
        $stmt->bind_param("iiiiii", $community_id, $user_id, $recipient_id, $recipient_id, $user_id, $last_message_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $new_messages = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
    
    // Mark new messages as read
    if (!empty($new_messages)) {
        $stmt = $conn->prepare("
            UPDATE community_messages 
            SET status = 'read' 
            WHERE community_id = ? AND sender_id = ? AND receiver_id = ? AND status = 'sent'
        ");
        
        if ($stmt) {
            $stmt->bind_param("iii", $community_id, $recipient_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($new_messages);
    exit();
}

// Handle AJAX request for unread counts
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_unread_counts' && $community_id > 0) {
    $unread_counts = [];
    
    $stmt = $conn->prepare("
        SELECT 
            sender_id as user_id,
            COUNT(*) as unread_count
        FROM community_messages
        WHERE community_id = ? AND receiver_id = ? AND status = 'sent'
        GROUP BY sender_id
    ");
    
    if ($stmt) {
        $stmt->bind_param("ii", $community_id, $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $unread_counts = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode($unread_counts);
    exit();
}

// Function to format time display
function formatMessageTime($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    } elseif ($diff < 86400) {
        return date('g:i A', $time);
    } else {
        return date('M j, g:i A', $time);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($community['name']) ? htmlspecialchars($community['name']) : 'Community Messages'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/BookHeaven2.0/css/messages.css">
</head>
<body>
    <?php include_once("../../header.php"); ?>
    <main>
        <div class="members-sidebar">
            <div class="community-header">
                <button class="back-btn"
                    onclick="window.location.href='community_dashboard.php?id=<?php echo $community_id; ?>'">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <?php if (isset($community['community_id'])): ?>
                    <div class="community-info">
                        <?php if (!empty($community['cover_image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($community['cover_image_url']); ?>"
                                alt="<?php echo htmlspecialchars($community['name']); ?>" class="community-image">
                        <?php else: ?>
                            <div class="community-image"
                                style="background-color: #<?php echo isset($community['name']) ? substr(md5($community['name']), 0, 6) : 'd41d8c'; ?>; 
                                display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                <?php echo isset($community['name']) ? substr($community['name'], 0, 2) : 'CM'; ?>
                            </div>
                        <?php endif; ?>
                        <div class="community-title">
                            <?php echo isset($community['name']) ? htmlspecialchars($community['name']) : 'Community'; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="members-list" id="membersList">
                <?php if (!empty($community_members)): ?>
                    <?php foreach ($community_members as $member): ?>
                        <?php if ($member['user_id'] == $user_id) continue; ?>
                        
                        <?php
                        // Get last message with this member
                        $stmt = $conn->prepare("
                            SELECT content, created_at, sender_id 
                            FROM community_messages 
                            WHERE community_id = ? 
                            AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                            ORDER BY created_at DESC 
                            LIMIT 1
                        ");
                        if ($stmt) {
                            $stmt->bind_param("iiiii", $community_id, $user_id, $member['user_id'], $member['user_id'], $user_id);
                            if ($stmt->execute()) {
                                $last_message = $stmt->get_result()->fetch_assoc();
                            } else {
                                error_log("Error fetching last message: " . $stmt->error);
                            }
                            $stmt->close();
                        }
                        ?>
                        <div class="member-item <?php echo ($current_recipient && $member['user_id'] == $current_recipient['user_id']) ? 'active' : ''; ?>"
                            data-user-id="<?php echo $member['user_id']; ?>"
                            onclick="window.location.href='messages.php?u_id=<?php echo $member['user_id']; ?>&c_id=<?php echo $community_id; ?>'">
                            <?php if (!empty($member['user_profile'])): ?>
                                <img src="/BookHeaven2.0/<?php echo htmlspecialchars($member['user_profile']); ?>"
                                    alt="<?php echo htmlspecialchars($member['username']); ?>" class="member-avatar">
                            <?php else: ?>
                                <div class="member-avatar"
                                    style="background-color: #<?php echo substr(md5($member['username']), 0, 6); ?>; 
                                    display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    <?php echo substr($member['username'], 0, 2); ?>
                                </div>
                            <?php endif; ?>
                            <div class="member-info">
                                <div class="member-name">
                                    <?php echo htmlspecialchars($member['username']); ?>
                                    <?php if ($last_message): ?>
                                        <span class="member-time"><?php echo formatMessageTime($last_message['created_at']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($last_message): ?>
                                    <div class="member-last-message">
                                        <?php
                                        $prefix = ($last_message['sender_id'] == $user_id) ? 'You: ' : '';
                                        $preview = htmlspecialchars(substr($last_message['content'], 0, 30));
                                        if (strlen($last_message['content']) > 30) {
                                            $preview .= '...';
                                        }
                                        echo $prefix . $preview;
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($member['unread_count'] > 0): ?>
                                <div class="unread-count" id="unreadCount-<?php echo $member['user_id']; ?>"><?php echo $member['unread_count']; ?></div>
                            <?php endif; ?>
                            <div class="member-status-indicator <?php echo strtolower($member['user_status']); ?>"></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-members">No members found in this community</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($current_recipient): ?>
            <div class="chat-container">
                <div class="chat-header">
                    <div class="recipient-info">
                        <?php if (!empty($current_recipient['user_profile'])): ?>
                            <img src="/BookHeaven2.0/<?php echo htmlspecialchars($current_recipient['user_profile']); ?>"
                                alt="<?php echo htmlspecialchars($current_recipient['username']); ?>" class="recipient-avatar">
                        <?php else: ?>
                            <div class="recipient-avatar"
                                style="background-color: #<?php echo substr(md5($current_recipient['username']), 0, 6); ?>; 
                                display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                <?php echo substr($current_recipient['username'], 0, 2); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="recipient-name"><?php echo htmlspecialchars($current_recipient['username']); ?></div>
                            <div class="recipient-status">
                                <span class="status-indicator <?php echo strtolower($current_recipient['user_status']); ?>"></span>
                                <?php echo ucfirst($current_recipient['user_status']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="messages-container" id="messagesContainer">
                    <?php foreach ($messages as $message): ?>
                        <?php
                        // Check if sender_id exists and compare with current user
                        $is_me = (isset($message['sender_id']) && $message['sender_id'] == $user_id);
                        ?>
                        <div class="message <?php echo $is_me ? 'message-me' : 'message-them'; ?>" data-message-id="<?php echo $message['message_id']; ?>">
                            <?php if (!$is_me && isset($message['sender_name'])): ?>
                                <div class="message-sender">
                                    <?php if (!empty($message['sender_avatar'])): ?>
                                        <img src="/BookHeaven2.0/<?php echo htmlspecialchars($message['sender_avatar']); ?>"
                                            alt="<?php echo htmlspecialchars($message['sender_name']); ?>"
                                            class="message-sender-avatar">
                                    <?php else: ?>
                                        <div class="message-sender-avatar"
                                            style="background-color: #<?php echo isset($message['sender_name']) ? substr(md5($message['sender_name']), 0, 6) : 'd41d8c'; ?>; 
                                            display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.7rem;">
                                            <?php echo isset($message['sender_name']) ? substr($message['sender_name'], 0, 2) : '??'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?php echo isset($message['content']) ? htmlspecialchars($message['content']) : ''; ?></div>
                            <div class="message-time">
                                <?php echo isset($message['created_at']) ? formatMessageTime($message['created_at']) : ''; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" class="message-input-container" id="messageForm">
                    <input type="text" name="message" class="message-input" placeholder="Type a message..." required>
                    <button type="submit" class="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="chat-container" style="display: flex; align-items: center; justify-content: center;">
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-comments" style="font-size: 3rem; color: var(--text-light); margin-bottom: 15px;"></i>
                    <h3>Select a member to start chatting</h3>
                    <p>Choose someone from the member list to begin your conversation</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include_once("../../footer.php"); ?>

    <script>
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            checkForUnreadCounts();
            // Start checking for new messages and unread counts
            checkForNewMessages();
            // checkForUnreadCounts();
        });

        // Handle message submission with AJAX
        const messageForm = document.getElementById('messageForm');
        if (messageForm) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const messageInput = this.querySelector('.message-input');
                const messageText = messageInput.value.trim();

                if (messageText) {
                    const formData = new FormData(this);
                    formData.append('ajax', 'true');

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            messageInput.value = '';
                            // Check for new messages immediately after sending
                           
                            // Also update unread counts
                            checkForUnreadCounts();
                             checkForNewMessages();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        }

        // Function to check for new messages
        function checkForNewMessages() {
            const messagesContainer = document.getElementById('messagesContainer');
            if (!messagesContainer) return;

            // Get the last message ID
            const lastMessage = messagesContainer.querySelector('.message:last-child');
            const lastMessageId = lastMessage ? parseInt(lastMessage.dataset.messageId) : 0;
            
            // Get current community and recipient IDs from URL
            const urlParams = new URLSearchParams(window.location.search);
            const communityId = urlParams.get('c_id');
            const recipientId = urlParams.get('u_id');

            if (!communityId || !recipientId) return;

            // Fetch new messages
            fetch(`messages.php?ajax=get_messages&c_id=${communityId}&u_id=${recipientId}&last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(messages => {
                    if (messages.length > 0) {
                        // Append new messages
                        messages.forEach(message => {
                            const isMe = message.sender_id == <?php echo $user_id; ?>;
                            const messageTime = formatMessageTime(message.created_at);
                            
                            let messageHtml = `
                                <div class="message ${isMe ? 'message-me' : 'message-them'}" data-message-id="${message.message_id}">
                            `;
                            
                            if (!isMe) {
                                let avatarHtml;
                                if (message.sender_avatar) {
                                    avatarHtml = `<img src="/BookHeaven2.0/${message.sender_avatar}" alt="${message.sender_name}" class="message-sender-avatar">`;
                                } else {
                                    const bgColor = '#' + md5(message.sender_name).substr(0, 6);
                                    avatarHtml = `
                                        <div class="message-sender-avatar" style="background-color: ${bgColor}; 
                                            display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.7rem;">
                                            ${message.sender_name.substr(0, 2)}
                                        </div>
                                    `;
                                }
                                
                                messageHtml += `
                                    <div class="message-sender">
                                        ${avatarHtml}
                                    </div>
                                `;
                            }
                            
                            messageHtml += `
                                    <div class="message-content">${escapeHtml(message.content)}</div>
                                    <div class="message-time">${messageTime}</div>
                                </div>
                            `;
                            
                            messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
                        });
                        
                        // Scroll to bottom
                        scrollToBottom();
                        
                        // Update unread counts
                        checkForUnreadCounts();
                    }
                    
                    // Schedule next check
                    setTimeout(checkForNewMessages, 3000);
                })
                .catch(error => {
                    console.error('Error fetching messages:', error);
                    // Retry after delay even if error occurs
                    setTimeout(checkForNewMessages, 3000);
                });
        }

        // Function to check for unread message counts
        function checkForUnreadCounts() {
            const urlParams = new URLSearchParams(window.location.search);
            const communityId = urlParams.get('c_id');
            
            if (!communityId) return;

            fetch(`messages.php?ajax=get_unread_counts&c_id=${communityId}`)
                .then(response => response.json())
                .then(counts => {
                    counts.forEach(count => {
                        const unreadCountEl = document.getElementById(`unreadCount-${count.user_id}`);
                        
                        if (count.unread_count > 0) {
                            if (!unreadCountEl) {
                                // Create new unread count element if it doesn't exist
                                const memberItem = document.querySelector(`.member-item[data-user-id="${count.user_id}"]`);
                                if (memberItem) {
                                    memberItem.insertAdjacentHTML('beforeend', 
                                        `<div class="unread-count" id="unreadCount-${count.user_id}">${count.unread_count}</div>`);
                                }
                            } else {
                                // Update existing unread count
                                unreadCountEl.textContent = count.unread_count;
                            }
                        } else if (unreadCountEl) {
                            // Remove unread count if it exists but count is 0
                            unreadCountEl.remove();
                        }
                    });
                    
                    // Schedule next check
                    setTimeout(checkForUnreadCounts, 5000);
                })
                .catch(error => {
                    console.error('Error fetching unread counts:', error);
                    // Retry after delay even if error occurs
                    setTimeout(checkForUnreadCounts, 2000);
                });
        }

        // Helper function to format message time (similar to PHP function)
        function formatMessageTime(datetime) {
            const now = new Date();
            const messageTime = new Date(datetime);
            const diff = (now - messageTime) / 1000; // difference in seconds
            
            if (diff < 60) {
                return 'Just now';
            } else if (diff < 3600) {
                return Math.floor(diff / 60) + ' min ago';
            } else if (diff < 86400) {
                return messageTime.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'});
            } else {
                return messageTime.toLocaleDateString([], {month: 'short', day: 'numeric'}) + 
                       ', ' + messageTime.toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'});
            }
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Simple MD5 function for avatar colors (just for frontend, doesn't need to be secure)
        function md5(string) {
            let hash = 0;
            for (let i = 0; i < string.length; i++) {
                const char = string.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return Math.abs(hash).toString(16).substr(0, 6);
        }
    </script>
</body>
</html>