<?php
require_once __DIR__ . '/../includes/helpers/notification_helper.php';

class NotificationController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Send a notification to one or more users
     * 
     * @param array|int $userIds - User ID or array of user IDs
     * @param string $message - Notification message
     * @param string $link - Optional link for the notification to redirect to
     * @return bool - Success status
     */
    public function sendNotification($userIds, $message, $link = '') {
        return sendNotification($userIds, $message, $this->conn, $link);
    }
    
    /**
     * Get notifications for a user
     * 
     * @param int $userId - User ID
     * @param int $limit - Maximum number of notifications to retrieve
     * @param bool $unreadOnly - Whether to retrieve only unread notifications
     * @return array - Array of notifications
     */
    public function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
        return getUserNotifications($userId, $this->conn, $limit, $unreadOnly);
    }
    
    /**
     * Get count of unread notifications for a user
     * 
     * @param int $userId - User ID
     * @return int - Count of unread notifications
     */
    public function getUnreadNotificationCount($userId) {
        return getUnreadNotificationCount($userId, $this->conn);
    }
    
    /**
     * Mark a notification as read
     * 
     * @param int $notificationId - Notification ID
     * @param int $userId - User ID
     * @return bool - Success status
     */
    public function markNotificationAsRead($notificationId, $userId) {
        return markNotificationAsRead($notificationId, $userId, $this->conn);
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $userId - User ID
     * @return bool - Success status
     */
    public function markAllNotificationsAsRead($userId) {
        return markAllNotificationsAsRead($userId, $this->conn);
    }
    
    /**
     * Get notifications with pagination and filtering
     * 
     * @param int $userId - User ID
     * @param int $page - Page number
     * @param int $limit - Items per page
     * @param string $filter - Filter by read status (all, read, unread)
     * @return array - Array with notifications and pagination info
     */
    public function getPaginatedNotifications($userId, $page = 1, $limit = 20, $filter = 'all') {
        $userId = (int)$userId;
        $page = (int)$page;
        $limit = (int)$limit;
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause based on filter
        $whereClause = "WHERE user_id = $userId";
        if ($filter === 'read') {
            $whereClause .= " AND is_read = 1";
        } else if ($filter === 'unread') {
            $whereClause .= " AND is_read = 0";
        }
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM notifications $whereClause";
        $countResult = mysqli_query($this->conn, $countSql);
        $totalNotifications = 0;
        
        if ($countResult && $row = mysqli_fetch_assoc($countResult)) {
            $totalNotifications = (int)$row['total'];
        }
        
        $totalPages = ceil($totalNotifications / $limit);
        
        // Get notifications for the current page
        $sql = "SELECT id, message, link, is_read, created_at 
                FROM notifications 
                $whereClause
                ORDER BY created_at DESC 
                LIMIT $offset, $limit";
        
        $result = mysqli_query($this->conn, $sql);
        $notifications = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = $row;
            }
        }
        
        return [
            'notifications' => $notifications,
            'total' => $totalNotifications,
            'pages' => $totalPages,
            'current_page' => $page
        ];
    }
    
    /**
     * Send task assignment notification
     * 
     * @param int $userId - User ID to notify
     * @param string $taskTitle - Task title
     * @param int $taskId - Task ID
     * @return bool - Success status
     */
    public function sendTaskAssignmentNotification($userId, $taskTitle, $taskId) {
        $message = "You have been assigned a new task: " . $taskTitle;
        $link = "tasks.php?view_task=" . $taskId;
        return $this->sendNotification($userId, $message, $link);
    }
    
    /**
     * Send task status update notification
     * 
     * @param int $userId - User ID to notify
     * @param string $taskTitle - Task title
     * @param string $status - New status
     * @param int $taskId - Task ID
     * @return bool - Success status
     */
    public function sendTaskStatusNotification($userId, $taskTitle, $status, $taskId) {
        $message = "Task '" . $taskTitle . "' status has been updated to " . str_replace('_', ' ', $status);
        $link = "tasks.php?view_task=" . $taskId;
        return $this->sendNotification($userId, $message, $link);
    }
    
    /**
     * Send task request approval notification
     * 
     * @param int $userId - User ID to notify
     * @param string $taskTitle - Task title
     * @param string $approvalType - Type of approval (program_head or adaa)
     * @param string $status - Approval status
     * @param int $requestId - Request ID
     * @return bool - Success status
     */
    public function sendApprovalNotification($userId, $taskTitle, $approvalType, $status, $requestId) {
        $approvalName = $approvalType === 'program_head' ? 'Program Head' : 'ADAA';
        $message = "Your task request '" . $taskTitle . "' has been " . $status . " by " . $approvalName;
        $link = "task_requests.php?view=" . $requestId;
        return $this->sendNotification($userId, $message, $link);
    }
    
    /**
     * Send task completion notification
     * 
     * @param int $userId - User ID to notify
     * @param string $taskTitle - Task title
     * @param int $taskId - Task ID
     * @return bool - Success status
     */
    public function sendTaskCompletionNotification($userId, $taskTitle, $taskId) {
        $message = "Task '" . $taskTitle . "' has been marked as completed";
        $link = "tasks.php?view_task=" . $taskId;
        return $this->sendNotification($userId, $message, $link);
    }
}
?> 