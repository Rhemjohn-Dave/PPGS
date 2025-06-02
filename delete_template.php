<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Check if template ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    $_SESSION['error_message'] = "Template ID is required.";
    header("location: pdf_templates.php");
    exit;
}

$template_id = $_GET["id"];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get template details first
    $sql = "SELECT template_path FROM pdf_templates WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $template_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) == 0){
        throw new Exception("Template not found.");
    }
    
    $template = mysqli_fetch_assoc($result);
    
    // Check if template is being used in any generated PDFs
    $sql = "SELECT COUNT(*) as count FROM generated_pdfs WHERE template_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $template_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if($row['count'] > 0){
        throw new Exception("Cannot delete template: It is being used in generated PDFs.");
    }
    
    // Delete the physical file
    if(file_exists($template["template_path"])){
        if(!unlink($template["template_path"])){
            throw new Exception("Error deleting template file.");
        }
    }
    
    // Delete the template record
    $sql = "DELETE FROM pdf_templates WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $template_id);
    
    if(!mysqli_stmt_execute($stmt)){
        throw new Exception("Error deleting template: " . mysqli_error($conn));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    $_SESSION['success_message'] = "Template has been deleted successfully.";
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
}

// Redirect back to templates page
header("location: pdf_templates.php");
exit; 