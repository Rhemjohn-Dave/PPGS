<?php
require_once '../controllers/TaskController.php';
require_once '../database/connection.php';

header('Content-Type: application/json');

// Initialize controllers
$taskController = new TaskController($conn);

// Get the action from the request
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $task_id = $taskController->create($data);
        echo json_encode(['success' => (bool)$task_id, 'task_id' => $task_id]);
        break;

    case 'update':
        $task_id = $_POST['task_id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $success = $taskController->update($task_id, $data);
        echo json_encode(['success' => $success]);
        break;

    case 'delete':
        $task_id = $_POST['task_id'] ?? 0;
        $success = $taskController->delete($task_id);
        echo json_encode(['success' => $success]);
        break;

    case 'get':
        $task_id = $_GET['task_id'] ?? 0;
        $task = $taskController->getById($task_id);
        echo json_encode(['task' => $task]);
        break;

    case 'list':
        $department_id = $_GET['department_id'] ?? 0;
        $user_id = $_GET['user_id'] ?? 0;
        
        if ($department_id) {
            $tasks = $taskController->getByDepartment($department_id);
        } elseif ($user_id) {
            $tasks = $taskController->getByAssignee($user_id);
        } else {
            $tasks = [];
        }
        
        echo json_encode(['tasks' => $tasks]);
        break;

    case 'stats':
        $department_id = $_GET['department_id'] ?? 0;
        $stats = $taskController->getStats($department_id);
        echo json_encode(['stats' => $stats]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
} 