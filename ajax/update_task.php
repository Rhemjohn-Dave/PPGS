<?php
// Start output buffering
ob_start();

// Disable error reporting to prevent output before JSON
error_reporting(0);
ini_set('display_errors', 0);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

session_start();
require_once '../database/connection.php';
require_once '../controllers/TaskController.php';
require_once '../controllers/UserController.php';
require_once '../includes/helpers/notification_helper.php';
require_once '../includes/helpers/task_helper.php';

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $debug_info = null) {
    // Clear any output that might have been generated
    ob_clean();
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($debug_info !== null && $_SESSION['role'] === 'admin') {
        $response['debug'] = $debug_info;
    }
    
    error_log('Sending JSON response: ' . json_encode($response));
    echo json_encode($response);
    exit;
}

// Log the request data
error_log('update_task.php - Request data: ' . print_r($_POST, true));
error_log('update_task.php - Session data: ' . print_r($_SESSION, true));

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    error_log('update_task.php - User not logged in');
    sendJsonResponse(false, 'Please login first');
}

// Initialize controllers
$taskController = new TaskController($conn);
$userController = new UserController($conn);

// Get user details
$user = $userController->getUserById($_SESSION["user_id"] ?? $_SESSION["id"]);
if(!$user){
    error_log('update_task.php - User not found');
    sendJsonResponse(false, 'User not found');
}

// Check if user has permission (admin or task owner)
if($user['role'] !== 'admin' && (!isset($_POST['id']) || !$taskController->isTaskOwner($_SESSION["user_id"], $_POST['id']))){
    error_log('update_task.php - Permission denied for user ' . $_SESSION['user_id']);
    sendJsonResponse(false, 'Permission denied');
}

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get task ID and validate
    $task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$task_id) {
        sendJsonResponse(false, 'Invalid task ID');
    }
    
    // Debug logging
    error_log("Update Task Debug - User ID: " . $_SESSION['user_id']);
    error_log("Update Task Debug - User Role: " . $_SESSION['role']);
    
    // Get task details to verify ownership
    $task = getTaskById($task_id, $conn);
    if (!$task) {
        sendJsonResponse(false, 'Task not found');
    }
    
    // Debug logging
    error_log("Update Task Debug - Task Data: " . print_r($task, true));
    
    // Verify user has permission to update this task
    $is_admin = $_SESSION['role'] === 'admin';
    $is_assigned_staff = isset($task['assigned_to']) && $task['assigned_to'] == $_SESSION['user_id'];
    
    // Debug logging
    error_log("Update Task Debug - Is Admin: " . ($is_admin ? 'true' : 'false'));
    error_log("Update Task Debug - Is Assigned Staff: " . ($is_assigned_staff ? 'true' : 'false'));
    error_log("Update Task Debug - Task Assigned To: " . ($task['assigned_to'] ?? 'null'));
    error_log("Update Task Debug - Session User ID: " . $_SESSION['user_id']);
    
    // Allow both admin and assigned staff to update the task
    if (!$is_admin && !$is_assigned_staff) {
        sendJsonResponse(false, 'You do not have permission to update this task. Task assigned to: ' . $task['assigned_to'] . ', Your ID: ' . $_SESSION['user_id']);
    }
    
    // Get and validate status
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $valid_statuses = ['pending', 'in_progress', 'pending_confirmation', 'completed', 'postponed'];
    if (!in_array($status, $valid_statuses)) {
        sendJsonResponse(false, 'Invalid status');
    }
    
    // Handle postponement reason if status is postponed
    $postponement_reason = null;
    if ($status === 'postponed') {
        $reason = isset($_POST['postponement_reason']) ? $_POST['postponement_reason'] : '';
        if (empty($reason)) {
            sendJsonResponse(false, 'Please provide a reason for postponement');
        }
        
        // If reason is 'other', get the custom reason
        if ($reason === 'other') {
            $custom_reason = isset($_POST['other_reason']) ? trim($_POST['other_reason']) : '';
            if (empty($custom_reason)) {
                sendJsonResponse(false, 'Please specify the other reason for postponement');
            }
            $postponement_reason = $custom_reason;
        } else {
            $postponement_reason = $reason;
        }
    }
    
    // Update task status and postponement reason
    $update_fields = [];
    $update_values = [];
    $types = '';
    
    $update_fields[] = "status = ?";
    $update_values[] = $status;
    $types .= 's';
    
    if ($postponement_reason !== null) {
        $update_fields[] = "postponement_reasons = ?";
        $update_values[] = $postponement_reason;
        $types .= 's';
    }
    
    $update_fields[] = "updated_at = NOW()";
    
    $query = "UPDATE tasks SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $update_values[] = $task_id;
    $types .= 'i';
    
    try {
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, $types, ...$update_values);
            
            if (mysqli_stmt_execute($stmt)) {
                // Send notification to task creator
                $message = "Task '" . $task['title'] . "' has been updated to " . ucfirst(str_replace('_', ' ', $status));
                if ($status === 'postponed') {
                    $message .= " - Reason: " . ucfirst(str_replace('_', ' ', $postponement_reason));
                }
                sendNotification([$task['created_by']], $message, $conn, "tasks.php");
                
                sendJsonResponse(true, 'Task updated successfully');
            } else {
                error_log('Error executing statement: ' . mysqli_error($conn));
                sendJsonResponse(false, 'Error updating task: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log('Error preparing statement: ' . mysqli_error($conn));
            sendJsonResponse(false, 'Error preparing statement: ' . mysqli_error($conn));
        }
    } catch (Exception $e) {
        error_log('Exception in update_task.php: ' . $e->getMessage());
        sendJsonResponse(false, 'An error occurred while updating the task');
    }
}

// If not POST request
sendJsonResponse(false, 'Invalid request method'); 