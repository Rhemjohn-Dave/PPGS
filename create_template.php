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

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter a template name.";
    } else{
        $name = trim($_POST["name"]);
        
        // Check if template name already exists
        $sql = "SELECT id FROM pdf_templates WHERE name = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $name);
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
    
    // Validate file upload
    if(!isset($_FILES["template_file"]) || $_FILES["template_file"]["error"] != 0){
        $file_err = "Please upload a valid PDF file.";
    } else{
        $file = $_FILES["template_file"];
        $file_type = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        
        if($file_type != "pdf"){
            $file_err = "Only PDF files are allowed.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($name_err) && empty($description_err) && empty($file_err)){
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Create uploads directory if it doesn't exist
            $upload_dir = "uploads/pdf_templates/";
            if(!file_exists($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
            $unique_filename = uniqid() . "." . $file_extension;
            $file_path = $upload_dir . $unique_filename;
            
            // Move uploaded file
            if(!move_uploaded_file($file["tmp_name"], $file_path)){
                throw new Exception("Error uploading file.");
            }
            
            // Insert into database
            $sql = "INSERT INTO pdf_templates (name, description, template_path) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if($stmt === false) {
                throw new Exception("Error preparing statement: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "sss", $name, $description, $file_path);
            
            if(!mysqli_stmt_execute($stmt)){
                throw new Exception("Error creating template: " . mysqli_error($conn));
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $_SESSION['success_message'] = "Template has been created successfully.";
            header("location: pdf_templates.php");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
}

// Set page title
$page_title = "Create PDF Template";

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
                <h1 class="h3 mb-0 text-gray-800">Create PDF Template</h1>
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
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
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
                                    <label>PDF Template File</label>
                                    <input type="file" name="template_file" class="form-control-file <?php echo (!empty($file_err)) ? 'is-invalid' : ''; ?>" accept=".pdf">
                                    <span class="invalid-feedback"><?php echo $file_err; ?></span>
                                    <small class="form-text text-muted">Only PDF files are allowed.</small>
                                </div>

                                <button type="submit" class="btn btn-primary">Create Template</button>
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
</body>
</html>

<?php
// Connection is already closed by shutdown function
?> 