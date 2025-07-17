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

// Return task data as JSON
echo json_encode([
    'success' => true,
    'task' => $task
]);
exit;