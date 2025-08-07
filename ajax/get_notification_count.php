<?php
// Start session if not already started
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
        'count' => 0
    ]);
    exit;
}

// Get user ID from session (support both formats for backward compatibility)
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)$_SESSION['id'];

// Include necessary files
require_once '../database/connection.php';
require_once '../controllers/NotificationController.php';

// Initialize notification controller
$notificationController = new NotificationController($conn);

// Get unread notification count
$unreadCount = $notificationController->getUnreadNotificationCount($userId);

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => $unreadCount
]);
?> 