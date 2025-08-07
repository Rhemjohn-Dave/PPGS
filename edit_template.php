<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

$name = $description = "";
$name_err = $description_err = "";

// Check if template ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: pdf_templates.php");
    exit;
}

$template_id = $_GET["id"];

// Get current template data
$sql = "SELECT * FROM pdf_templates WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $template_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0){
    $_SESSION['error_message'] = "Template not found.";
    header("location: pdf_templates.php");
    exit;
}

$template = mysqli_fetch_assoc($result);

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter a template name.";
    } else{
        $name = trim($_POST["name"]);
        
        // Check if template name already exists (excluding current template)
        $sql = "SELECT id FROM pdf_templates WHERE name = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $name, $template_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) > 0){
            $name_err = "This template name already exists.";
        }
        mysqli_stmt_close($stmt);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = trim($_POST["description"]);
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Handle file upload if new file is provided
        if(isset($_FILES["template_file"]) && $_FILES["template_file"]["error"] == 0){
            $file = $_FILES["template_file"];
            $file_type = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            
            if($file_type != "pdf"){
                throw new Exception("Only PDF files are allowed.");
            }
            
            // Create uploads directory if it doesn't exist
            $upload_dir = "uploads/pdf_templates/";
            if(!file_exists($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $unique_filename = uniqid() . ".pdf";
            $file_path = $upload_dir . $unique_filename;
            
            // Move uploaded file
            if(!move_uploaded_file($file["tmp_name"], $file_path)){
                throw new Exception("Error uploading file.");
            }
            
            // Delete old file
            if(file_exists($template["template_path"])){
                unlink($template["template_path"]);
            }
            
            // Update template with new file path
            $sql = "UPDATE pdf_templates SET name = ?, description = ?, template_path = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $file_path, $template_id);
        } else {
            // Update template without changing file
            $sql = "UPDATE pdf_templates SET name = ?, description = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $template_id);
        }
        
        if(!mysqli_stmt_execute($stmt)){
            throw new Exception("Error updating template: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success_message'] = "Template has been updated successfully.";
        header("location: pdf_templates.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
} else {
    // Initialize form with current template data
    $name = $template["name"];
    $description = $template["description"];
}

// Set page title
$page_title = "Edit PDF Template";

// Include header and sidebar
include 'includes/components/header.php';
include 'includes/components/sidebar.php';
?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        <?php include 'includes/components/navbar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Edit PDF Template</h1>
                <a href="pdf_templates.php" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Templates
                </a>
            </div>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Content Row -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $template_id; ?>" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Template Name</label>
                                    <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>">
                                    <span class="invalid-feedback"><?php echo $name_err; ?></span>
                                </div>
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="4"><?php echo $description; ?></textarea>
                                    <span class="invalid-feedback"><?php echo $description_err; ?></span>
                                </div>

                                <div class="form-group">
                                    <label>Current PDF Template</label>
                                    <div class="mb-2">
                                        <a href="<?php echo htmlspecialchars($template["template_path"]); ?>" class="btn btn-info btn-sm" target="_blank">
                                            <i class="fas fa-file-pdf"></i> View Current Template
                                        </a>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Upload New PDF Template (Optional)</label>
                                    <input type="file" name="template_file" class="form-control-file" accept=".pdf">
                                    <small class="form-text text-muted">Only PDF files are allowed. Leave empty to keep current template.</small>
                                </div>

                                <button type="submit" class="btn btn-primary">Update Template</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->

    <!-- Footer -->
    <?php include 'includes/components/footer.php'; ?>
    <!-- End of Footer -->
</div>
<!-- End of Content Wrapper -->

<?php include 'includes/components/footer_scripts.php'; ?> 