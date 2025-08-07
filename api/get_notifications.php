<?php
// Start session if not already started
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
        'count' => 0,
        'notifications' => []
    ]);
    exit;
}

// Get user ID from session (support both formats for backward compatibility)
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)$_SESSION['id'];

// Include necessary files
require_once '../database/connection.php';
require_once '../controllers/NotificationController.php';

// Initialize the notification controller
$notificationController = new NotificationController($conn);

// Get unread notification count
$unreadCount = $notificationController->getUnreadNotificationCount($userId);

// Get recent notifications
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$notifications = $notificationController->getUserNotifications($userId, $limit);

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => $unreadCount,
    'notifications' => $notifications
]);
?>