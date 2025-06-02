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

// Include necessary files
require_once '../database/connection.php';
require_once '../controllers/NotificationController.php';

// Initialize the notification controller
$notificationController = new NotificationController($conn);

// Mark all notifications as read
$success = $notificationController->markAllNotificationsAsRead($userId);

// Return JSON response
echo json_encode([
    'success' => $success,
    'message' => $success ? 'All notifications marked as read' : 'Failed to mark notifications as read'
]); 