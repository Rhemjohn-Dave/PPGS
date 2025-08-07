<?php
session_start();
require_once "../config/database.php";
require_once '../controllers/NotificationController.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

// Check if required fields are set
if(!isset($_POST['request_id']) || !isset($_POST['staff_id']) || !isset($_POST['due_date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields.'
    ]);
    exit;
}

$request_id = $_POST['request_id'];
$staff_id = $_POST['staff_id'];
$due_date = $_POST['due_date'];

try {
    // Check if request exists and is approved
    $sql = "SELECT title, requester_id, status, program_head_approval, adaa_approval 
            FROM task_requests WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $request = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if(!$request) {
            throw new Exception("Request not found.");
        }
        
        // Check if task already exists for this request
        $task_check_sql = "SELECT id FROM tasks WHERE request_id = ?";
        if($task_stmt = mysqli_prepare($conn, $task_check_sql)) {
            mysqli_stmt_bind_param($task_stmt, "i", $request_id);
            mysqli_stmt_execute($task_stmt);
            $task_result = mysqli_stmt_get_result($task_stmt);
            if(mysqli_num_rows($task_result) > 0) {
                throw new Exception("This request has already been assigned to a task.");
            }
            mysqli_stmt_close($task_stmt);
        }
        
        if($request['program_head_approval'] != 'approved' || $request['adaa_approval'] != 'approved') {
            throw new Exception("This request has not been fully approved.");
        }
    }
    
    // Check staff availability
    $sql = "SELECT COUNT(*) as task_count FROM tasks 
            WHERE assigned_to = ? AND status IN ('pending', 'in_progress')";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $staff_tasks = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if($staff_tasks['task_count'] >= 5) {
            throw new Exception("Selected staff member has too many pending tasks.");
        }
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update task request status to approved (since it's being assigned)
        $sql = "UPDATE task_requests SET status = 'approved' WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $request_id);
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update task request status.");
            }
            mysqli_stmt_close($stmt);
        }
        
        // Create task
        $sql = "INSERT INTO tasks (request_id, assigned_to, status, priority, due_date) VALUES (?, ?, 'pending', 'medium', ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iis", $request_id, $staff_id, $due_date);
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to create task.");
            }
            $task_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }
        
        // Update task status to in_progress
        $sql = "UPDATE tasks SET status = 'in_progress' WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $task_id);
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update task status.");
            }
            mysqli_stmt_close($stmt);
        }
        
        // Send notifications
        $notificationController = new NotificationController($conn);
        
        // Notify staff
        $staff_message = "You have been assigned a new task: " . htmlspecialchars($request['title']);
        $staff_link = "tasks.php?view_task=" . $task_id;
        $notificationController->sendNotification([$staff_id], $staff_message, $staff_link);
        
        // Notify requester
        $requester_message = "Your task '" . htmlspecialchars($request['title']) . "' has been assigned to a staff member.";
        $requester_link = "tasks.php?view_request=" . $request_id;
        $notificationController->sendNotification([$request['requester_id']], $requester_message, $requester_link);
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task assigned successfully.'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 