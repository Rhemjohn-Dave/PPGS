<?php
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/notification_helper.php';

/**
 * Get task details by ID
 * @param int $task_id Task ID
 * @param mysqli $conn Database connection
 * @return array|null Task details or null if not found
 */
function getTaskById($task_id, $conn) {
    $task_id = mysqli_real_escape_string($conn, $task_id);
    
    // Try with new schema (id column)
    $query = "SELECT t.*, tr.title, tr.description, t.priority,
              d.name as department_name, u.username as assigned_to_name, t.assigned_to
              FROM tasks t
              LEFT JOIN task_requests tr ON t.request_id = tr.id
              LEFT JOIN departments d ON tr.department_id = d.id
              LEFT JOIN users u ON t.assigned_to = u.id
              WHERE t.id = '$task_id'";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $task = mysqli_fetch_assoc($result);
        error_log("Task found with new schema: " . print_r($task, true));
        return $task;
    }
    
    // Try with legacy schema (task_id column)
    $query = "SELECT t.*, tr.title, tr.description, t.priority,
              d.name as department_name, u.username as assigned_to_name, t.assigned_to
              FROM tasks t
              LEFT JOIN task_requests tr ON t.request_id = tr.id
              LEFT JOIN departments d ON tr.department_id = d.id
              LEFT JOIN users u ON t.assigned_to = u.id
              WHERE t.task_id = '$task_id'";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $task = mysqli_fetch_assoc($result);
        error_log("Task found with legacy schema: " . print_r($task, true));
        return $task;
    }
    
    error_log("No task found for ID: " . $task_id);
    return null;
}

/**
 * Get tasks by department
 * @param int $department_id Department ID
 * @param mysqli $conn Database connection
 * @return array Array of tasks
 */
function getTasksByDepartment($department_id, $conn) {
    $department_id = mysqli_real_escape_string($conn, $department_id);
    $query = "SELECT t.*, d.department_name, u.username as assigned_to_name
              FROM tasks t
              LEFT JOIN departments d ON t.department_id = d.department_id
              LEFT JOIN users u ON t.assigned_to = u.user_id
              WHERE t.department_id = '$department_id'
              ORDER BY t.created_at DESC";
    
    $tasks = [];
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tasks[] = $row;
        }
    }
    return $tasks;
}

/**
 * Get tasks assigned to a user
 * @param int $user_id User ID
 * @param mysqli $conn Database connection
 * @return array Array of tasks
 */
function getTasksByAssignee($user_id, $conn) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $query = "SELECT t.*, d.department_name, u.username as assigned_to_name
              FROM tasks t
              LEFT JOIN departments d ON t.department_id = d.department_id
              LEFT JOIN users u ON t.assigned_to = u.user_id
              WHERE t.assigned_to = '$user_id'
              ORDER BY t.created_at DESC";
    
    $tasks = [];
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tasks[] = $row;
        }
    }
    return $tasks;
}

/**
 * Create a new task
 * @param array $task_data Task data
 * @param mysqli $conn Database connection
 * @return int|false Task ID if successful, false otherwise
 */
function createTask($task_data, $conn) {
    $title = mysqli_real_escape_string($conn, $task_data['title']);
    $description = mysqli_real_escape_string($conn, $task_data['description']);
    $department_id = mysqli_real_escape_string($conn, $task_data['department_id']);
    $assigned_to = mysqli_real_escape_string($conn, $task_data['assigned_to']);
    $priority = mysqli_real_escape_string($conn, $task_data['priority']);
    $due_date = mysqli_real_escape_string($conn, $task_data['due_date']);
    $created_by = mysqli_real_escape_string($conn, $task_data['created_by']);
    
    $query = "INSERT INTO tasks (title, description, department_id, assigned_to, 
              priority, due_date, created_by, status, created_at)
              VALUES ('$title', '$description', '$department_id', '$assigned_to',
              '$priority', '$due_date', '$created_by', 'pending', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $task_id = mysqli_insert_id($conn);
        
        // Send notification to assigned user
        $message = "You have been assigned a new task: " . $title;
        $link = "tasks.php";
        sendNotification([$assigned_to], $message, $conn, $link);
        
        return $task_id;
    }
    return false;
}

/**
 * Update task status
 * @param int $task_id Task ID
 * @param string $status New status
 * @param mysqli $conn Database connection
 * @return bool True if successful, false otherwise
 */
function updateTaskStatus($task_id, $status, $conn) {
    $task_id = mysqli_real_escape_string($conn, $task_id);
    $status = mysqli_real_escape_string($conn, $status);
    
    $query = "UPDATE tasks SET status = '$status', updated_at = NOW() 
              WHERE task_id = '$task_id'";
    
    if (mysqli_query($conn, $query)) {
        $task = getTaskById($task_id, $conn);
        if ($task) {
            // Send notification to task creator
            $message = "Your task '" . $task['title'] . "' has been updated";
            $link = "tasks.php";
            sendNotification([$task['created_by']], $message, $conn, $link);
        }
        return true;
    }
    return false;
}

/**
 * Delete task
 * @param int $task_id Task ID
 * @param mysqli $conn Database connection
 * @return bool True if successful, false otherwise
 */
function deleteTask($task_id, $conn) {
    $task_id = mysqli_real_escape_string($conn, $task_id);
    $task = getTaskById($task_id, $conn);
    
    // First try with 'task_id' column (legacy)
    $query = "DELETE FROM tasks WHERE task_id = '$task_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_affected_rows($conn) > 0) {
        // Send notification to assigned user if task was found
        if ($task && isset($task['assigned_to']) && $task['assigned_to']) {
            $message = "A task assigned to you has been deleted: " . $task['title'];
            sendNotification([$task['assigned_to']], $message, $conn, null);
        }
        return true;
    } 
    
    // Try with 'id' column (new schema)
    $query = "DELETE FROM tasks WHERE id = '$task_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_affected_rows($conn) > 0) {
        // Send notification to assigned user if task was found
        if ($task && isset($task['assigned_to']) && $task['assigned_to']) {
            $message = "A task assigned to you has been deleted: " . $task['title'];
            sendNotification([$task['assigned_to']], $message, $conn, null);
        }
        return true;
    }
    
    return false;
}

/**
 * Get task statistics for a department
 * @param int $department_id Department ID
 * @param mysqli $conn Database connection
 * @return array Task statistics
 */
function getTaskStatsByDepartment($department_id, $conn) {
    $department_id = mysqli_real_escape_string($conn, $department_id);
    $query = "SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks
              FROM tasks 
              WHERE department_id = '$department_id'";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        return mysqli_fetch_assoc($result);
    }
    return [
        'total_tasks' => 0,
        'completed_tasks' => 0,
        'pending_tasks' => 0,
        'in_progress_tasks' => 0
    ];
} 