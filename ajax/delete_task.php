<?php
session_start();
require_once '../database/connection.php';
require_once '../controllers/TaskController.php';
require_once '../controllers/UserController.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Initialize controllers
$taskController = new TaskController($conn);
$userController = new UserController($conn);

// Get user details
$user = $userController->getUserById($_SESSION["user_id"] ?? $_SESSION["id"]);
if(!$user){
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Only admin can delete tasks
if($user['role'] !== 'admin'){
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only administrators can delete tasks.']);
    exit;
}

// Check if task ID is provided
if(isset($_POST['id']) && !empty($_POST['id'])){
    $taskId = $_POST['id'];
    
    // Get task details before deletion for notifications
    $task = $taskController->getById($taskId);
    if(!$task){
        // Try to delete directly from the database if the task exists
        $query = "DELETE FROM tasks WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $query)){
            mysqli_stmt_bind_param($stmt, "i", $taskId);
            if(mysqli_stmt_execute($stmt)){
                echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . mysqli_error($conn)]);
            exit;
        }
    }
    
    // If we got this far, we have a task, so use the controller to delete it
    $success = $taskController->delete($taskId);
    
    if($success){
        echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
    } else {
        // Fallback direct deletion if controller method fails
        $query = "DELETE FROM tasks WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $query)){
            mysqli_stmt_bind_param($stmt, "i", $taskId);
            if(mysqli_stmt_execute($stmt)){
                echo json_encode(['success' => true, 'message' => 'Task deleted successfully (direct)']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete task: ' . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare delete query: ' . mysqli_error($conn)]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
}

exit; 