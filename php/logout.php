<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the user_id from the session
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    // Establish database connection
    require_once("../db_connection.php");

    // Update user status to 'offline' in the user_activities table
    $stmt = $conn->prepare("UPDATE user_activities SET status = 'offline' WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        // Success message can be added here if needed
        // $_SESSION['success_message'] = "User status updated to offline";
    } else {
        // Handle error if needed
        // $_SESSION['error_message'] = "Error updating user status in activities table";
    }
}

// Clear session data and destroy session
session_unset();  
session_destroy();  

// Clear session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to authentication page after logout
header("Location: /BookHeaven2.0/php/authentication.php"); 
exit();
?>
