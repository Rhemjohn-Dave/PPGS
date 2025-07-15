<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Check if request ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    $_SESSION['error_message'] = "Request ID is required.";
    header("location: task_requests.php");
    exit;
}

$request_id = $_GET["id"];

// Get request details
$sql = "SELECT tr.*, t.title as task_title, t.description as task_description,
        u1.username as requester_name, u2.username as first_approver_name,
        u3.username as adaa_name, u4.username as assigned_staff_name,
        d.name as department_name
        FROM task_requests tr
        JOIN tasks t ON tr.task_id = t.id
        JOIN users u1 ON tr.requester_id = u1.id
        LEFT JOIN users u2 ON tr.first_approver_id = u2.id
        LEFT JOIN users u3 ON tr.dean_adaa_id = u3.id
        LEFT JOIN users u4 ON t.assigned_to = u4.id
        JOIN departments d ON u1.department_id = d.id
        WHERE tr.id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $request_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0){
    $_SESSION['error_message'] = "Request not found.";
    header("location: task_requests.php");
    exit;
}

$request = mysqli_fetch_assoc($result);

// Get template details
$sql = "SELECT * FROM pdf_templates WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $request["template_id"]);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0){
    $_SESSION['error_message'] = "Template not found.";
    header("location: task_requests.php");
    exit;
}

$template = mysqli_fetch_assoc($result);

// Create uploads directory if it doesn't exist
$upload_dir = "uploads/generated_pdfs/";
if(!file_exists($upload_dir)){
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$unique_filename = uniqid() . ".pdf";
$output_path = $upload_dir . $unique_filename;

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Copy template to output location
    if(!copy($template["template_path"], $output_path)){
        throw new Exception("Error copying template file.");
    }
    
    // Insert record in generated_pdfs table
    $sql = "INSERT INTO generated_pdfs (request_id, template_id, pdf_path, generated_by, generated_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisi", $request_id, $template["id"], $output_path, $_SESSION["id"]);
    
    if(!mysqli_stmt_execute($stmt)){
        throw new Exception("Error saving generated PDF record: " . mysqli_error($conn));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Redirect to download the generated PDF
    header("Location: " . $output_path);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    // Clean up any partially generated files
    if(file_exists($output_path)){
        unlink($output_path);
    }
    
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("location: task_requests.php");
    exit;
} 