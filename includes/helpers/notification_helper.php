<?php
/**
 * Notification Helper Functions
 * Contains functions for managing and sending notifications
 */

/**
 * Send a notification to one or more users
 * 
 * @param array|int $userIds - User ID or array of user IDs
 * @param string $message - Notification message
 * @param mysqli $conn - Database connection
 * @param string $link - Optional link for the notification to redirect to
 * @return bool - Success status
 */
function sendNotification($userIds, $message, $conn, $link = '') {
    if (empty($userIds)) {
        return false;
    }
    
    if (!is_array($userIds)) {
        $userIds = [$userIds];
    }
    
    $message = mysqli_real_escape_string($conn, $message);
    $link = mysqli_real_escape_string($conn, $link);
    $created_at = date('Y-m-d H:i:s');
    $success = true;
    
    foreach ($userIds as $userId) {
        $userId = (int)$userId;
        $query = "INSERT INTO notifications (user_id, message, link, created_at) 
                  VALUES ($userId, '$message', '$link', '$created_at')";
        
        if (!mysqli_query($conn, $query)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Get notifications for a user
 * 
 * @param int $userId - User ID
 * @param mysqli $conn - Database connection
 * @param int $limit - Maximum number of notifications to retrieve
 * @param bool $unreadOnly - Whether to retrieve only unread notifications
 * @return array - Array of notifications
 */
function getUserNotifications($userId, $conn, $limit = 10, $unreadOnly = false) {
    $userId = (int)$userId;
    $limit = (int)$limit;
    
    $whereClause = $unreadOnly ? "AND is_read = 0" : "";
    
    $query = "SELECT id, message, link, is_read, created_at 
              FROM notifications 
              WHERE user_id = $userId $whereClause 
              ORDER BY created_at DESC 
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return [];
    }
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Get count of unread notifications for a user
 * 
 * @param int $userId - User ID
 * @param mysqli $conn - Database connection
 * @return int - Count of unread notifications
 */
function getUnreadNotificationCount($userId, $conn) {
    $userId = (int)$userId;
    
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $userId AND is_read = 0";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return 0;
    }
    
    $row = mysqli_fetch_assoc($result);
    return (int)$row['count'];
}

/**
 * Mark a notification as read
 * 
 * @param int $notificationId - Notification ID
 * @param int $userId - User ID (for security)
 * @param mysqli $conn - Database connection
 * @return bool - Success status
 */
function markNotificationAsRead($notificationId, $userId, $conn) {
    $notificationId = (int)$notificationId;
    $userId = (int)$userId;
    
    $query = "UPDATE notifications 
              SET is_read = 1 
              WHERE id = $notificationId AND user_id = $userId";
    
    return mysqli_query($conn, $query) ? true : false;
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int $userId - User ID
 * @param mysqli $conn - Database connection
 * @return bool - Success status
 */
function markAllNotificationsAsRead($userId, $conn) {
    $userId = (int)$userId;
    
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = $userId AND is_read = 0";
    
    return mysqli_query($conn, $query) ? true : false;
}

/**
 * Get user IDs by role
 * 
 * @param string $role The role to search for
 * @param mysqli $conn Database connection
 * @return array Array of user IDs
 */
function getUserIdsByRole($role, $conn) {
    $user_ids = [];
    $sql = "SELECT id FROM users WHERE role = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $role);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $user_ids[] = $row['id'];
            }
        } else {
            error_log("Error getting user IDs by role: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing get user IDs statement: " . mysqli_error($conn));
    }

    return $user_ids;
}

/**
 * Get user IDs by department and role
 * 
 * @param int $department_id The department ID
 * @param string $role The role to search for
 * @param mysqli $conn Database connection
 * @return array Array of user IDs
 */
function getUserIdsByDepartmentAndRole($department_id, $role, $conn) {
    $user_ids = [];
    $sql = "SELECT id FROM users WHERE department_id = ? AND role = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $department_id, $role);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $user_ids[] = $row['id'];
            }
        } else {
            error_log("Error getting user IDs by department and role: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing get user IDs by department statement: " . mysqli_error($conn));
    }

    return $user_ids;
}

/**
 * Get recent notifications for a user
 * 
 * @param int $user_id The user ID
 * @param mysqli $conn Database connection
 * @param int $limit Maximum number of notifications to return
 * @return array Array of notification objects
 */
function getRecentNotifications($user_id, $conn, $limit = 10) {
    $notifications = [];
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = $row;
            }
        } else {
            error_log("Error getting recent notifications: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing get recent notifications statement: " . mysqli_error($conn));
    }

    return $notifications;
}
