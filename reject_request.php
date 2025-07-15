<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

// Check if request ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: manage_requests.php");
    exit;
}

$request_id = $_GET["id"];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update request status to rejected
    $sql = "UPDATE task_requests SET status = 'rejected' WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Get task_id from the request
    $sql = "SELECT task_id FROM task_requests WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $task_id = $row['task_id'];
        mysqli_stmt_close($stmt);
    }

    // Update task status back to pending
    $sql = "UPDATE tasks SET status = 'pending' WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $task_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect back to manage requests page with success message
    $_SESSION['success_message'] = "Task request has been rejected successfully.";
    header("location: manage_requests.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    $_SESSION['error_message'] = "Error rejecting request. Please try again.";
    header("location: manage_requests.php");
    exit;
}
?> 