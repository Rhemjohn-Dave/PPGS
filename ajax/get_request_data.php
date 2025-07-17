<?php
session_start();
require_once '../database/connection.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit;
}

$requestId = intval($_GET['id']);

$sql = "SELECT tr.*, u.username as requester_username, u.full_name as requester_full_name, d.name as department_name
        FROM task_requests tr
        JOIN users u ON tr.requester_id = u.id
        JOIN departments d ON tr.department_id = d.id
        WHERE tr.id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $requestId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($request) {
        echo json_encode(['success' => true, 'request' => $request]);
        exit;
    }
}
echo json_encode(['success' => false, 'message' => 'Request not found']);
exit;