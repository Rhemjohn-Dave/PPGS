<?php
session_start();
require_once '../database/connection.php';
require_once '../controllers/TaskController.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get input data
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
    $equipment_name = isset($_POST['equipment_name']) ? trim($_POST['equipment_name']) : '';

    // Validate input
    if ($department_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department selected.']);
        exit;
    }
    if (empty($equipment_name)) {
        echo json_encode(['success' => false, 'message' => 'Equipment name cannot be empty.']);
        exit;
    }

    $taskController = new TaskController($conn);

    // Get repair frequency
    $repair_count = $taskController->getRepairFrequencyByEquipment($department_id, $equipment_name);

    echo json_encode(['success' => true, 'repair_count' => $repair_count]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?> 