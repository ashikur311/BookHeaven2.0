<?php
// logout.php
session_start();

// Verify admin is logged in before logging out
if (!isset($_SESSION['admin_id'])) {
    header("Location: authentication.php");
    exit();
}

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with logout message
$_SESSION['logout_message'] = "You have been successfully logged out.";
header("Location: authentication.php");
exit();
?>