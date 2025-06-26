<?php
// Start session and include database connection
require_once('../db_connection.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: authentication.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Function to get subscription status
function getSubscriptionStatus($end_date) {
    $current_date = new DateTime();
    $end_date = new DateTime($end_date);
    return ($current_date < $end_date) ? 'active' : 'expired';
}

// Fetch user's active subscriptions
$active_subscriptions = [];
$expired_subscriptions = [];

$subscription_query = "SELECT us.user_subscription_id, us.subscription_plan_id, us.start_date, us.end_date, 
                      us.status, us.available_audio, us.available_rent_book, us.used_audio_book, us.used_rent_book,
                      sp.plan_name, sp.price, sp.validity_days, sp.book_quantity, sp.audiobook_quantity
                      FROM user_subscriptions us
                      JOIN subscription_plans sp ON us.subscription_plan_id = sp.plan_id
                      WHERE us.user_id = ?";
$stmt = $conn->prepare($subscription_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $status = getSubscriptionStatus($row['end_date']);
    
    if ($status === 'active') {
        $active_subscriptions[] = $row;
    } else {
        $expired_subscriptions[] = $row;
    }
}

// Calculate stats for the grid
$total_subscriptions = count($active_subscriptions) + count($expired_subscriptions);
$active_count = count($active_subscriptions);
$expired_count = count($expired_subscriptions);
$renew_needed = 0; // You can add logic to determine if renewal is needed

// Fetch user information for the sidebar
$user_query = "SELECT u.username, u.user_profile, u.create_time, ui.* 
              FROM users u 
              LEFT JOIN user_info ui ON u.user_id = ui.user_id 
              WHERE u.user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Close statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscriptions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
:root {
  --primary-color: #57abd2;
  --secondary-color: #f8f5fc;
  --accent-color: rgb(223, 219, 227);
  --text-color: #333;
  --light-purple: #e6d9f2;
  --dark-text: #212529;
  --light-text: #f8f9fa;
  --card-bg: #f8f9fa;
  --aside-bg: #f0f2f5;
  --nav-hover: #e0e0e0;
  --column-hover: #cee9ea;
}

.dark-mode {
  --primary-color: #57abd2;
  --secondary-color: #2d3748;
  --accent-color: #4a5568;
  --text-color: #f8f9fa;
  --light-purple: #4a5568;
  --dark-text: #f8f9fa;
  --light-text: #212529;
  --card-bg: #1a202c;
  --aside-bg: #1a202c;
  --nav-hover: #4a5568;
   --column-hover: #656565;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  transition: background-color 0.3s, color 0.3s;
}

body {
  background-color: var(--secondary-color);
  color: var(--text-color);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

main {
  display: flex;
  flex: 1;
  padding: 20px;
  gap: 20px;
}

aside {
  width: 280px;
  background-color: var(--aside-bg);
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.subscription_content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--accent-color);
  margin-bottom: 20px;
}

.user-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--primary-color);
}

.user-name {
  font-size: 1.2rem;
  font-weight: 600;
}

nav ul {
  list-style: none;
}

nav ul li a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 10px;
  border-radius: 8px;
  text-decoration: none;
  color: var(--text-color);
  margin-bottom: 5px;
  font-weight: 500;
}

nav ul li a:hover {
  background-color: var(--nav-hover);
}

nav ul li a.active {
  background-color: var(--primary-color);
  color: white;
}

nav ul li a i {
  width: 20px;
  text-align: center;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background-color: var(--card-bg);
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.stat-card h3 {
  font-size: 0.9rem;
  color: var(--primary-color);
  margin-bottom: 10px;
}

.stat-card p {
  font-size: 1.8rem;
  font-weight: 700;
}

.subscription-table {
  background-color: var(--card-bg);
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid var(--accent-color);
}

th {
  background-color: var(--primary-color);
  color: black;
  font-weight: 600;
}

tr:hover {
  background-color:var(--column-hover);
}

.btn {
  padding: 6px 12px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-view {
  background-color: var(--primary-color);
  color: white;
}

.btn-view:hover {
  background-color: #3d96c4;
}

/* Subscription Plan Cards */
.subscription-plan-card {
  background-color: var(--card-bg);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.plan-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--accent-color);
}

.plan-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--primary-color);
}

.plan-dates {
  font-size: 0.9rem;
  color: var(--text-color);
}

.plan-stats {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.plan-stat {
  background-color: var(--light-purple);
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 0.9rem;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 10px;
  margin-top: 15px;
}

.btn-add {
  background-color: #28a745;
  color: white;
  padding: 8px 15px;
  text-decoration: none;
  border-radius: 5px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.btn-add:hover {
  background-color: #218838;
}

.btn-add i {
  font-size: 0.9rem;
}

/* Tab Styles */
.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--accent-color);
  padding-bottom: 10px;
}

.tab-btn {
  padding: 8px 15px;
  cursor: pointer;
  text-decoration: none;
  color: var(--text-color);
  border-radius: 5px;
  font-weight: 500;
  transition: all 0.2s;
}

.tab-btn:hover {
  background-color: var(--nav-hover);
}

.tab-btn.active {
  background-color: var(--primary-color);
  color: white;
}

.tab-content {
  display: none;
}

.tab-content.active {
  display: block;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  main {
    flex-direction: column;
  }

  aside {
    width: 100%;
  }

  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }

  .plan-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  th,
  td {
    padding: 8px 10px;
    font-size: 0.9rem;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .btn-add {
    width: 100%;
    justify-content: center;
  }
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
                        <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wish List</a></li>
                        <li><a href="user_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                        <li><a href="user_subscription.php" class="active"><i class="fas fa-calendar-check"></i> My Subscription</a></li>
                        <li><a href="user_setting.php"><i class="fas fa-cog"></i> Setting</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </section>
        </aside>

        <div class="subscription_content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Subscriptions</h3>
                    <p><?php echo $total_subscriptions; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active</h3>
                    <p><?php echo $active_count; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Expired</h3>
                    <p><?php echo $expired_count; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Renew Needed</h3>
                    <p><?php echo $renew_needed; ?></p>
                </div>
            </div>

            <div class="tabs">
                <a href="#books" class="tab-btn active">Books</a>
                <a href="#audiobooks" class="tab-btn">Audiobooks</a>
            </div>

            <div id="books" class="tab-content active">
                <?php if (empty($active_subscriptions)): ?>
                    <div class="subscription-plan-card">
                        <p>You don't have any active subscriptions with books.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_subscriptions as $subscription): ?>
                        <div class="subscription-plan-card">
                            <div class="plan-header">
                                <div class="plan-title"><?php echo htmlspecialchars($subscription['plan_name']); ?> Plan</div>
                                <div class="plan-dates">
                                    <?php echo date('M d, Y', strtotime($subscription['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($subscription['end_date'])); ?>
                                </div>
                            </div>
                            
                            <div class="plan-stats">
                                <div class="plan-stat">
                                    <strong>Books Allowed:</strong> <?php echo $subscription['available_rent_book']; ?>
                                </div>
                                <div class="plan-stat">
                                    <strong>Books Used:</strong> <?php echo $subscription['used_rent_book'] ?? 0; ?>
                                </div>
                            </div>
                            
                            <?php 
                            // Fetch books for this subscription
                            $book_query = "SELECT rb.rent_book_id, rb.title, rb.writer, rb.genre, rb.language, rb.poster_url 
                                           FROM rent_books rb
                                           JOIN user_subscription_rent_book_access usrba ON rb.rent_book_id = usrba.rent_book_id
                                           WHERE usrba.user_subscription_id = ?";
                            $book_stmt = $conn->prepare($book_query);
                            $book_stmt->bind_param("i", $subscription['user_subscription_id']);
                            $book_stmt->execute();
                            $book_result = $book_stmt->get_result();
                            
                            if ($book_result->num_rows > 0): ?>
                                <div class="subscription-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Genre</th>
                                                <th>Language</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($book = $book_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['writer']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['genre']); ?></td>
                                                    <td><?php echo htmlspecialchars($book['language']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No books added to this subscription yet.</p>
                            <?php endif; ?>
                            
                            <div class="action-buttons">
                                <a href="book_add_to_subscription.php?plan_type=<?= urlencode(strtolower($subscription['plan_name'])) ?>&sub_id=<?= $subscription['user_subscription_id'] ?>" class="btn-add">
                                    <i class="fas fa-plus"></i> Add Book
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="audiobooks" class="tab-content">
                <?php if (empty($active_subscriptions)): ?>
                    <div class="subscription-plan-card">
                        <p>You don't have any active subscriptions with audiobooks.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_subscriptions as $subscription): ?>
                        <div class="subscription-plan-card">
                            <div class="plan-header">
                                <div class="plan-title"><?php echo htmlspecialchars($subscription['plan_name']); ?> Plan</div>
                                <div class="plan-dates">
                                    <?php echo date('M d, Y', strtotime($subscription['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($subscription['end_date'])); ?>
                                </div>
                            </div>
                            
                            <div class="plan-stats">
                                <div class="plan-stat">
                                    <strong>Audiobooks Allowed:</strong> <?php echo $subscription['available_audio']; ?>
                                </div>
                                <div class="plan-stat">
                                    <strong>Audiobooks Used:</strong> <?php echo $subscription['used_audio_book'] ?? 0; ?>
                                </div>
                            </div>
                            
                            <?php 
                            // Fetch audiobooks for this subscription
                            $audio_query = "SELECT ab.audiobook_id, ab.title, ab.writer, ab.genre, ab.language, ab.audio_url 
                                            FROM audiobooks ab
                                            JOIN user_subscription_audiobook_access usaaa ON ab.audiobook_id = usaaa.audiobook_id
                                            WHERE usaaa.user_subscription_id = ?";
                            $audio_stmt = $conn->prepare($audio_query);
                            $audio_stmt->bind_param("i", $subscription['user_subscription_id']);
                            $audio_stmt->execute();
                            $audio_result = $audio_stmt->get_result();
                            
                            if ($audio_result->num_rows > 0): ?>
                                <div class="subscription-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Genre</th>
                                                <th>Language</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($audio = $audio_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($audio['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($audio['writer']); ?></td>
                                                    <td><?php echo htmlspecialchars($audio['genre']); ?></td>
                                                    <td><?php echo htmlspecialchars($audio['language']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No audiobooks added to this subscription yet.</p>
                            <?php endif; ?>
                            
                            <div class="action-buttons">
                                <a href="audio_book_add_to_subscription.php?sub_id=<?= $subscription['user_subscription_id'] ?>&plan_type=<?= urlencode(strtolower($subscription['plan_name'])) ?>" class="btn-add">
                                    <i class="fas fa-headphones"></i> Add Audiobook
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include_once("../footer.php"); ?>

    <script>
        // Tab switching functionality
        document.querySelectorAll(".tab-btn").forEach(tab => {
            tab.addEventListener("click", function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and contents
                document.querySelectorAll(".tab-btn").forEach(t => t.classList.remove("active"));
                document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add("active");
                const target = this.getAttribute("href");
                document.querySelector(target).classList.add("active");
            });
        });
    </script>
</body>
</html>