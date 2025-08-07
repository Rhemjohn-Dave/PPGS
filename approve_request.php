<?php
session_start();
require_once "config/database.php";
require_once "includes/helpers/notification_helper.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if request ID is provided
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    $_SESSION['error_message'] = "Invalid request.";
    header("location: task_approvals.php");
    exit;
}

$request_id = $_GET["id"];
$approver_id = $_SESSION["id"];
$approver_role = $_SESSION["role"];

// Get request details including department and requester info
$sql = "SELECT tr.*, u.full_name as requester_name, u.department_id as requester_dept_id, d.name as department_name
        FROM task_requests tr 
        JOIN users u ON tr.requester_id = u.id 
        JOIN departments d ON tr.department_id = d.id
        WHERE tr.id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$request) {
    $_SESSION['error_message'] = "Request not found.";
    header("location: task_approvals.php");
    exit;
}

// Determine which approval to update based on role
$update_field = "";
if ($approver_role == "program head") {
    // Verify the program head belongs to the request's department
    $check_dept_sql = "SELECT department_id FROM users WHERE id = ? AND role = 'program head'";
    $program_head_dept = null;
    if ($check_stmt = mysqli_prepare($conn, $check_dept_sql)) {
        mysqli_stmt_bind_param($check_stmt, "i", $approver_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        if ($check_row = mysqli_fetch_assoc($check_result)) {
            $program_head_dept = $check_row['department_id'];
        }
        mysqli_stmt_close($check_stmt);
    }

    if ($program_head_dept != $request['department_id']) {
        $_SESSION['error_message'] = "You are not authorized to approve requests for this department.";
        header("location: task_approvals.php");
        exit;
    }

    $update_field = "program_head_approval";
} elseif ($approver_role == "adaa") {
    $update_field = "adaa_approval";
} else {
    $_SESSION['error_message'] = "Unauthorized to approve requests.";
    header("location: task_approvals.php");
    exit;
}

// Update approval status
$sql = "UPDATE task_requests SET $update_field = 'approved' WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    if (mysqli_stmt_execute($stmt)) {
        // --- START NOTIFICATION LOGIC ---

        // 1. Notify the requester
        $requester_message = "Your task request '" . htmlspecialchars($request['title']) . "' has been approved by " .
            htmlspecialchars($_SESSION['full_name']) . " as " . ucfirst($approver_role) . ".";
        $requester_link = "tasks.php?view_request=" . $request_id;
        sendNotification([$request['requester_id']], $requester_message, $conn, $requester_link);

        // 2. If program head approved, notify ADAA
        if ($approver_role == "program head") {
            $adaa_ids = getUserIdsByRole('adaa', $conn);
            if (!empty($adaa_ids)) {
                $adaa_message = "Task request '" . htmlspecialchars($request['title']) . "' from " .
                    htmlspecialchars($request['requester_name']) . " (" . htmlspecialchars($request['department_name']) .
                    ") has been approved by Program Head " . htmlspecialchars($_SESSION['full_name']) . " and needs your approval.";
                $adaa_link = "task_approvals.php";
                sendNotification($adaa_ids, $adaa_message, $conn, $adaa_link);
            }

            // Log the action for debugging
            error_log("Program Head approval notification sent to " . count($adaa_ids) . " ADAAs for request #" . $request_id);
        }
        // If ADAA approved, notify admin
        elseif ($approver_role == "adaa") {
            $admin_ids = getUserIdsByRole('admin', $conn);
            if (!empty($admin_ids)) {
                $admin_message = "Task request '" . htmlspecialchars($request['title']) . "' from " .
                    htmlspecialchars($request['requester_name']) . " (" . htmlspecialchars($request['department_name']) .
                    ") has been fully approved and is ready for task creation.";
                $admin_link = "assign_tasks.php";
                sendNotification($admin_ids, $admin_message, $conn, $admin_link);
            }

            // Update overall status if both approvals are complete
            $update_status_sql = "UPDATE task_requests SET status = 'approved' WHERE id = ? AND program_head_approval = 'approved' AND adaa_approval = 'approved'";
            if ($status_stmt = mysqli_prepare($conn, $update_status_sql)) {
                mysqli_stmt_bind_param($status_stmt, "i", $request_id);
                mysqli_stmt_execute($status_stmt);
                mysqli_stmt_close($status_stmt);
            }

            // Log the action for debugging
            error_log("ADAA approval notification sent to " . count($admin_ids) . " admins for request #" . $request_id);
        }

        $_SESSION['success_message'] = "Request approved successfully.";
    } else {
        error_log("Error approving request: " . mysqli_stmt_error($stmt));
        $_SESSION['error_message'] = "Error approving request.";
    }
    mysqli_stmt_close($stmt);
}

header("location: task_approvals.php");
exit;
?>