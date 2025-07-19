<?php
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
require_once '../controllers/NotificationController.php';
require_once '../includes/helpers/notification_helper.php';

// Set JSON header
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $debug_info = null)
{
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
error_log('update_task_status.php - Request data: ' . print_r($_POST, true));
error_log('update_task_status.php - Session data: ' . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    error_log('update_task_status.php - User not logged in');
    sendJsonResponse(false, 'Please login first');
}

// Check if required parameters are set
if (!isset($_POST['task_id']) || !isset($_POST['status'])) {
    $debug = [
        'post' => $_POST,
        'session' => $_SESSION,
        'missing' => [
            'task_id' => !isset($_POST['task_id']),
            'status' => !isset($_POST['status'])
        ]
    ];
    error_log('update_task_status.php - Missing required parameters: ' . json_encode($debug));
    sendJsonResponse(false, 'Missing required parameters', $debug);
}

$task_id = $_POST['task_id'];
$new_status = $_POST['status'];
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

error_log("Processing task update - ID: $task_id, Status: $new_status, Notes: $notes");

// Define valid statuses and their transitions
$valid_statuses = [
    'pending' => ['in_progress'],
    'in_progress' => ['pending_confirmation'],
    'pending_confirmation' => ['completed', 'rejected', 'pending'],
    'completed' => [],
    'rejected' => []
];

// Get task details
$stmt = $conn->prepare("SELECT t.*, tr.requester_id, tr.title as request_title,
                               u.username as assigned_to_name, u.full_name as assigned_to_full_name
                       FROM tasks t 
                       JOIN task_requests tr ON t.request_id = tr.id
                       LEFT JOIN users u ON t.assigned_to = u.id
                       WHERE t.id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    sendJsonResponse(false, 'Task not found');
}

// Check if user is authorized to update status
$is_requester = ($task['requester_id'] == $_SESSION['user_id']);
$is_assigned_staff = ($task['assigned_to'] == $_SESSION['user_id']);
$is_admin = ($_SESSION['role'] === 'admin');

// Validate status transition
$current_status = $task['status'];
if (!isset($valid_statuses[$current_status]) || !in_array($new_status, $valid_statuses[$current_status])) {
    sendJsonResponse(false, 'Invalid status transition');
}

// Check permissions based on status transition
if ($new_status === 'in_progress' && !$is_assigned_staff) {
    sendJsonResponse(false, 'Only assigned staff can start a task');
}

if ($new_status === 'pending_confirmation' && !$is_assigned_staff) {
    sendJsonResponse(false, 'Only assigned staff can mark a task as finished');
}

if ($new_status === 'completed' && !($is_requester || $is_admin)) {
    sendJsonResponse(false, 'Only requester or admin can confirm task completion');
}

if ($new_status === 'rejected' && !($is_requester || $is_admin)) {
    sendJsonResponse(false, 'Only requester or admin can reject a task');
}

if ($new_status === 'pending' && !($is_requester || $is_admin)) {
    sendJsonResponse(false, 'Only requester or admin can send a task back for further work');
}

// Start transaction
$conn->begin_transaction();

try {
    // Update task status
    $stmt = $conn->prepare("UPDATE tasks SET status = ?, completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $new_status, $task_id);

    if (!$stmt->execute()) {
        throw new Exception("Error updating task status");
    }

    // Add completion, rejection, or postponement notes if provided
    if (
        ($notes && ($new_status === 'completed' || $new_status === 'rejected' || $new_status === 'pending')) ||
        ($new_status === 'postponed' && isset($_POST['postponement_reason']) && $_POST['postponement_reason'])
    ) {
        if ($new_status === 'completed') {
            $note_type = 'completion';
            $note_text = $notes;
        } elseif ($new_status === 'postponed') {
            $note_type = 'postponement';
            $note_text = $_POST['postponement_reason'];
            if (isset($_POST['other_reason']) && $_POST['postponement_reason'] === 'other' && $_POST['other_reason']) {
                $note_text .= ': ' . $_POST['other_reason'];
            }
        } else {
            $note_type = 'rejection';
            $note_text = $notes;
        }
        $stmt = $conn->prepare("INSERT INTO task_completion_notes (task_id, user_id, notes, note_type, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiss", $task_id, $_SESSION['user_id'], $note_text, $note_type);
        if (!$stmt->execute()) {
            throw new Exception("Error saving completion/rejection/postponement notes");
        }
    }

    // Send notifications based on status change
    switch ($new_status) {
        case 'in_progress':
            // Notify requester that task has started
            $message = "Your task '{$task['request_title']}' has been started by {$task['assigned_to_name']}";
            $link = "view_task.php?id=" . $task_id;
            sendNotification($task['requester_id'], $message, $conn, $link);
            break;

        case 'pending_confirmation':
            // Notify requester that task is ready for review
            $message = "Your task '{$task['request_title']}' has been marked as finished by {$task['assigned_to_name']}";
            $link = "view_task.php?id=" . $task_id;
            sendNotification($task['requester_id'], $message, $conn, $link);
            break;

        case 'completed':
            // Notify staff that their task has been confirmed
            $message = "Your task '{$task['request_title']}' has been confirmed as completed";
            if ($notes) {
                $message .= " with the following notes: " . $notes;
            }
            $link = "view_task.php?id=" . $task_id;
            sendNotification($task['assigned_to'], $message, $conn, $link);
            break;

        case 'rejected':
            // Notify staff that their task has been rejected
            $message = "Your task '{$task['request_title']}' has been rejected";
            if ($notes) {
                $message .= " with the following notes: " . $notes;
            }
            $link = "view_task.php?id=" . $task_id;
            sendNotification($task['assigned_to'], $message, $conn, $link);
            break;
        case 'pending':
            // Notify staff that their task was sent back for further work
            $message = "Your task '{$task['request_title']}' was sent back by the requestor for further work.";
            $link = "view_task.php?id=" . $task_id;
            sendNotification($task['assigned_to'], $message, $conn, $link);
            break;
    }

    // Commit transaction
    $conn->commit();
    sendJsonResponse(true, 'Task status updated successfully');

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    sendJsonResponse(false, $e->getMessage());
}
?>