<?php
session_start();
require_once '../database/connection.php';
require_once '../controllers/NotificationController.php';
require_once '../includes/helpers/notification_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if (!isset($_POST['task_id']) || !isset($_POST['note']) || trim($_POST['note']) === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$task_id = intval($_POST['task_id']);
$note = trim($_POST['note']);
$user_id = $_SESSION['user_id'];

// Get assigned staff
$stmt = $conn->prepare("SELECT assigned_to, request_id FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();
if (!$task || !$task['assigned_to']) {
    echo json_encode(['success' => false, 'message' => 'Assigned staff not found for this task.']);
    exit;
}
$staff_id = $task['assigned_to'];

// Insert followup note
$stmt = $conn->prepare("INSERT INTO task_completion_notes (task_id, user_id, notes, note_type, created_at) VALUES (?, ?, ?, 'followup', NOW())");
$stmt->bind_param("iis", $task_id, $user_id, $note);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save follow-up note.']);
    exit;
}

// Send notification to staff
$message = "You have a new follow-up from the requestor on a postponed task.";
$link = "view_task.php?id=" . $task_id;
sendNotification($staff_id, $message, $conn, $link);

echo json_encode(['success' => true, 'message' => 'Follow-up sent and staff notified.']);