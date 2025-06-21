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
    
    $event_id = (int)$_POST['event_id'];
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
    <link rel="stylesheet" href="/BookHeaven2.0/css/events.css">
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
                    case 'email': echo "Failed to send ticket email. Please try again."; break;
                    case 'pdf': echo "Failed to generate ticket. Please try again."; break;
                    default: echo "An error occurred. Please try again.";
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
                            <h3 class="event-title"><?= htmlspecialchars($event['name']) ?></h3>
                            <div class="event-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue']) ?></span>
                                <span><i class="far fa-calendar-alt"></i> <?= date('M d, Y h:i A', strtotime($event['event_date'])) ?></span>
                            </div>
                            <p class="event-details"><?= htmlspecialchars($event['description'] ?: 'No description available') ?></p>
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
                            <div class="countdown"><?= date_diff(new DateTime(), new DateTime($event['event_date']))->format('%d days %H hours') ?> left</div>
                            <img src="/BookHeaven2.0/<?= htmlspecialchars($event['banner_url'] ?? 'assets/default_event.jpg') ?>" 
                                 alt="<?= htmlspecialchars($event['name']) ?>" class="event-poster">
                            <div class="event-content">
                                <h3 class="event-title"><?= htmlspecialchars($event['name']) ?></h3>
                                <div class="event-meta">
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['venue']) ?></span>
                                    <span><i class="far fa-calendar-alt"></i> <?= date('M d, Y h:i A', strtotime($event['event_date'])) ?></span>
                                </div>
                                <p class="event-details"><?= htmlspecialchars($event['description'] ?: 'No description available') ?></p>
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