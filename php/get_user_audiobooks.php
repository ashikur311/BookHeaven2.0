<?php
session_start();
require_once '../admin/db.php'; // Your database connection file

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Check for active subscriptions
    $stmt = $pdo->prepare("
        SELECT us.*, sp.audiobook_quantity 
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.subscription_plan_id = sp.plan_id
        WHERE us.user_id = ? AND us.status = 'active' AND us.end_date > NOW()
        ORDER BY us.end_date DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        echo json_encode(['success' => false, 'message' => 'No active subscription']);
        exit;
    }

    // Get accessible audiobooks
    $stmt = $pdo->prepare("
        SELECT ab.* 
        FROM audiobooks ab
        JOIN user_subscription_audiobook_access access ON ab.audiobook_id = access.audiobook_id
        WHERE access.user_subscription_id = ? AND access.status = 'borrowed'
        ORDER BY ab.title
    ");
    $stmt->execute([$subscription['user_subscription_id']]);
    $audiobooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'audiobooks' => $audiobooks
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}