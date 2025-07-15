<?php
// Start session if not already started
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Get user ID from session (support both formats for backward compatibility)
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)$_SESSION['id'];

// Check if notification ID is provided
if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Notification ID is required'
    ]);
    exit;
}

$notificationId = (int)$_POST['notification_id'];

// Include necessary files
require_once '../database/connection.php';
require_once '../controllers/NotificationController.php';

// Initialize the notification controller
$notificationController = new NotificationController($conn);

// Mark the notification as read
$success = $notificationController->markNotificationAsRead($notificationId, $userId);

// Get updated count of unread notifications
$unreadCount = $notificationController->getUnreadNotificationCount($userId);

// Return a single combined JSON response
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read',
    'unread_count' => $unreadCount
]);
?> 