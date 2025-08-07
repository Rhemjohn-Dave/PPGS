<?php
session_start();
require_once '../database/connection.php';
require_once '../controllers/TaskController.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

$taskId = $_GET['id'];
$taskController = new TaskController($conn);

// Get task details
$task = $taskController->getById($taskId);

if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

// Check if user has permission to view this task
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$hasPermission = false;

// Get task request details to check permissions
$requestQuery = "SELECT tr.requester_id, tr.department_id, tr.program_head_approval, d.head_id 
                 FROM task_requests tr 
                 LEFT JOIN departments d ON tr.department_id = d.id 
                 WHERE tr.id = ?";
if ($stmt = mysqli_prepare($conn, $requestQuery)) {
    mysqli_stmt_bind_param($stmt, "i", $task['request_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $requester_id = $row['requester_id'];
        $department_id = $row['department_id'];
        $program_head_approval = $row['program_head_approval'];
        $head_id = $row['head_id'];

        // Check permissions based on role
        if ($user_role === 'admin' || $user_role === 'adaa') {
            // Admin and ADAA can see all tasks
            $hasPermission = true;
        } elseif ($user_role === 'program_head' || $user_role === 'program head') {
            // Program head can see tasks from their department that they approved
            // Get program head's department from database
            $program_head_dept_query = "SELECT department_id FROM users WHERE id = ?";
            if ($dept_stmt = mysqli_prepare($conn, $program_head_dept_query)) {
                mysqli_stmt_bind_param($dept_stmt, "i", $user_id);
                mysqli_stmt_execute($dept_stmt);
                $dept_result = mysqli_stmt_get_result($dept_stmt);
                if ($dept_row = mysqli_fetch_assoc($dept_result)) {
                    $program_head_department_id = $dept_row['department_id'];
                    $hasPermission = ($department_id == $program_head_department_id && $program_head_approval === 'approved');
                }
                mysqli_stmt_close($dept_stmt);
            }
        } else {
            // Regular users can see tasks they created or are assigned to
            $hasPermission = ($requester_id == $user_id || $task['assigned_to'] == $user_id);
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$hasPermission) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to view this task']);
    exit;
}

// Return task data as JSON
echo json_encode([
    'success' => true,
    'task' => $task
]);
exit;