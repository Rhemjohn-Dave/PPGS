<?php
require_once '../includes/helpers/notification_helper.php';
require_once '../database/connection.php';

header('Content-Type: application/json');

// Get the action from the request
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'count':
        // Get unread notification count
        $user_id = $_SESSION['user_id'] ?? 0;
        $count = getUnreadNotificationCount($user_id, $conn);
        echo json_encode(['count' => $count]);
        break;

    case 'mark_read':
        // Mark notification as read
        $notification_id = $_POST['notification_id'] ?? 0;
        $success = markNotificationAsRead($notification_id, $conn);
        echo json_encode(['success' => $success]);
        break;

    case 'list':
        // Get all notifications
        $user_id = $_SESSION['user_id'] ?? 0;
        $notifications = getUserNotifications($user_id, $conn);
        echo json_encode(['notifications' => $notifications]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
} 