<?php
require_once '../controllers/DepartmentController.php';
require_once '../database/connection.php';

header('Content-Type: application/json');

// Initialize controllers
$departmentController = new DepartmentController($conn);

// Get the action from the request
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $department_id = $departmentController->create($data);
        echo json_encode(['success' => (bool)$department_id, 'department_id' => $department_id]);
        break;

    case 'update':
        $department_id = $_POST['department_id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $success = $departmentController->update($department_id, $data);
        echo json_encode(['success' => $success]);
        break;

    case 'delete':
        $department_id = $_POST['department_id'] ?? 0;
        $success = $departmentController->delete($department_id);
        echo json_encode(['success' => $success]);
        break;

    case 'get':
        $department_id = $_GET['department_id'] ?? 0;
        $department = $departmentController->getById($department_id);
        echo json_encode(['department' => $department]);
        break;

    case 'list':
        $departments = $departmentController->getAll();
        echo json_encode(['departments' => $departments]);
        break;

    case 'stats':
        $department_id = $_GET['department_id'] ?? 0;
        $stats = $departmentController->getStats($department_id);
        echo json_encode(['stats' => $stats]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
} 