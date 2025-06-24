<?php
include_once("../db_connection.php");
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: authentication.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Verify the user exists
$user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$user_check->bind_param('i', $user_id);
$user_check->execute();
$user_check->store_result();

if ($user_check->num_rows === 0) {
  // User doesn't exist in database
  session_destroy();
  header("Location: authentication.php");
  exit();
}

// Get joined events for the user
$joined_events_query = "SELECT e.* 
                        FROM events e
                        JOIN event_participants ep ON e.event_id = ep.event_id
                        WHERE ep.user_id = ? AND ep.status = 'registered'";

$stmt = $conn->prepare($joined_events_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$joined_events_result = $stmt->get_result();

// Get upcoming events
$upcoming_events_query = "SELECT e.*, 
                         (SELECT 1 FROM event_participants ep 
                          WHERE ep.event_id = e.event_id AND ep.user_id = ?) AS is_joined
                         FROM events e 
                         WHERE e.event_date > NOW() AND e.status = 'upcoming'
                         ORDER BY e.event_date";
$stmt = $conn->prepare($upcoming_events_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$upcoming_events_result = $stmt->get_result();

$events_data = [
  'joinedEvents' => $joined_events_result->fetch_all(MYSQLI_ASSOC),
  'upcomingEvents' => $upcoming_events_result->fetch_all(MYSQLI_ASSOC)
];

// Handle the join/leave action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'], $_POST['action'])) {
  // Verify CSRF token if you have one

  $event_id = (int) $_POST['event_id'];
  $action = $_POST['action']; // join or leave

  // Verify event exists
  $event_check = $conn->prepare("SELECT event_id FROM events WHERE event_id = ?");
  $event_check->bind_param('i', $event_id);
  $event_check->execute();
  $event_check->store_result();

  if ($event_check->num_rows === 0) {
    die("Invalid event ID");
  }

  if ($action === 'join') {
    // Check if the user is already registered
    $check_query = "SELECT * FROM event_participants WHERE user_id = ? AND event_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
      // User is not registered for the event, so join
      $join_query = "INSERT INTO event_participants (user_id, event_id, status) VALUES (?, ?, 'registered')";
      $stmt = $conn->prepare($join_query);
      $stmt->bind_param('ii', $user_id, $event_id);
      if ($stmt->execute()) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
      } else {
        die("Error joining event: " . $conn->error);
      }
    }
  } elseif ($action === 'leave') {
    // Remove user from event participation
    $leave_query = "DELETE FROM event_participants WHERE user_id = ? AND event_id = ?";
    $stmt = $conn->prepare($leave_query);
    $stmt->bind_param('ii', $user_id, $event_id);
    if ($stmt->execute()) {
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit;
    } else {
      die("Error leaving event: " . $conn->error);
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
  --important-bg: #fff8e1;
  --important-border: #ffd54f;
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
  --important-bg: #2d3748;
  --important-border: #57abd2;
}

/* Base styles */
body {
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  background-color: var(--aside-bg);
  color: var(--text-color);
  margin: 0;
  padding: 0;
  transition: background-color 0.3s, color 0.3s;
}

main {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
  background-color: var(--aside-bg);
}

h1, h2, h3 {
  color: var(--text-color);
}

.section-title {
  margin: 30px 0 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid var(--primary-color);
  color: var(--text-color);
}

/* Event cards */
.events-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 25px;
  margin-top: 20px;
}

.event-card {
  background-color: var(--card-bg);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: var(--card-shadow);
  transition: transform 0.3s, box-shadow 0.3s;
  position: relative;
  border: 1px solid var(--border-color);
}

.event-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.event-poster {
  width: 100%;
  height: 180px;
  object-fit: cover;
}

.event-content {
  padding: 15px;
}

.event-title {
  font-size: 1.2rem;
  margin: 0 0 10px;
  color: var(--text-color);
}

.event-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 15px;
  font-size: 0.9rem;
  color: var(--text-color);
}

.event-meta span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.event-meta i {
  color: var(--primary-color);
  width: 20px;
  text-align: center;
}

.event-details {
  font-size: 0.9rem;
  line-height: 1.5;
  margin-bottom: 15px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  color: var(--text-color);
}

/* Important info card */
.event-info-card {
  background-color: var(--important-bg);
  border: 2px solid var(--important-border);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.event-info-card p {
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
  color: var(--text-color);
  font-weight: 500;
}

.event-info-card p strong {
  color: var(--primary-color);
  font-weight: 600;
}

/* Buttons */
.join-btn {
  background-color: var(--primary-color);
  color: var(--light-text);
  border: none;
  padding: 8px 15px;
  border-radius: 5px;
  cursor: pointer;
  width: 100%;
  font-weight: bold;
  transition: background-color 0.2s;
}

.join-btn:hover {
  background-color: var(--primary-dark);
}

.joined-btn {
  background-color: var(--success-color);
  color: white;
}

.leave-btn {
  background-color: var(--danger-color);
  color: white;
}

.leave-btn:hover {
  background-color: #c82333;
}

.countdown {
  position: absolute;
  top: 15px;
  right: 15px;
  background-color: var(--primary-color);
  color: var(--light-text);
  padding: 5px 10px;
  border-radius: 5px;
  font-size: 0.8rem;
  font-weight: bold;
}

.no-events {
  text-align: center;
  padding: 40px;
  grid-column: 1 / -1;
  color: var(--text-color);
  opacity: 0.7;
}

.event-actions {
  display: flex;
  gap: 10px;
  margin-top: 15px;
}

.download-btn {
  background-color: var(--success-color);
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: background-color 0.2s;
}

.download-btn:hover {
  background-color: #218838;
}

.btn-link {
  background: none;
  border: none;
  color: var(--primary-color);
  text-decoration: underline;
  cursor: pointer;
  padding: 0;
  margin-left: 10px;
}

/* Alerts */
.alert {
  padding: 15px;
  margin: 0 auto 20px;
  border-radius: 4px;
  max-width: 800px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: background-color 0.3s, color 0.3s;
}

.alert.success {
  background-color: rgba(40, 167, 69, 0.2);
  color: var(--success-color);
  border: 1px solid var(--success-color);
}

.alert.error {
  background-color: rgba(220, 53, 69, 0.2);
  color: var(--danger-color);
  border: 1px solid var(--danger-color);
}

.alert .close-btn {
  background: none;
  border: none;
  color: inherit;
  font-size: 20px;
  cursor: pointer;
  padding: 0 0 0 10px;
}

/* Responsive styles */
@media (max-width: 768px) {
  .events-container {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  }
}

@media (max-width: 480px) {
  .events-container {
    grid-template-columns: 1fr;
  }

  main {
    padding: 10px;
  }

  .section-title {
    font-size: 1.3rem;
  }
  
  .event-actions {
    flex-direction: column;
  }
  
  .download-btn, .join-btn, .leave-btn {
    width: 100%;
  }
}
  </style>
</head>

<body>
  <?php include_once("../header.php"); ?>
  <main>
    <?php if (isset($_GET['joined'])): ?>
      <div class="alert success">
        Successfully joined the event! Your ticket has been sent to your email.
        <form action="download_ticket.php" method="POST" style="display: inline;">
          <input type="hidden" name="event_id" value="<?= htmlspecialchars($_GET['event_id'] ?? '') ?>">
          <button type="submit" class="btn-link">Resend Ticket</button>
        </form>
      </div>
    <?php elseif (isset($_GET['resent'])): ?>
      <div class="alert success">Ticket has been resent to your email.</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="alert error">
        <?php
        switch ($_GET['error']) {
          case 'email':
            echo "Failed to send ticket email. Please try again.";
            break;
          case 'pdf':
            echo "Failed to generate ticket. Please try again.";
            break;
          default:
            echo "An error occurred. Please try again.";
        }
        ?>
      </div>
    <?php endif; ?>

    <h1 class="section-title">My Events</h1>
    <div class="events-container" id="joined-events">
      <?php if (count($events_data['joinedEvents']) > 0): ?>
        <?php foreach ($events_data['joinedEvents'] as $event): ?>
          <div class="event-card" data-id="<?= htmlspecialchars($event['event_id']) ?>">
            <div class="countdown">Joined</div>
            <img src="/BookHeaven2.0/<?= htmlspecialchars($event['banner_url'] ?? 'assets/default_event.jpg') ?>"
              alt="<?= htmlspecialchars($event['name']) ?>" class="event-poster">
            <div class="event-content">
              <div class="event-info-card">
                <p><strong>Event:</strong> <?= htmlspecialchars($event['name']) ?></p>
                <p><strong>Venue:</strong> <?= htmlspecialchars($event['venue']) ?></p>
                <p><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($event['event_date'])) ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($event['description'] ?: 'No description available') ?></p>
              </div>
              <div class="event-actions">
                <form method="POST">
                  <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['event_id']) ?>">
                  <input type="hidden" name="action" value="leave">
                  <button class="join-btn leave-btn" type="submit">Leave Event</button>
                </form>
                <form action="download_ticket.php" method="POST">
                  <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['event_id']) ?>">
                  <button class="download-btn" type="submit">
                    <i class="fas fa-download"></i> Get Ticket
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-events">You haven't joined any events yet</div>
      <?php endif; ?>
    </div>

    <h1 class="section-title">Upcoming Events</h1>
    <div class="events-container" id="upcoming-events">
      <?php if (count($events_data['upcomingEvents']) > 0): ?>
        <?php foreach ($events_data['upcomingEvents'] as $event): ?>
          <?php if (empty($event['is_joined'])): ?>
            <div class="event-card" data-id="<?= htmlspecialchars($event['event_id']) ?>">
              <div class="countdown">
                <?= date_diff(new DateTime(), new DateTime($event['event_date']))->format('%d days %H hours') ?> left</div>
              <img src="/BookHeaven2.0/<?= htmlspecialchars($event['banner_url'] ?? 'assets/default_event.jpg') ?>"
                alt="<?= htmlspecialchars($event['name']) ?>" class="event-poster">
              <div class="event-content">
                <div class="event-info-card">
                  <p><strong>Event:</strong> <?= htmlspecialchars($event['name']) ?></p>
                  <p><strong>Venue:</strong> <?= htmlspecialchars($event['venue']) ?></p>
                  <p><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($event['event_date'])) ?></p>
                  <p><strong>Description:</strong> <?= htmlspecialchars($event['description'] ?: 'No description available') ?></p>
                </div>
                <form method="POST">
                  <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['event_id']) ?>">
                  <input type="hidden" name="action" value="join">
                  <button class="join-btn" type="submit">Join Event</button>
                </form>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-events">No upcoming events at the moment</div>
      <?php endif; ?>
    </div>
  </main>
  <?php include_once("../footer.php"); ?>
</body>
</html>