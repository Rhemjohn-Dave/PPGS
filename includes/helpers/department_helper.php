<?php
require_once __DIR__ . '/../../database/connection.php';

/**
 * Get department details by ID
 * @param int $department_id Department ID
 * @param mysqli $conn Database connection
 * @return array|null Department details or null if not found
 */
function getDepartmentById($department_id, $conn) {
    if ($department_id === null) {
        return null;
    }
    $department_id = mysqli_real_escape_string($conn, $department_id);
    $query = "SELECT d.*, u.username as head_username 
              FROM departments d 
              LEFT JOIN users u ON d.head_id = u.id 
              WHERE d.id = '$department_id'";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Get all departments
 * @param mysqli $conn Database connection
 * @return array Array of departments
 */
function getAllDepartments($conn) {
    $query = "SELECT d.*, u.username as head_username 
              FROM departments d 
              LEFT JOIN users u ON d.head_id = u.id 
              ORDER BY d.name";
    
    $departments = [];
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $departments[] = $row;
        }
    }
    return $departments;
}

/**
 * Create a new department
 * @param array $department_data Department data
 * @param mysqli $conn Database connection
 * @return array Result with success status and message
 */
function createDepartment($department_data, $conn) {
    $name = mysqli_real_escape_string($conn, $department_data['name']);
    $description = mysqli_real_escape_string($conn, $department_data['description'] ?? '');
    $head_id = isset($department_data['head_id']) ? mysqli_real_escape_string($conn, $department_data['head_id']) : null;
    
    // Validate head_id if provided
    if ($head_id) {
        $check_head = "SELECT id FROM users WHERE id = '$head_id' AND role = 'program head'";
        $result = mysqli_query($conn, $check_head);
        if (!$result || mysqli_num_rows($result) === 0) {
            return ['success' => false, 'message' => 'Invalid program head selected'];
        }
    }
    
    $query = "INSERT INTO departments (name, description, head_id, created_at)
              VALUES ('$name', '$description', " . ($head_id ? "'$head_id'" : "NULL") . ", NOW())";
    
    if (mysqli_query($conn, $query)) {
        return ['success' => true, 'message' => 'Department created successfully', 'id' => mysqli_insert_id($conn)];
    }
    return ['success' => false, 'message' => 'Failed to create department: ' . mysqli_error($conn)];
}

/**
 * Update department
 * @param int $department_id Department ID
 * @param array $department_data Department data to update
 * @param mysqli $conn Database connection
 * @return array Result with success status and message
 */
function updateDepartment($department_id, $department_data, $conn) {
    $department_id = mysqli_real_escape_string($conn, $department_id);
    $updates = [];
    
    if (isset($department_data['name'])) {
        $name = mysqli_real_escape_string($conn, $department_data['name']);
        $updates[] = "name = '$name'";
    }
    
    if (isset($department_data['description'])) {
        $description = mysqli_real_escape_string($conn, $department_data['description']);
        $updates[] = "description = '$description'";
    }
    
    if (isset($department_data['head_id'])) {
        $head_id = mysqli_real_escape_string($conn, $department_data['head_id']);
        if ($head_id) {
            // Validate head_id
            $check_head = "SELECT id FROM users WHERE id = '$head_id' AND role = 'program head'";
            $result = mysqli_query($conn, $check_head);
            if (!$result || mysqli_num_rows($result) === 0) {
                return ['success' => false, 'message' => 'Invalid program head selected'];
            }
        }
        $updates[] = "head_id = " . ($head_id ? "'$head_id'" : "NULL");
    }
    
    if (!empty($updates)) {
        $updates[] = "updated_at = NOW()";
        $update_str = implode(", ", $updates);
        $query = "UPDATE departments SET $update_str WHERE id = '$department_id'";
        
        if (mysqli_query($conn, $query)) {
            return ['success' => true, 'message' => 'Department updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update department: ' . mysqli_error($conn)];
    }
    return ['success' => false, 'message' => 'No data to update'];
}

/**
 * Delete department
 * @param int $department_id Department ID
 * @param mysqli $conn Database connection
 * @return array Result with success status and message
 */
function deleteDepartment($department_id, $conn) {
    $department_id = mysqli_real_escape_string($conn, $department_id);
    
    // First check if there are any users or tasks in this department
    $check_query = "SELECT COUNT(*) as count FROM users WHERE department_id = '$department_id'
                   UNION ALL
                   SELECT COUNT(*) as count FROM tasks WHERE department_id = '$department_id'";
    
    $result = mysqli_query($conn, $check_query);
    if ($result) {
        $user_count = mysqli_fetch_assoc($result)['count'];
        mysqli_data_seek($result, 1);
        $task_count = mysqli_fetch_assoc($result)['count'];
        
        if ($user_count > 0 || $task_count > 0) {
            return ['success' => false, 'message' => 'Cannot delete department with associated users or tasks'];
        }
        
        $query = "DELETE FROM departments WHERE id = '$department_id'";
        if (mysqli_query($conn, $query)) {
            return ['success' => true, 'message' => 'Department deleted successfully'];
        }
        return ['success' => false, 'message' => 'Failed to delete department: ' . mysqli_error($conn)];
    }
    return ['success' => false, 'message' => 'Failed to check department dependencies'];
}

/**
 * Get department statistics
 * @param int $department_id Department ID
 * @param mysqli $conn Database connection
 * @return array Department statistics
 */
function getDepartmentStats($department_id, $conn) {
    $department_id = mysqli_real_escape_string($conn, $department_id);
    
    $stats = [
        'total_users' => 0,
        'total_tasks' => 0,
        'completed_tasks' => 0,
        'pending_tasks' => 0,
        'in_progress_tasks' => 0
    ];
    
    // Get user count
    $user_query = "SELECT COUNT(*) as count FROM users WHERE department_id = '$department_id'";
    $result = mysqli_query($conn, $user_query);
    if ($result) {
        $stats['total_users'] = mysqli_fetch_assoc($result)['count'];
    }
    
    // Get task statistics
    $task_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
                   FROM tasks 
                   WHERE department_id = '$department_id'";
    
    $result = mysqli_query($conn, $task_query);
    if ($result) {
        $task_stats = mysqli_fetch_assoc($result);
        $stats['total_tasks'] = $task_stats['total'];
        $stats['completed_tasks'] = $task_stats['completed'];
        $stats['pending_tasks'] = $task_stats['pending'];
        $stats['in_progress_tasks'] = $task_stats['in_progress'];
    }
    
    return $stats;
} 